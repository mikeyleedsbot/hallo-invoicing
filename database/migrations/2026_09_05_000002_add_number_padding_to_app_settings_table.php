<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Breedte van de factuur- en offerteteller instelbaar maken.
 *
 * Het nummer werd altijd met vijf cijfers opgebouwd (prefix 2026 + teller 6
 * gaf 202600006). Hoeveel cijfers de teller krijgt, wordt nu bepaald door
 * hoe je hem in de instellingen invult: "0006" geeft 20260006, "6" geeft
 * 20266.
 *
 * De tellers zelf blijven een int, daarin passen geen voorloopnullen; die
 * breedte staat daarom apart.
 *
 * Standaard 5, zodat de nummering van bestaande accounts niet verandert
 * zolang zij hun teller niet aanpassen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('app_settings', 'invoice_number_padding')) {
                $table->unsignedTinyInteger('invoice_number_padding')->default(5)->after('invoice_number_start');
            }
            if (! Schema::hasColumn('app_settings', 'quote_number_padding')) {
                $table->unsignedTinyInteger('quote_number_padding')->default(5)->after('quote_number_start');
            }
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $drop = array_values(array_filter(
                ['invoice_number_padding', 'quote_number_padding'],
                fn ($c) => Schema::hasColumn('app_settings', $c)
            ));

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
