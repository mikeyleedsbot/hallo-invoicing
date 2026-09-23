<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vastleggen hoe de status van een factuur tot stand kwam: door iemand zelf
 * gezet, of afgeleid uit een gekoppelde banktransactie.
 *
 * Zonder dit zou een bankkoppeling een handmatige keuze stil kunnen
 * overschrijven, en zie je achteraf niet meer waar de status vandaan komt.
 * Leeg betekent: nooit expliciet gezet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'status_source')) {
                $table->string('status_source', 10)->nullable()->after('status');
            }
            if (! Schema::hasColumn('invoices', 'status_changed_at')) {
                $table->timestamp('status_changed_at')->nullable()->after('status_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $drop = array_values(array_filter(
                ['status_source', 'status_changed_at'],
                fn ($c) => Schema::hasColumn('invoices', $c)
            ));

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
