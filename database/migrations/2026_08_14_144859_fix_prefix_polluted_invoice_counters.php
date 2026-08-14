<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Bug: AppSetting::advanceCounter() haalde het volgende nummer op met
     * een regex die alle cijfers aan het eind van het opgeslagen nummer
     * pakte (bv. "2026034" uit prefix "2026" + nummer "034"), zonder
     * rekening te houden met een numerieke prefix. Bij prefixen die zelf
     * op cijfers eindigen (jaartallen zoals "2026") kwamen die cijfers zo
     * in de teller terecht, die daardoor exponentieel opliep en bij het
     * volgende nummer dubbel voor de prefix kwam te staan.
     *
     * Deze migratie herstelt de teller door 'm te herberekenen op basis
     * van het laatst uitgegeven factuur-/offertenummer, met de prefix
     * eraf geknipt — dezelfde logica als de gefixte advanceCounter().
     */
    public function up(): void
    {
        $this->fixCounter('invoice_number_start', 'invoice_prefix', 'invoices', 'invoice_number');
        $this->fixCounter('quote_number_start', 'quote_prefix', 'quotes', 'quote_number');
    }

    private function fixCounter(string $counterColumn, string $prefixColumn, string $table, string $numberColumn): void
    {
        $settings = DB::table('app_settings')->get(['id', 'user_id', $prefixColumn, $counterColumn]);

        foreach ($settings as $setting) {
            $prefix = (string) $setting->{$prefixColumn};
            $counter = (int) $setting->{$counterColumn};

            if ($prefix === '' || $counter === 0) {
                continue;
            }

            // Alleen tellers die overduidelijk vervuild zijn: de prefix staat
            // er (als cijferreeks) letterlijk voorop.
            if (!str_starts_with((string) $counter, $prefix) || !ctype_digit($prefix)) {
                continue;
            }

            $candidates = DB::table($table)
                ->where('user_id', $setting->user_id)
                ->where($numberColumn, 'like', $prefix . '%')
                ->pluck($numberColumn);

            // Niet zomaar de laatst aangemaakte of lexicografisch hoogste
            // nemen: sommige nummers hebben een non-numerieke suffix
            // (creditnota-correcties zoals "2023194-2"). Alleen nummers
            // met een puur numerieke staart tellen mee voor de teller.
            $best = null;
            foreach ($candidates as $candidate) {
                $tail = substr($candidate, strlen($prefix));

                if (!ctype_digit($tail)) {
                    continue;
                }

                $best = $best === null ? (int) $tail : max($best, (int) $tail);
            }

            if ($best === null) {
                continue;
            }

            $correct = $best + 1;

            DB::table('app_settings')
                ->where('id', $setting->id)
                ->update([$counterColumn => $correct]);
        }
    }

    /**
     * Niet automatisch terug te draaien: de oude (vervuilde) waarde is
     * bewust corrupt en hoeft niet hersteld te worden.
     */
    public function down(): void
    {
        //
    }
};
