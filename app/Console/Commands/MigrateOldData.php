<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateOldData extends Command
{
    protected $signature = 'migrate:old-data {--force : Skip bevestiging}';
    protected $description = 'Importeer data (BTW, klanten, producten, facturen, offertes) uit de oude goforitsit_invoice database en koppel op e-mail aan bestaande accounts. Users blijven intact.';

    // ID mappings old → new
    private array $userMap = [];       // old user_id → new user_id (gematcht op e-mail)
    private array $customerMap = [];   // old client_id → new customer_id

    public function handle(): int
    {
        $this->info('=== Oude Data Import (users blijven intact) ===');
        $this->newLine();

        // Stap 1: Check of oude database beschikbaar is
        try {
            $count = DB::connection('old')->table('users')->count();
            $this->info("✅ Oude database gevonden met {$count} gebruikers.");
        } catch (\Exception $e) {
            $this->error('❌ Kan niet verbinden met oude database.');
            $this->error('Importeer eerst de dump: php import-old-dump.php goforitsit_invoice_20260713.sql');
            $this->error('Fout: ' . $e->getMessage());
            return 1;
        }

        // Stap 2: Map oude users op bestaande accounts via e-mail
        if (!$this->mapUsers()) {
            return 1;
        }

        // Stap 3: Bevestiging
        if (!$this->option('force') && !$this->confirm('⚠️  Dit wist bestaande klanten/facturen/offertes/BTW/producten van de gematchte accounts en importeert de oude data opnieuw. Users blijven staan. Doorgaan?')) {
            $this->info('Geannuleerd.');
            return 0;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Stap 4: Bestaande data van gematchte accounts opruimen (idempotent)
        $this->wipeExistingData();

        // Stap 5: Data importeren
        $this->migrateVatRates();
        $this->migrateCustomers();
        $this->migrateProducts();
        $this->migrateInvoices();
        $this->migrateQuotes();
        $this->ensureSettingsAndTemplates();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Stap 6: Samenvatting
        $this->newLine();
        $this->info('=== ✅ Import Voltooid ===');
        $this->newLine();
        $this->printSummary();

        return 0;
    }

    private function mapUsers(): bool
    {
        $this->info('');
        $this->info('🔗 Mappen: Oude users → bestaande accounts (op e-mail)...');

        $oldUsers = DB::connection('old')->table('users')
            ->where(function ($q) {
                $q->whereNull('deleted_at')
                  ->orWhere('deleted_at', '0000-00-00 00:00:00');
            })
            ->get();

        // Bestaande accounts indexeren op e-mail
        $newUsers = DB::table('users')->pluck('id', 'email')
            ->mapWithKeys(fn ($id, $email) => [strtolower(trim($email)) => $id]);

        $unmatched = [];
        foreach ($oldUsers as $old) {
            $email = strtolower(trim($old->email));
            if (isset($newUsers[$email])) {
                $this->userMap[$old->id] = $newUsers[$email];
            } else {
                $invoiceCount = DB::connection('old')->table('invoices')->where('user_id', $old->id)->count();
                $unmatched[] = [$old->id, $old->email, $invoiceCount];
            }
        }

        $this->info(' → ' . count($this->userMap) . ' van ' . $oldUsers->count() . ' oude users gematcht.');

        if ($unmatched) {
            $this->newLine();
            $this->warn('⚠️  Niet gematcht (data van deze users wordt overgeslagen):');
            $this->table(['Oude ID', 'E-mail', 'Facturen'], $unmatched);
        }

        if (empty($this->userMap)) {
            $this->error('❌ Geen enkele oude user kon op e-mail gematcht worden. Gestopt.');
            return false;
        }

        return true;
    }

    /**
     * Wist eerder geïmporteerde data van de gematchte accounts,
     * zodat het commando veilig opnieuw gedraaid kan worden.
     * Users, company_settings en app_settings blijven onaangeroerd.
     */
    private function wipeExistingData(): void
    {
        $this->info('');
        $this->info('🧹 Opruimen: bestaande data van gematchte accounts...');

        $userIds = array_values(array_unique($this->userMap));

        $invoiceIds = DB::table('invoices')->whereIn('user_id', $userIds)->pluck('id');
        $quoteIds   = DB::table('quotes')->whereIn('user_id', $userIds)->pluck('id');

        DB::table('invoice_lines')->whereIn('invoice_id', $invoiceIds)->delete();
        DB::table('invoices')->whereIn('id', $invoiceIds)->delete();
        DB::table('quote_lines')->whereIn('quote_id', $quoteIds)->delete();
        DB::table('quotes')->whereIn('id', $quoteIds)->delete();
        DB::table('customers')->whereIn('user_id', $userIds)->delete();
        DB::table('products')->whereIn('user_id', $userIds)->delete();
        DB::table('vat_rates')->whereIn('user_id', $userIds)->delete();

        $this->info(' → Opgeruimd voor ' . count($userIds) . ' accounts.');
    }

    /**
     * Fix ongeldige MySQL datums (0000-00-00) naar een geldige datum.
     */
    private function fixDate($date): string
    {
        if (empty($date) || $date === '0000-00-00 00:00:00' || $date === '0000-00-00'
            || (is_string($date) && str_starts_with($date, '0000'))) {
            return now()->toDateTimeString();
        }
        return (string)$date;
    }

    private function migrateVatRates(): void
    {
        $this->info('💰 Migreren: BTW-tarieven...');

        $oldTaxes = DB::connection('old')->table('taxes')->get();
        $count = 0;

        foreach ($oldTaxes as $old) {
            if (!isset($this->userMap[$old->user_id])) continue;

            DB::table('vat_rates')->insert([
                'user_id'    => $this->userMap[$old->user_id],
                'name'       => $this->formatTaxName($old->title, $old->percentage),
                'rate'       => $old->percentage,
                'is_default' => ($old->percentage == 21),
                'sort_order' => $old->percentage == 21 ? 0 : ($old->percentage == 9 ? 1 : ($old->percentage == 6 ? 2 : 3)),
                'created_at' => $this->fixDate($old->created_at ?? null),
                'updated_at' => now(),
            ]);
            $count++;
        }

        $this->info(" → {$count} BTW-tarieven gemigreerd.");
    }

    private function formatTaxName(string $title, int $percentage): string
    {
        $title = trim($title);
        if (stripos($title, 'hoog') !== false || $percentage == 21) return "BTW Hoog ({$percentage}%)";
        if (stripos($title, 'laag') !== false) return "BTW Laag ({$percentage}%)";
        if ($percentage == 0) return "BTW Nul (0%)";
        return "{$title} ({$percentage}%)";
    }

    private function migrateCustomers(): void
    {
        $this->info('👥 Migreren: Klanten...');

        $oldClients = DB::connection('old')->table('clients')->get();
        $bar = $this->output->createProgressBar($oldClients->count());
        $count = 0;

        foreach ($oldClients as $old) {
            if (!isset($this->userMap[$old->user_id])) {
                $bar->advance();
                continue;
            }

            $profile = DB::connection('old')->table('profiles')
                ->where('id', $old->profile_id)
                ->first();

            $address = DB::connection('old')->table('addresses')
                ->where('id', $old->location_address_id)
                ->first();

            $streetFull = '';
            if ($address) {
                $streetFull = trim(($address->street ?? '') . ' ' . ($address->housenumber ?? ''));
            }

            $newId = DB::table('customers')->insertGetId([
                'user_id'      => $this->userMap[$old->user_id],
                'name'         => $profile->name ?? 'Onbekend',
                'email'        => $profile->invoice_email ?: ($profile->email ?? ''),
                'phone'        => $profile->phone ?? '',
                'company_name' => $profile->name ?? '',
                'vat_number'   => $old->vat ?? '',
                'address'      => $streetFull,
                'city'         => $address->city ?? '',
                'postal_code'  => $address->postcode ?? '',
                'country'      => 'Nederland',
                'active'       => true,
                'created_at'   => $this->fixDate($old->created_at ?? null),
                'updated_at'   => now(),
            ]);

            $this->customerMap[$old->id] = $newId;
            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->info(" → {$count} klanten gemigreerd.");
    }

    private function migrateProducts(): void
    {
        $this->info('📦 Migreren: Producten...');

        $oldProducts = DB::connection('old')->table('products')->get();
        $count = 0;

        foreach ($oldProducts as $old) {
            if (!isset($this->userMap[$old->user_id])) continue;

            DB::table('products')->insert([
                'user_id'     => $this->userMap[$old->user_id],
                'name'        => $old->title ?? 'Product',
                'description' => '',
                'price'       => $old->price ?? 0,
                'active'      => true,
                'created_at'  => $this->fixDate($old->created_at ?? null),
                'updated_at'  => now(),
            ]);
            $count++;
        }

        $this->info(" → {$count} producten gemigreerd.");
    }

    private function migrateInvoices(): void
    {
        $this->info('📄 Migreren: Facturen + regels...');

        $oldInvoices = DB::connection('old')->table('invoices')->get();
        $bar = $this->output->createProgressBar($oldInvoices->count());
        $invoiceCount = 0;
        $lineCount = 0;
        $missingLines = [];
        $seenNumbers = []; // Track per user_id => [invoice_number => count]

        foreach ($oldInvoices as $old) {
            if (!isset($this->userMap[$old->user_id])) {
                $bar->advance();
                continue;
            }
            if (!isset($this->customerMap[$old->client_id])) {
                $bar->advance();
                continue;
            }

            // Haal regels op en bereken totalen
            $oldLines = DB::connection('old')->table('invoice_rows')
                ->where('invoice_id', $old->id)
                ->get();

            if ($oldLines->isEmpty()) {
                $missingLines[] = $old->id;
            }

            $subtotal = 0;
            $vatAmount = 0;
            $inclTax = (bool)$old->price_incl_tax;

            foreach ($oldLines as $line) {
                $lineTotal = round((float)$line->amount * (float)$line->price, 2);
                $taxPct = (int)$line->tax_percentage;

                if ($inclTax) {
                    // Prijs is inclusief BTW
                    $lineExcl = round($lineTotal / (1 + $taxPct / 100), 2);
                    $lineVat = $lineTotal - $lineExcl;
                    $subtotal += $lineExcl;
                    $vatAmount += $lineVat;
                } else {
                    // Prijs is exclusief BTW
                    $subtotal += $lineTotal;
                    if (!$old->tax_shifted) {
                        $vatAmount += round($lineTotal * $taxPct / 100, 2);
                    }
                }
            }

            $total = round($subtotal + $vatAmount, 2);

            // Factuurnummer samenstellen + deduplicatie per user
            $newUserId = $this->userMap[$old->user_id];
            $baseNumber = trim(($old->number_prefix ?? '') . ($old->number ?? ''));
            if (empty($baseNumber)) {
                $baseNumber = 'LEGACY-' . $old->id;
            }

            $invoiceNumber = $baseNumber;
            if (!isset($seenNumbers[$newUserId])) {
                $seenNumbers[$newUserId] = [];
            }
            if (isset($seenNumbers[$newUserId][$invoiceNumber])) {
                $seenNumbers[$newUserId][$invoiceNumber]++;
                $invoiceNumber = $baseNumber . '-' . $seenNumbers[$newUserId][$invoiceNumber];
            } else {
                $seenNumbers[$newUserId][$invoiceNumber] = 1;
            }

            // Status bepalen uit information veld
            $status = $this->detectInvoiceStatus($old->information ?? '');

            // Betalingstermijn parsen (varchar → int)
            $paymentTerms = $this->parsePaymentTerm($old->payment_term ?? '14');

            // Due date berekenen
            $invoiceDate = $this->fixDate($old->invoice_date ?? $old->created_at ?? null);
            $dueDate = date('Y-m-d', strtotime($invoiceDate . " + {$paymentTerms} days"));

            // Notities combineren uit description + information
            $notes = '';
            if (!empty($old->description)) $notes .= $old->description;
            if (!empty($old->information)) {
                if ($notes) $notes .= "\n\n";
                $notes .= $old->information;
            }

            try {
                $newInvoiceId = DB::table('invoices')->insertGetId([
                    'user_id'              => $newUserId,
                    'customer_id'          => $this->customerMap[$old->client_id],
                    'template_id'          => null,
                    'invoice_number'       => $invoiceNumber,
                    'invoice_date'         => $invoiceDate,
                    'due_date'             => $dueDate,
                    'payment_terms'        => $paymentTerms,
                    'subtotal'             => $subtotal,
                    'vat_amount'           => $vatAmount,
                    'total'                => $total,
                    'status'               => $status,
                    'vat_reverse_charged'  => (bool)$old->tax_shifted,
                    'sent_at'              => $status !== 'draft' ? $invoiceDate : null,
                    'paid_at'              => $status === 'paid' ? $this->fixDate($old->updated_at ?? null) : null,
                    'notes'                => $notes ?: null,
                    'created_at'           => $this->fixDate($old->created_at ?? null),
                    'updated_at'           => $this->fixDate($old->updated_at ?? null),
                ]);
            } catch (\Exception $e) {
                $this->warn("  ⚠️  Factuur {$invoiceNumber} overslaan: " . $e->getMessage());
                $bar->advance();
                continue;
            }

            // Regels migreren
            foreach ($oldLines as $line) {
                $lineTotal = round((float)$line->amount * (float)$line->price, 2);
                $taxPct = (int)$line->tax_percentage;

                if ($inclTax) {
                    $unitPriceExcl = round((float)$line->price / (1 + $taxPct / 100), 2);
                    $lineTotal = round((float)$line->amount * $unitPriceExcl, 2);
                } else {
                    $unitPriceExcl = (float)$line->price;
                }

                DB::table('invoice_lines')->insert([
                    'invoice_id'  => $newInvoiceId,
                    'description' => $line->title ?? 'Omschrijving',
                    'quantity'    => max(1, (int)round((float)$line->amount)),
                    'unit_price'  => $unitPriceExcl,
                    'vat_rate'    => $taxPct,
                    'total'       => $lineTotal,
                    'created_at'  => $this->fixDate($line->created_at ?? null),
                    'updated_at'  => now(),
                ]);
                $lineCount++;
            }

            $invoiceCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->info(" → {$invoiceCount} facturen + {$lineCount} regels gemigreerd.");

        if ($missingLines) {
            $this->warn('  ⚠️  ' . count($missingLines) . ' facturen zonder regels in de dump (oude IDs): ' . implode(', ', array_slice($missingLines, 0, 20)) . (count($missingLines) > 20 ? ', …' : ''));
        }
    }

    private function migrateQuotes(): void
    {
        $this->info('📋 Migreren: Offertes + regels...');

        $oldOffers = DB::connection('old')->table('offers')->get();
        $bar = $this->output->createProgressBar($oldOffers->count());
        $quoteCount = 0;
        $lineCount = 0;
        $seenNumbers = [];

        foreach ($oldOffers as $old) {
            if (!isset($this->userMap[$old->user_id])) {
                $bar->advance();
                continue;
            }
            if (!isset($this->customerMap[$old->client_id])) {
                $bar->advance();
                continue;
            }

            $oldLines = DB::connection('old')->table('offer_rows')
                ->where('offer_id', $old->id)
                ->get();

            $subtotal = 0;
            $vatAmount = 0;
            $inclTax = (bool)$old->price_incl_tax;

            foreach ($oldLines as $line) {
                $lineTotal = round((float)$line->amount * (float)$line->price, 2);
                $taxPct = (int)$line->tax_percentage;

                if ($inclTax) {
                    $lineExcl = round($lineTotal / (1 + $taxPct / 100), 2);
                    $lineVat = $lineTotal - $lineExcl;
                    $subtotal += $lineExcl;
                    $vatAmount += $lineVat;
                } else {
                    $subtotal += $lineTotal;
                    if (!$old->tax_shifted) {
                        $vatAmount += round($lineTotal * $taxPct / 100, 2);
                    }
                }
            }

            $total = round($subtotal + $vatAmount, 2);

            // Offertenummer samenstellen + deduplicatie per user
            $newUserId = $this->userMap[$old->user_id];
            $baseNumber = trim(($old->number_prefix ?? '') . ($old->number ?? ''));
            if (empty($baseNumber)) {
                $baseNumber = 'LEGACY-' . $old->id;
            }

            $quoteNumber = $baseNumber;
            if (!isset($seenNumbers[$newUserId])) {
                $seenNumbers[$newUserId] = [];
            }
            if (isset($seenNumbers[$newUserId][$quoteNumber])) {
                $seenNumbers[$newUserId][$quoteNumber]++;
                $quoteNumber = $baseNumber . '-' . $seenNumbers[$newUserId][$quoteNumber];
            } else {
                $seenNumbers[$newUserId][$quoteNumber] = 1;
            }

            $offerDate = $this->fixDate($old->offer_date ?? $old->created_at ?? null);
            $validUntil = date('Y-m-d', strtotime($offerDate . ' + 30 days'));

            $notes = '';
            if (!empty($old->description)) $notes .= $old->description;
            if (!empty($old->information)) {
                if ($notes) $notes .= "\n\n";
                $notes .= $old->information;
            }

            try {
                $newQuoteId = DB::table('quotes')->insertGetId([
                    'user_id'       => $newUserId,
                    'customer_id'   => $this->customerMap[$old->client_id],
                    'template_id'   => null,
                    'quote_number'  => $quoteNumber,
                    'quote_date'    => $offerDate,
                    'valid_until'   => $validUntil,
                    'valid_days'    => 30,
                    'subtotal'      => $subtotal,
                    'vat_amount'    => $vatAmount,
                    'total'         => $total,
                    'status'        => 'sent',
                    'sent_at'       => $offerDate,
                    'notes'         => $notes ?: null,
                    'created_at'    => $this->fixDate($old->created_at ?? null),
                    'updated_at'    => $this->fixDate($old->updated_at ?? null),
                ]);
            } catch (\Exception $e) {
                $this->warn("  ⚠️  Offerte {$quoteNumber} overslaan: " . $e->getMessage());
                $bar->advance();
                continue;
            }

            foreach ($oldLines as $line) {
                $lineTotal = round((float)$line->amount * (float)$line->price, 2);
                $taxPct = (int)$line->tax_percentage;

                if ($inclTax) {
                    $unitPriceExcl = round((float)$line->price / (1 + $taxPct / 100), 2);
                    $lineTotal = round((float)$line->amount * $unitPriceExcl, 2);
                } else {
                    $unitPriceExcl = (float)$line->price;
                }

                DB::table('quote_lines')->insert([
                    'quote_id'    => $newQuoteId,
                    'description' => $line->title ?? 'Omschrijving',
                    'quantity'    => round((float)$line->amount, 2),
                    'unit_price'  => $unitPriceExcl,
                    'vat_rate'    => $taxPct,
                    'total'       => $lineTotal,
                    'created_at'  => $this->fixDate($line->created_at ?? null),
                    'updated_at'  => now(),
                ]);
                $lineCount++;
            }

            $quoteCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->info(" → {$quoteCount} offertes + {$lineCount} regels gemigreerd.");
    }

    /**
     * Vult ontbrekende company_settings/app_settings/templates aan.
     * Bestaande instellingen blijven onaangeroerd (accounts zijn leidend).
     */
    private function ensureSettingsAndTemplates(): void
    {
        $this->info('⚙️  Controleren: instellingen & templates...');

        $created = 0;
        foreach ($this->userMap as $oldId => $newId) {
            $oldUser = DB::connection('old')->table('users')->where('id', $oldId)->first();

            if (!DB::table('company_settings')->where('user_id', $newId)->exists()) {
                $address = $oldUser->address_id
                    ? DB::connection('old')->table('addresses')->where('id', $oldUser->address_id)->first()
                    : null;
                $profile = $oldUser->profile_id
                    ? DB::connection('old')->table('profiles')->where('id', $oldUser->profile_id)->first()
                    : null;

                $streetFull = '';
                if ($address) {
                    $streetFull = trim(($address->street ?? '') . ' ' . ($address->housenumber ?? ''));
                }

                DB::table('company_settings')->insert([
                    'user_id'       => $newId,
                    'company_name'  => $profile->name ?? '',
                    'address'       => $streetFull,
                    'postal_code'   => $address->postcode ?? '',
                    'city'          => $address->city ?? '',
                    'country'       => 'Nederland',
                    'phone'         => $profile->phone ?? '',
                    'email'         => $profile->email ?? $oldUser->email,
                    'website'       => $profile->website ?? '',
                    'kvk_number'    => $oldUser->kvk ?? '',
                    'vat_number'    => $oldUser->vat ?? '',
                    'iban'          => $oldUser->iban ?? '',
                    'bic'           => '',
                    'bank_name'     => '',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $created++;
            }

            if (!DB::table('app_settings')->where('user_id', $newId)->exists()) {
                DB::table('app_settings')->insert([
                    'user_id'               => $newId,
                    'default_vat_rate'      => 21.00,
                    'default_payment_terms' => 14,
                    'quote_valid_days'      => 30,
                    'currency'              => 'EUR',
                    'currency_symbol'       => '€',
                    'date_format'           => 'd-m-Y',
                    'invoice_prefix'        => $oldUser->invoice_prefix ?? 'INV',
                    'quote_prefix'          => $oldUser->offer_prefix ?? 'OFF',
                    'invoice_number_start'  => max(1, (int)($oldUser->invoice_count ?? 1)),
                    'quote_number_start'    => max(1, (int)($oldUser->offer_count ?? 1)),
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
                $created++;
            }

            if (!DB::table('invoice_templates')->where('user_id', $newId)->exists()) {
                DB::table('invoice_templates')->insert([
                    'user_id'    => $newId,
                    'name'       => 'Standaard Template',
                    'is_default_invoice' => true,
                    'is_default_quote' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created++;
            }
        }

        $this->info(" → {$created} ontbrekende instellingen/templates aangevuld.");
    }

    private function detectInvoiceStatus(string $information): string
    {
        $lower = mb_strtolower($information);

        if (str_contains($lower, 'voldaan') ||
            str_contains($lower, 'betaald') ||
            str_contains($lower, 'ontvangen') ||
            str_contains($lower, 'contant') ||
            str_contains($lower, 'kas:') ||
            str_contains($lower, 'pin:') ||
            str_contains($lower, 'bank:')) {
            return 'paid';
        }

        return 'sent'; // Oude facturen zijn sowieso verstuurd
    }

    private function parsePaymentTerm(string $term): int
    {
        if (preg_match('/(\d+)/', $term, $matches)) {
            return max(1, (int)$matches[1]);
        }
        return 14; // default
    }

    private function printSummary(): void
    {
        $this->table(
            ['Tabel', 'Aantal'],
            [
                ['Gebruikers (onaangeroerd)', DB::table('users')->count()],
                ['BTW-tarieven', DB::table('vat_rates')->count()],
                ['Klanten', DB::table('customers')->count()],
                ['Producten', DB::table('products')->count()],
                ['Facturen', DB::table('invoices')->count()],
                ['Factuurregels', DB::table('invoice_lines')->count()],
                ['Offertes', DB::table('quotes')->count()],
                ['Offerteregels', DB::table('quote_lines')->count()],
            ]
        );

        $this->newLine();
        $this->info('📧 Data per account:');
        $this->newLine();

        $rows = [];
        foreach (array_unique($this->userMap) as $newId) {
            $user = DB::table('users')->where('id', $newId)->first();
            $rows[] = [
                $newId,
                $user->email,
                DB::table('customers')->where('user_id', $newId)->count(),
                DB::table('invoices')->where('user_id', $newId)->count(),
                DB::table('quotes')->where('user_id', $newId)->count(),
            ];
        }
        sort($rows);

        $this->table(
            ['ID', 'E-mail', 'Klanten', 'Facturen', 'Offertes'],
            $rows
        );
    }
}
