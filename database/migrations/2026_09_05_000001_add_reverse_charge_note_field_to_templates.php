<?php

use App\Services\TemplatePresets;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * De verplichte "BTW verlegd"-vermelding stond op een vaste plek in de
 * PDF-generator en was niet te verplaatsen. Hij is nu een gewoon veld
 * (reverse_charge_note) in de template.
 *
 * Bestaande templates kennen dat veld nog niet. Deze migratie zet het er
 * alvast in, op precies de plek waar de vaste vermelding stond, zodat de
 * lay-out onveranderd blijft en het veld meteen in de editor staat.
 *
 * Op de factuur verschijnt het veld alleen als BTW verlegd aanstaat; dat
 * regelt de generator.
 */
return new class extends Migration
{
    private const FIELD = 'reverse_charge_note';

    public function up(): void
    {
        $default = TemplatePresets::positions('klassiek')[self::FIELD] ?? null;

        if (! $default) {
            return;
        }

        foreach (DB::table('invoice_templates')->select('id', 'field_positions')->cursor() as $template) {
            $positions = json_decode((string) $template->field_positions, true);

            // Lege templates vallen zelf al terug op de preset
            if (! is_array($positions) || $positions === [] || array_key_exists(self::FIELD, $positions)) {
                continue;
            }

            $positions[self::FIELD] = $default;

            DB::table('invoice_templates')
                ->where('id', $template->id)
                ->update(['field_positions' => json_encode($positions)]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('invoice_templates')->select('id', 'field_positions')->cursor() as $template) {
            $positions = json_decode((string) $template->field_positions, true);

            if (! is_array($positions) || ! array_key_exists(self::FIELD, $positions)) {
                continue;
            }

            unset($positions[self::FIELD]);

            DB::table('invoice_templates')
                ->where('id', $template->id)
                ->update(['field_positions' => json_encode($positions)]);
        }
    }
};
