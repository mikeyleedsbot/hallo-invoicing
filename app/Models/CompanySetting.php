<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTeam;

class CompanySetting extends Model
{
    use BelongsToTeam;

    protected $fillable = [
        'user_id',
        'team_id',
        'company_name',
        'address',
        'postal_code',
        'city',
        'country',
        'phone',
        'email',
        'website',
        'kvk_number',
        'vat_number',
        'iban',
        'bic',
        'bank_name',
        'invoice_footer',
        'logo_path',
    ];

    // Per-team singleton: elk team heeft eigen bedrijfsgegevens
    public static function get()
    {
        $team = auth()->check() ? auth()->user()->currentTeam : null;

        $defaults = [
            'company_name' => 'Mijn Bedrijf',
            'address' => '',
            'postal_code' => '',
            'city' => '',
            'country' => 'Nederland',
            'email' => '',
            'vat_number' => '',
            'iban' => '',
        ];

        if (!$team) {
            // CLI / seeder context: pak het eerste record
            return static::withoutGlobalScope('belongs_to_team')->firstOrCreate(
                ['id' => 1],
                $defaults
            );
        }

        // Per-team singleton: vul defaults met bekende teamdata
        $defaults['company_name'] = $team->name ?? 'Mijn Bedrijf';
        $defaults['email'] = auth()->user()->email ?? '';

        return static::firstOrCreate(
            ['team_id' => $team->id],
            $defaults
        );
    }
}
