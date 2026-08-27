<?php

namespace App\Observers;

use App\Models\Team;
use App\Services\InvoicePdfGenerator;
use Illuminate\Support\Facades\DB;

class TeamObserver
{
    /**
     * Na aanmaken van een nieuw team: seed standaard BTW-tarieven en template.
     * Alle teamleden delen deze data — dus dit gebeurt per team, niet per user.
     */
    public function created(Team $team): void
    {
        $this->seedDefaultVatRates($team);
        $this->seedDefaultTemplates($team);
    }

    private function seedDefaultVatRates(Team $team): void
    {
        $rates = [
            ['name' => 'Hoog tarief',  'rate' => 21.00, 'is_default' => true,  'sort_order' => 1],
            ['name' => 'Laag tarief',  'rate' =>  9.00, 'is_default' => false, 'sort_order' => 2],
            ['name' => 'Vrijgesteld',  'rate' =>  0.00, 'is_default' => false, 'sort_order' => 3],
        ];

        foreach ($rates as $rate) {
            DB::table('vat_rates')->insert(array_merge($rate, [
                'team_id'    => $team->id,
                'user_id'    => $team->owner_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    private function seedDefaultTemplates(Team $team): void
    {
        $defaultPositions = InvoicePdfGenerator::getDefaultPositions();

        DB::table('invoice_templates')->insert([
            'team_id'             => $team->id,
            'user_id'             => $team->owner_id,
            'name'                => 'Standaard Template',
            'is_default_invoice'  => true,
            'is_default_quote'    => true,
            'logo_path'           => null,
            'background_path'     => null,
            'page_size'           => 'A4',
            'field_positions'     => json_encode($defaultPositions),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }
}
