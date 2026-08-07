<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    /**
     * Seed realistische Nederlandse testdata voor alle users.
     * Veilig om meerdere keren te draaien: skipt users die al klanten hebben.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $this->command->info("Seeding data voor user #{$user->id} ({$user->email})...");

            $this->seedCompanySettings($user);
            $this->seedAppSettings($user);
            $this->seedVatRates($user);
            $this->seedTemplates($user);
            $customerIds = $this->seedCustomers($user);
            $productIds = $this->seedProducts($user);
            $this->seedInvoices($user, $customerIds, $productIds);
            $this->seedQuotes($user, $customerIds, $productIds);

            $this->command->info("  ✓ User #{$user->id} klaar.");
        }
    }

    private function seedCompanySettings(User $user): void
    {
        if (DB::table('company_settings')->where('user_id', $user->id)->exists()) {
            return;
        }

        DB::table('company_settings')->insert([
            'user_id' => $user->id,
            'company_name' => $user->company_name ?: 'Testbedrijf ' . $user->name,
            'address' => 'Keizersgracht 123',
            'postal_code' => '1015 CJ',
            'city' => 'Amsterdam',
            'country' => 'Nederland',
            'phone' => '020-1234567',
            'email' => $user->email,
            'website' => 'https://www.testbedrijf.nl',
            'kvk_number' => '1234567' . $user->id,
            'vat_number' => 'NL00123456' . str_pad($user->id, 2, '0', STR_PAD_LEFT) . 'B01',
            'iban' => 'NL91ABNA041716430' . $user->id,
            'bic' => 'ABNANL2A',
            'bank_name' => 'ABN AMRO',
            'invoice_footer' => "Betaling binnen 14 dagen na factuurdatum.\nVermeld het factuurnummer bij uw betaling.",
            'logo_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedAppSettings(User $user): void
    {
        if (DB::table('app_settings')->where('user_id', $user->id)->exists()) {
            return;
        }

        DB::table('app_settings')->insert([
            'user_id' => $user->id,
            'default_vat_rate' => 21.00,
            'default_payment_terms' => 14,
            'quote_valid_days' => 30,
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'date_format' => 'd-m-Y',
            'invoice_prefix' => 'INV',
            'quote_prefix' => 'OFF',
            'invoice_number_start' => 1,
            'quote_number_start' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedVatRates(User $user): void
    {
        if (DB::table('vat_rates')->where('user_id', $user->id)->exists()) {
            return;
        }

        $rates = [
            ['name' => 'Hoog tarief',  'rate' => 21.00, 'is_default' => true,  'sort_order' => 1],
            ['name' => 'Laag tarief',  'rate' =>  9.00, 'is_default' => false, 'sort_order' => 2],
            ['name' => 'Vrijgesteld',  'rate' =>  0.00, 'is_default' => false, 'sort_order' => 3],
        ];

        foreach ($rates as $rate) {
            DB::table('vat_rates')->insert(array_merge($rate, [
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    private function seedTemplates(User $user): void
    {
        // Wordt al afgehandeld door InvoiceTemplateSeeder, maar voor de zekerheid
        if (DB::table('invoice_templates')->where('user_id', $user->id)->exists()) {
            return;
        }

        (new InvoiceTemplateSeeder())->run();
    }

    /**
     * @return array<int> customer IDs
     */
    private function seedCustomers(User $user): array
    {
        // Return bestaande IDs als user al klanten heeft
        $existing = DB::table('customers')->where('user_id', $user->id)->pluck('id')->toArray();
        if (count($existing) >= 10) {
            return $existing;
        }
        $customers = [
            [
                'name' => 'Jan de Vries',
                'email' => 'jan@devries-consultancy.nl',
                'phone' => '06-12345678',
                'company_name' => 'De Vries Consultancy B.V.',
                'vat_number' => 'NL854372890B01',
                'address' => 'Herengracht 456',
                'city' => 'Amsterdam',
                'postal_code' => '1017 CA',
                'country' => 'Nederland',
            ],
            [
                'name' => 'Maria Bakker',
                'email' => 'maria@bakker-design.nl',
                'phone' => '06-23456789',
                'company_name' => 'Bakker Design Studio',
                'vat_number' => 'NL123456789B02',
                'address' => 'Oudegracht 78',
                'city' => 'Utrecht',
                'postal_code' => '3511 AR',
                'country' => 'Nederland',
            ],
            [
                'name' => 'Pieter Jansen',
                'email' => 'pieter@techflow.nl',
                'phone' => '06-34567890',
                'company_name' => 'TechFlow Solutions B.V.',
                'vat_number' => 'NL987654321B01',
                'address' => 'Coolsingel 104',
                'city' => 'Rotterdam',
                'postal_code' => '3011 AG',
                'country' => 'Nederland',
            ],
            [
                'name' => 'Sophie van den Berg',
                'email' => 'sophie@vandenberg-advocaten.nl',
                'phone' => '070-4567890',
                'company_name' => 'Van den Berg Advocaten',
                'vat_number' => 'NL456789012B01',
                'address' => 'Lange Voorhout 15',
                'city' => 'Den Haag',
                'postal_code' => '2514 EA',
                'country' => 'Nederland',
            ],
            [
                'name' => 'Thomas Mulder',
                'email' => 'thomas@mulder-bouw.nl',
                'phone' => '06-45678901',
                'company_name' => 'Mulder Bouwbedrijf',
                'vat_number' => 'NL567890123B01',
                'address' => 'Industrieweg 42',
                'city' => 'Eindhoven',
                'postal_code' => '5651 GJ',
                'country' => 'Nederland',
            ],
            [
                'name' => 'Lisa Visser',
                'email' => 'lisa@visser-marketing.nl',
                'phone' => '06-56789012',
                'company_name' => 'Visser Online Marketing',
                'vat_number' => 'NL678901234B01',
                'address' => 'Grote Markt 18',
                'city' => 'Groningen',
                'postal_code' => '9712 HN',
                'country' => 'Nederland',
            ],
            [
                'name' => 'Robert Hendriks',
                'email' => 'robert@hendriks-transport.nl',
                'phone' => '06-67890123',
                'company_name' => 'Hendriks Transport & Logistiek',
                'vat_number' => 'NL789012345B01',
                'address' => 'Havenweg 5',
                'city' => 'Tilburg',
                'postal_code' => '5015 AA',
                'country' => 'Nederland',
            ],
            [
                'name' => 'Emma Dijkstra',
                'email' => 'emma@dijkstra-events.nl',
                'phone' => '06-78901234',
                'company_name' => 'Dijkstra Events & Catering',
                'vat_number' => 'NL890123456B01',
                'address' => 'Stationsplein 3',
                'city' => 'Arnhem',
                'postal_code' => '6811 KG',
                'country' => 'Nederland',
            ],
            [
                'name' => 'Kees Smit',
                'email' => 'kees@smit-elektro.nl',
                'phone' => '06-89012345',
                'company_name' => 'Smit Elektrotechniek',
                'vat_number' => 'NL901234567B01',
                'address' => 'Dorpsstraat 112',
                'city' => 'Haarlem',
                'postal_code' => '2011 AJ',
                'country' => 'Nederland',
            ],
            [
                'name' => 'Anne Willems',
                'email' => 'anne@willems-fotografie.nl',
                'phone' => '06-90123456',
                'company_name' => 'Willems Fotografie',
                'vat_number' => null,
                'address' => 'Kerkstraat 7',
                'city' => 'Maastricht',
                'postal_code' => '6211 TC',
                'country' => 'Nederland',
            ],
        ];

        $ids = [];
        foreach ($customers as $customer) {
            $ids[] = DB::table('customers')->insertGetId(array_merge($customer, [
                'user_id' => $user->id,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        return $ids;
    }

    /**
     * @return array<int> product IDs
     */
    private function seedProducts(User $user): array
    {
        // Return bestaande IDs als user al producten heeft
        $existing = DB::table('products')->where('user_id', $user->id)->pluck('id')->toArray();
        if (count($existing) >= 12) {
            return $existing;
        }
        $products = [
            ['name' => 'Webdesign pakket',         'description' => 'Volledig responsive website design inclusief revisies',   'price' => 2500.00],
            ['name' => 'Logo ontwerp',              'description' => 'Professioneel logo ontwerp met 3 concepten',              'price' =>  750.00],
            ['name' => 'SEO optimalisatie',         'description' => 'Zoekmachineoptimalisatie maandpakket',                    'price' =>  450.00],
            ['name' => 'Hosting (jaarlijks)',       'description' => 'Managed hosting met SSL, backup en onderhoud',            'price' =>  360.00],
            ['name' => 'Consultancy (per uur)',     'description' => 'ICT consultancy en advies',                               'price' =>   95.00],
            ['name' => 'WordPress onderhoud',       'description' => 'Maandelijks WordPress updates en monitoring',             'price' =>  150.00],
            ['name' => 'E-mailmarketing setup',     'description' => 'Configuratie nieuwsbriefsysteem + eerste template',       'price' =>  650.00],
            ['name' => 'Social media beheer',       'description' => 'Maandelijks beheer van 3 social media kanalen',           'price' =>  800.00],
            ['name' => 'Fotografie (per dagdeel)',  'description' => 'Professionele bedrijfsfotografie op locatie',              'price' =>  450.00],
            ['name' => 'Drukwerk visitekaartjes',   'description' => '500 stuks dubbelzijdig full-colour visitekaartjes',       'price' =>   85.00],
            ['name' => 'Webshop ontwikkeling',      'description' => 'WooCommerce webshop met iDEAL betaling',                 'price' => 4500.00],
            ['name' => 'Grafisch ontwerp (per uur)','description' => 'Grafisch ontwerpwerk op uurbasis',                        'price' =>   75.00],
        ];

        $ids = [];
        foreach ($products as $product) {
            $ids[] = DB::table('products')->insertGetId(array_merge($product, [
                'user_id' => $user->id,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        return $ids;
    }

    private function seedInvoices(User $user, array $customerIds, array $productIds): void
    {
        if (DB::table('invoices')->where('user_id', $user->id)->exists()) {
            return;
        }

        $templateId = DB::table('invoice_templates')
            ->where('user_id', $user->id)
            ->where('is_default_invoice', true)
            ->value('id');

        $products = DB::table('products')->whereIn('id', $productIds)->get()->keyBy('id');

        // Factuur-definities: [klant-index, status, dagen-geleden, regels[product-index, qty], notitie]
        $invoiceDefinitions = [
            // Betaalde facturen (oudere)
            [0, 'paid',      90, [[0, 1], [5, 3]],           'Website project fase 1'],
            [1, 'paid',      75, [[1, 1], [9, 2]],           'Branding pakket'],
            [2, 'paid',      60, [[4, 16], [2, 1]],          'Consultancy januari + SEO'],
            [3, 'paid',      50, [[4, 8]],                    'Juridisch advies website'],
            [5, 'paid',      45, [[7, 2], [2, 1]],           'Social media Q1'],
            [7, 'paid',      40, [[8, 2], [11, 4]],          'Evenement fotografie'],

            // Verzonden (wachtend op betaling)
            [0, 'sent',      20, [[10, 1]],                   'Webshop ontwikkeling'],
            [4, 'sent',      18, [[0, 1], [3, 1]],           'Website + hosting Mulder Bouw'],
            [6, 'sent',      12, [[4, 24], [2, 3]],          'Consultancy Q2 + SEO'],
            [8, 'sent',       8, [[4, 12]],                   'Elektrotechnisch advies platform'],

            // Openstaand / te laat
            [2, 'overdue',   35, [[10, 1], [6, 1]],          'Webshop + email marketing TechFlow'],
            [5, 'overdue',   30, [[7, 3]],                    'Social media beheer Q2'],

            // Concepten
            [9, 'draft',      2, [[8, 1], [1, 1]],           'Fotoshoot + logo Willems'],
            [3, 'draft',      1, [[4, 8], [11, 6]],          'Offerte advieswerk juni'],
            [7, 'draft',      0, [[0, 1], [3, 1], [2, 6]],   'Totaalpakket Dijkstra Events'],

            // Geannuleerd
            [1, 'cancelled', 55, [[10, 1]],                   'GEANNULEERD - Webshop Bakker (klant afgehaakt)'],
        ];

        foreach ($invoiceDefinitions as $index => $def) {
            [$custIdx, $status, $daysAgo, $linesDef, $notes] = $def;

            $invoiceDate = Carbon::now()->subDays($daysAgo);
            $dueDate = $invoiceDate->copy()->addDays(14);
            $invoiceNumber = 'INV' . str_pad($index + 1, 5, '0', STR_PAD_LEFT);

            // Bereken totalen
            $subtotal = 0;
            $vatAmount = 0;
            $lines = [];

            foreach ($linesDef as $lineDef) {
                [$prodIdx, $qty] = $lineDef;
                $product = $products->get($productIds[$prodIdx]);
                $lineTotal = $product->price * $qty;
                $vatRate = ($prodIdx === 3) ? 0.00 : 21.00; // Hosting vrijgesteld (voorbeeld)
                $lineVat = $lineTotal * ($vatRate / 100);

                $subtotal += $lineTotal;
                $vatAmount += $lineVat;

                $lines[] = [
                    'description' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'vat_rate' => $vatRate,
                    'total' => $lineTotal,
                ];
            }

            $total = $subtotal + $vatAmount;

            $sentAt = in_array($status, ['sent', 'paid', 'overdue']) ? $invoiceDate->copy()->addDay() : null;
            $paidAt = $status === 'paid' ? $invoiceDate->copy()->addDays(rand(5, 13)) : null;

            $invoiceId = DB::table('invoices')->insertGetId([
                'user_id' => $user->id,
                'customer_id' => $customerIds[$custIdx],
                'template_id' => $templateId,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'payment_terms' => 14,
                'subtotal' => $subtotal,
                'vat_amount' => $vatAmount,
                'total' => $total,
                'status' => $status,
                'vat_reverse_charged' => false,
                'sent_at' => $sentAt?->toDateString(),
                'paid_at' => $paidAt?->toDateString(),
                'notes' => $notes,
                'created_at' => $invoiceDate,
                'updated_at' => now(),
            ]);

            foreach ($lines as $line) {
                DB::table('invoice_lines')->insert(array_merge($line, [
                    'invoice_id' => $invoiceId,
                    'created_at' => $invoiceDate,
                    'updated_at' => now(),
                ]));
            }
        }
    }

    private function seedQuotes(User $user, array $customerIds, array $productIds): void
    {
        if (DB::table('quotes')->where('user_id', $user->id)->exists()) {
            return;
        }

        $templateId = DB::table('invoice_templates')
            ->where('user_id', $user->id)
            ->where('is_default_quote', true)
            ->value('id');

        $products = DB::table('products')->whereIn('id', $productIds)->get()->keyBy('id');

        // Offerte-definities: [klant-index, status, dagen-geleden, regels[product-index, qty], notitie]
        $quoteDefinitions = [
            // Geaccepteerde offertes
            [0, 'accepted',   80, [[0, 1], [5, 6], [3, 1]],   'Website redesign + onderhoud + hosting'],
            [2, 'accepted',   55, [[10, 1], [6, 1]],           'Webshop + e-mailmarketing'],
            [5, 'accepted',   40, [[7, 6], [2, 3]],            'Social media jaarcontract + SEO'],

            // Verzonden (wachtend op antwoord)
            [4, 'sent',       15, [[0, 1], [1, 1], [3, 1]],   'Complete branding + website Mulder'],
            [6, 'sent',       10, [[4, 40], [2, 6]],           'Jaarcontract consultancy + SEO'],
            [8, 'sent',        7, [[10, 1], [5, 12]],          'Webshop + onderhoud Smit'],
            [9, 'sent',        5, [[8, 3], [1, 1], [11, 8]],   'Fotografie + branding Willems'],

            // Concepten
            [7, 'draft',       3, [[0, 1], [7, 3], [6, 1]],   'Evenementen platform Dijkstra'],
            [3, 'draft',       1, [[4, 20], [11, 10]],         'Advieswerk + ontwerp Q3'],
            [1, 'draft',       0, [[0, 1], [2, 6], [5, 12]],   'Website + SEO + onderhoud Bakker'],

            // Afgewezen
            [6, 'rejected',   50, [[10, 1]],                    'Webshop te duur bevonden'],
            [8, 'rejected',   45, [[0, 1], [1, 1]],            'Klant kiest andere partij'],

            // Verlopen
            [1, 'expired',    65, [[7, 3]],                     'Social media beheer (geen reactie)'],
            [4, 'expired',    60, [[4, 16]],                    'Consultancy voorstel (verlopen)'],
        ];

        foreach ($quoteDefinitions as $index => $def) {
            [$custIdx, $status, $daysAgo, $linesDef, $notes] = $def;

            $quoteDate = Carbon::now()->subDays($daysAgo);
            $validUntil = $quoteDate->copy()->addDays(30);
            $quoteNumber = 'OFF' . str_pad($index + 1, 5, '0', STR_PAD_LEFT);

            // Bereken totalen
            $subtotal = 0;
            $vatAmount = 0;
            $lines = [];

            foreach ($linesDef as $lineDef) {
                [$prodIdx, $qty] = $lineDef;
                $product = $products->get($productIds[$prodIdx]);
                $lineTotal = $product->price * $qty;
                $vatRate = ($prodIdx === 3) ? 0.00 : 21.00;
                $lineVat = $lineTotal * ($vatRate / 100);

                $subtotal += $lineTotal;
                $vatAmount += $lineVat;

                $lines[] = [
                    'description' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'vat_rate' => $vatRate,
                    'total' => $lineTotal,
                ];
            }

            $total = $subtotal + $vatAmount;

            $sentAt = in_array($status, ['sent', 'accepted', 'rejected', 'expired'])
                ? $quoteDate->copy()->addDay()
                : null;

            $quoteId = DB::table('quotes')->insertGetId([
                'user_id' => $user->id,
                'customer_id' => $customerIds[$custIdx],
                'template_id' => $templateId,
                'quote_number' => $quoteNumber,
                'quote_date' => $quoteDate->toDateString(),
                'valid_until' => $validUntil->toDateString(),
                'valid_days' => 30,
                'subtotal' => $subtotal,
                'vat_amount' => $vatAmount,
                'total' => $total,
                'status' => $status,
                'sent_at' => $sentAt?->toDateString(),
                'notes' => $notes,
                'converted_invoice_id' => null,
                'converted_at' => null,
                'created_at' => $quoteDate,
                'updated_at' => now(),
            ]);

            foreach ($lines as $line) {
                DB::table('quote_lines')->insert(array_merge($line, [
                    'quote_id' => $quoteId,
                    'created_at' => $quoteDate,
                    'updated_at' => now(),
                ]));
            }
        }
    }
}
