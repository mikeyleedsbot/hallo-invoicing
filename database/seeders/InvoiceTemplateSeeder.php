<?php

namespace Database\Seeders;

use App\Models\User;
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

        // Standaard template
        \DB::table('invoice_templates')->insert([
            'user_id' => $user->id,
            'name' => 'Standaard Template',
            'is_default' => true,
            'logo_path' => null,
            'background_path' => null,
            'page_size' => 'A4',
            'field_positions' => json_encode([
                'company_name' => ['x' => 50, 'y' => 50, 'width' => 300, 'height' => 40, 'fontSize' => 18, 'bold' => true],
                'company_address' => ['x' => 50, 'y' => 100, 'width' => 300, 'height' => 60, 'fontSize' => 11],
                'company_email' => ['x' => 50, 'y' => 170, 'width' => 300, 'height' => 20, 'fontSize' => 10],
                'company_phone' => ['x' => 50, 'y' => 195, 'width' => 300, 'height' => 20, 'fontSize' => 10],
                'logo' => ['x' => 600, 'y' => 50, 'width' => 150, 'height' => 80],
                'invoice_number' => ['x' => 550, 'y' => 150, 'width' => 200, 'height' => 25, 'fontSize' => 12],
                'invoice_date' => ['x' => 550, 'y' => 180, 'width' => 200, 'height' => 25, 'fontSize' => 12],
                'due_date' => ['x' => 550, 'y' => 210, 'width' => 200, 'height' => 25, 'fontSize' => 12],
                'client_name' => ['x' => 50, 'y' => 250, 'width' => 300, 'height' => 30, 'fontSize' => 14, 'bold' => true],
                'client_address' => ['x' => 50, 'y' => 290, 'width' => 300, 'height' => 60, 'fontSize' => 11],
                'client_email' => ['x' => 50, 'y' => 360, 'width' => 300, 'height' => 20, 'fontSize' => 10],
                'items_table' => ['x' => 50, 'y' => 420, 'width' => 700, 'height' => 300],
                'subtotal' => ['x' => 550, 'y' => 750, 'width' => 200, 'height' => 25, 'fontSize' => 12],
                'tax' => ['x' => 550, 'y' => 780, 'width' => 200, 'height' => 25, 'fontSize' => 12],
                'total' => ['x' => 550, 'y' => 810, 'width' => 200, 'height' => 30, 'fontSize' => 16, 'bold' => true],
                'payment_terms' => ['x' => 50, 'y' => 900, 'width' => 700, 'height' => 80, 'fontSize' => 10],
                'thank_you' => ['x' => 50, 'y' => 1000, 'width' => 700, 'height' => 40, 'fontSize' => 11, 'align' => 'center'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Modern template
        \DB::table('invoice_templates')->insert([
            'user_id' => $user->id,
            'name' => 'Modern Template',
            'is_default' => false,
            'logo_path' => null,
            'background_path' => null,
            'page_size' => 'A4',
            'field_positions' => json_encode([
                'logo' => ['x' => 320, 'y' => 40, 'width' => 150, 'height' => 80],
                'company_name' => ['x' => 200, 'y' => 140, 'width' => 400, 'height' => 40, 'fontSize' => 20, 'bold' => true, 'align' => 'center'],
                'company_address' => ['x' => 200, 'y' => 190, 'width' => 400, 'height' => 40, 'fontSize' => 10, 'align' => 'center'],
                'company_email' => ['x' => 200, 'y' => 235, 'width' => 400, 'height' => 20, 'fontSize' => 10, 'align' => 'center'],
                'invoice_number' => ['x' => 50, 'y' => 300, 'width' => 200, 'height' => 25, 'fontSize' => 12],
                'invoice_date' => ['x' => 50, 'y' => 330, 'width' => 200, 'height' => 25, 'fontSize' => 12],
                'client_name' => ['x' => 500, 'y' => 300, 'width' => 250, 'height' => 30, 'fontSize' => 14, 'bold' => true],
                'client_address' => ['x' => 500, 'y' => 340, 'width' => 250, 'height' => 60, 'fontSize' => 11],
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
