<?php

namespace App\Observers;

use App\Models\User;
use App\Models\VatRate;
use App\Models\InvoiceTemplate;
use App\Services\InvoicePdfGenerator;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    /**
     * Na aanmaken van een nieuwe user: seed standaard BTW-tarieven en templates.
     */
    public function created(User $user): void
    {
        $this->seedDefaultVatRates($user);
        $this->seedDefaultTemplates($user);
    }

    private function seedDefaultVatRates(User $user): void
    {
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

    private function seedDefaultTemplates(User $user): void
    {
        $defaultPositions = InvoicePdfGenerator::getDefaultPositions();

        DB::table('invoice_templates')->insert([
            'user_id' => $user->id,
            'name' => 'Standaard Template',
            'is_default_invoice' => true,
            'is_default_quote' => true,
            'logo_path' => null,
            'background_path' => null,
            'page_size' => 'A4',
            'field_positions' => json_encode($defaultPositions),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
