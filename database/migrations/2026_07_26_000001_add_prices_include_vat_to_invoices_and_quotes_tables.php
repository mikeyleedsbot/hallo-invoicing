<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoerwijze van bedragen per factuur/offerte vastleggen.
 *
 * false (standaard) = unit_price is exclusief BTW — het bestaande gedrag,
 * dus alle bestaande records houden exact dezelfde betekenis.
 * true = unit_price is inclusief BTW; de BTW wordt er bij de berekening
 * uitgehaald in plaats van erbij opgeteld.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'prices_include_vat')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->boolean('prices_include_vat')->default(false)->after('vat_reverse_charged');
            });
        }

        if (! Schema::hasColumn('quotes', 'prices_include_vat')) {
            Schema::table('quotes', function (Blueprint $table) {
                $table->boolean('prices_include_vat')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'prices_include_vat')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('prices_include_vat');
            });
        }

        if (Schema::hasColumn('quotes', 'prices_include_vat')) {
            Schema::table('quotes', function (Blueprint $table) {
                $table->dropColumn('prices_include_vat');
            });
        }
    }
};
