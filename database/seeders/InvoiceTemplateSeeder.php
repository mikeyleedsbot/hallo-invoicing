<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\InvoicePdfGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvoiceTemplateSeeder extends Seeder
{
    /**
     * Seed default templates + BTW-tarieven voor alle users die ze nog niet hebben.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $this->seedTemplatesForUser($user);
            $this->seedVatRatesForUser($user);
        }
    }

    private function seedTemplatesForUser(User $user): void
    {
        // Skip als user al templates heeft
        if (\DB::table('invoice_templates')->where('user_id', $user->id)->exists()) {
            return;
        }

        // Standaard template — posities uit InvoicePdfGenerator zodat het altijd in sync is
        \DB::table('invoice_templates')->insert([
            'user_id' => $user->id,
            'name' => 'Standaard Template',
            'is_default_invoice' => true,
            'is_default_quote' => true,
            'logo_path' => null,
            'background_path' => null,
            'page_size' => 'A4',
            'field_positions' => json_encode(InvoicePdfGenerator::getDefaultPositions()),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Modern template
        \DB::table('invoice_templates')->insert([
            'user_id' => $user->id,
            'name' => 'Modern Template',
            'is_default_invoice' => false,
            'is_default_quote' => false,
            'logo_path' => null,
            'background_path' => null,
            'page_size' => 'A4',
            'field_positions' => json_encode([
                'logo' => ['x' => 320, 'y' => 40, 'width' => 150, 'height' => 80],
                'company_name' => ['x' => 200, 'y' => 140, 'width' => 400, 'height' => 40, 'fontSize' => 20, 'bold' => true, 'align' => 'center'],
                'company_address' => ['x' => 200, 'y' => 190, 'width' => 400, 'height' => 25, 'fontSize' => 10, 'align' => 'center'],
                'company_postal_code' => ['x' => 200, 'y' => 218, 'width' => 100, 'height' => 25, 'fontSize' => 10, 'align' => 'right'],
                'company_city' => ['x' => 305, 'y' => 218, 'width' => 295, 'height' => 25, 'fontSize' => 10, 'align' => 'left'],
                'company_email' => ['x' => 200, 'y' => 248, 'width' => 400, 'height' => 20, 'fontSize' => 10, 'align' => 'center'],
                'invoice_number' => ['x' => 50, 'y' => 310, 'width' => 200, 'height' => 25, 'fontSize' => 12],
                'invoice_date' => ['x' => 50, 'y' => 340, 'width' => 200, 'height' => 25, 'fontSize' => 12],
                'client_name' => ['x' => 500, 'y' => 310, 'width' => 250, 'height' => 30, 'fontSize' => 14, 'bold' => true],
                'client_address' => ['x' => 500, 'y' => 350, 'width' => 250, 'height' => 25, 'fontSize' => 11],
                'client_postal_code' => ['x' => 500, 'y' => 378, 'width' => 80, 'height' => 25, 'fontSize' => 11],
                'client_city' => ['x' => 585, 'y' => 378, 'width' => 165, 'height' => 25, 'fontSize' => 11],
                'items_table' => ['x' => 50, 'y' => 450, 'width' => 700, 'height' => 300],
                'subtotal' => ['x' => 550, 'y' => 780, 'width' => 200, 'height' => 25, 'fontSize' => 12],
                'tax' => ['x' => 550, 'y' => 810, 'width' => 200, 'height' => 25, 'fontSize' => 12],
                'total' => ['x' => 550, 'y' => 840, 'width' => 200, 'height' => 30, 'fontSize' => 16, 'bold' => true],
                'payment_terms' => ['x' => 50, 'y' => 920, 'width' => 700, 'height' => 80, 'fontSize' => 9],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedVatRatesForUser(User $user): void
    {
        // Skip als user al BTW-tarieven heeft
        if (\DB::table('vat_rates')->where('user_id', $user->id)->exists()) {
            return;
        }

        $rates = [
            ['name' => 'Hoog tarief',   'rate' => 21.00, 'is_default' => true,  'sort_order' => 1],
            ['name' => 'Laag tarief',   'rate' =>  9.00, 'is_default' => false, 'sort_order' => 2],
            ['name' => 'Vrijgesteld',   'rate' =>  0.00, 'is_default' => false, 'sort_order' => 3],
        ];

        foreach ($rates as $rate) {
            \DB::table('vat_rates')->insert(array_merge($rate, [
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
