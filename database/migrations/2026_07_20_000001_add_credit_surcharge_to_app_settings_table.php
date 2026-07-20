<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kredietbeperkingstoeslag: percentage van het factuurtotaal (incl. btw)
 * dat vervalt bij tijdige betaling. Conform Belastingdienst: toeslag wordt
 * berekend over het totaal incl. btw, zonder btw over de toeslag zelf.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('credit_surcharge_enabled')->default(false)->after('quote_number_start');
            $table->unsignedTinyInteger('credit_surcharge_percent')->default(2)->after('credit_surcharge_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['credit_surcharge_enabled', 'credit_surcharge_percent']);
        });
    }
};
