<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class MigrateOldDatabase extends Command
{
    protected $signature = 'migrate:old-database {dump-path? : Pad naar SQL dump bestand} {--force : Skip bevestiging}';
    protected $description = 'Migreer data van de oude goforitsit_invoice database naar het nieuwe systeem';

    // ID mappings old → new
    private array $userMap = [];
    private array $customerMap = [];   // old client_id → new customer_id
    private array $invoiceMap = [];
    private array $quoteMap = [];

    public function handle(): int
    {
        $this->info('=== Oude Database Migratie ===');
        $this->newLine();

        // Stap 1: Check of oude database beschikbaar is
        try {
            $count = DB::connection('old')->table('users')->count();
            $this->info("✅ Oude database gevonden met {$count} gebruikers.");
        } catch (\Exception $e) {
            $this->error('❌ Kan niet verbinden met oude database.');
            $this->error('Voer eerst uit:');
            $this->error('  mysql -u root -e "CREATE DATABASE IF NOT EXISTS goforitsit_invoice_old"');
            $this->error('  mysql -u root goforitsit_invoice_old < /pad/naar/goforitsit_invoice.sql');
            $this->error('');
            $this->error('Fout: ' . $e->getMessage());
            return 1;
        }

        // Stap 2: Vraag bevestiging (overslaan met --force)
        if (!$this->option('force') && !$this->confirm('⚠️  Dit wist ALLE huidige data en importeert de oude database. Doorgaan?')) {
            $this->info('Geannuleerd.');
            return 0;
        }

        // Stap 3: Fresh migrate (wist alles, maakt schone tabellen)
        $this->info('');
        $this->info('📦 Database opnieuw aanmaken (migrate:fresh)...');
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->info('✅ Schone database aangemaakt.');

        // Stap 4: Migreer data
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->migrateUsers();
        $this->migrateCompanySettings();
        $this->migrateAppSettings();
        $this->migrateVatRates();
        $this->migrateCustomers();
        $this->migrateProducts();
        $this->migrateInvoices();
        $this->migrateQuotes();
        $this->createDefaultTemplates();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Stap 5: Samenvatting
        $this->newLine();
        $this->info('=== ✅ Migratie Voltooid ===');
        $this->newLine();
        $this->printSummary();

        return 0;
    }

    private function migrateUsers(): void
    {
        $this->info('');
        $this->info('👤 Migreren: Gebruikers...');

        // Filter op niet-verwijderde users, check ook op '0000-00-00' als deleted_at
        $oldUsers = DB::connection('old')->table('users')
            ->where(function ($q) {
                $q->whereNull('deleted_at')
                  ->orWhere('deleted_at', '0000-00-00 00:00:00');
            })
            ->get();

        $bar = $this->output->createProgressBar($oldUsers->count());
        $passwordHash = Hash::make('welkom2026');
        $seenEmails = [];
        $migrated = 0;

        foreach ($oldUsers as $old) {
            // Skip duplicaat emails
            $email = strtolower(trim($old->email));
            if (isset($seenEmails[$email])) {
                $this->userMap[$old->id] = $seenEmails[$email];
                $bar->advance();
                continue;
            }

            // Haal profiel op voor naam
            $profile = DB::connection('old')->table('profiles')
                ->where('id', $old->profile_id)
                ->first();

            $name = $profile->name ?? $old->email;

            // Fix ongeldige datums
            $createdAt = $this->fixDate($old->created_at);

            try {
                $newId = DB::table('users')->insertGetId([
                    'name'              => $name,
                    'email'             => $email,
                    'password'          => $passwordHash,
                    'email_verified_at' => now(),
                    'mfa_enabled'       => false,
                    'mfa_secret'        => null,
                    'is_admin'          => ($old->id == 1),
                    'status'            => 'approved',
                    'approved_at'       => now(),
                    'remember_token'    => null,
                    'created_at'        => $createdAt,
                    'updated_at'        => now(),
                ]);

                $this->userMap[$old->id] = $newId;
                $seenEmails[$email] = $newId;
                $migrated++;
            } catch (\Exception $e) {
                $this->warn("  ⚠️  Overslaan user {$email}: " . $e->getMessage());
                // Probeer bestaande user ID te vinden als fallback
                $existingId = DB::table('users')->where('email', $email)->value('id');
                if ($existingId) {
                    $this->userMap[$old->id] = $existingId;
                    $seenEmails[$email] = $existingId;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info(" → {$migrated} gebruikers gemigreerd.");
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

    private function migrateCompanySettings(): void
    {
        $this->info('🏢 Migreren: Bedrijfsgegevens...');

        foreach ($this->userMap as $oldId => $newId) {
            $oldUser = DB::connection('old')->table('users')->where('id', $oldId)->first();

            // Haal adres en profiel op
            $address = DB::connection('old')->table('addresses')
                ->where('id', $oldUser->address_id)
                ->first();

            $profile = DB::connection('old')->table('profiles')
                ->where('id', $oldUser->profile_id)
                ->first();

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
        }

        $this->info(" → " . count($this->userMap) . " bedrijfsprofielen aangemaakt.");
    }

    private function migrateAppSettings(): void
    {
        $this->info('⚙️  Migreren: App-instellingen...');

        foreach ($this->userMap as $oldId => $newId) {
            $oldUser = DB::connection('old')->table('users')->where('id', $oldId)->first();

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
        }

        $this->info(" → " . count($this->userMap) . " app-instellingen aangemaakt.");
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
        // Maak nette namen
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

            // Maak nummer uniek per user
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

            $this->invoiceMap[$old->id] = $newInvoiceId;

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
    }

    private function migrateQuotes(): void
    {
        $this->info('📋 Migreren: Offertes + regels...');

        $oldOffers = DB::connection('old')->table('offers')->get();
        $bar = $this->output->createProgressBar($oldOffers->count());
        $quoteCount = 0;
        $lineCount = 0;
        $seenNumbers = []; // Track per user_id => [quote_number => count]

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

            // Maak nummer uniek per user
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

            $paymentTerms = $this->parsePaymentTerm($old->payment_term ?? '30');

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

    private function createDefaultTemplates(): void
    {
        $this->info('🎨 Aanmaken: Standaard templates...');

        foreach ($this->userMap as $oldId => $newId) {
            DB::table('invoice_templates')->insert([
                'user_id'    => $newId,
                'name'       => 'Standaard Template',
                'is_default_invoice' => true,
                'is_default_quote' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info(" → " . count($this->userMap) . " standaard templates aangemaakt.");
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
        // Probeer een getal te extracten
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
                ['Gebruikers', DB::table('users')->count()],
                ['Bedrijfsgegevens', DB::table('company_settings')->count()],
                ['App-instellingen', DB::table('app_settings')->count()],
                ['BTW-tarieven', DB::table('vat_rates')->count()],
                ['Klanten', DB::table('customers')->count()],
                ['Producten', DB::table('products')->count()],
                ['Facturen', DB::table('invoices')->count()],
                ['Factuurregels', DB::table('invoice_lines')->count()],
                ['Offertes', DB::table('quotes')->count()],
                ['Offerteregels', DB::table('quote_lines')->count()],
                ['Templates', DB::table('invoice_templates')->count()],
            ]
        );

        $this->newLine();
        $this->info('📧 Alle accounts (wachtwoord: welkom2026):');
        $this->newLine();

        $users = DB::table('users')
            ->join('company_settings', 'users.id', '=', 'company_settings.user_id')
            ->select('users.id', 'users.email', 'users.name', 'company_settings.company_name')
            ->orderBy('users.id')
            ->get();

        $rows = [];
        foreach ($users as $user) {
            $invoiceCount = DB::table('invoices')->where('user_id', $user->id)->count();
            $quoteCount = DB::table('quotes')->where('user_id', $user->id)->count();
            $rows[] = [
                $user->id,
                $user->email,
                $user->company_name ?: $user->name,
                $invoiceCount,
                $quoteCount,
            ];
        }

        $this->table(
            ['ID', 'E-mail', 'Bedrijf', 'Facturen', 'Offertes'],
            $rows
        );
    }
}
