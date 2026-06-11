<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Voeg user_id toe aan alle core-tabellen voor data-isolatie.
 * Elke gebruiker ziet alleen zijn eigen klanten, facturen, offertes, etc.
 */
return new class extends Migration
{
    private array $tables = [
        'customers',
        'invoices',
        'quotes',
        'products',
        'invoice_templates',
        'vat_rates',
        'company_settings',
        'app_settings',
    ];

    public function up(): void
    {
        // Stap 1: voeg nullable user_id kolom toe aan alle tabellen
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('user_id')->nullable()->after('id')
                      ->constrained()->cascadeOnDelete();
                    $t->index('user_id');
                });
            }
        }

        // Stap 2: backfill bestaande records → toewijzen aan eerste user
        $firstUserId = DB::table('users')->orderBy('id')->value('id');

        if ($firstUserId) {
            foreach ($this->tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->whereNull('user_id')->update(['user_id' => $firstUserId]);
                }
            }
        } else {
            // Geen users beschikbaar (verse installatie) — verwijder orphan rows
            foreach ($this->tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->whereNull('user_id')->delete();
                }
            }
        }

        // Stap 3: maak user_id NOT NULL (data-integriteit)
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                // SQLite ondersteunt geen ALTER COLUMN, maar MySQL/PostgreSQL wel
                if (DB::getDriverName() !== 'sqlite') {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `user_id` BIGINT UNSIGNED NOT NULL");
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['user_id']);
                    $t->dropColumn('user_id');
                });
            }
        }
    }
};
