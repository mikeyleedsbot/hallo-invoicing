<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rondt de team_id-migratie af: team_id verplicht maken, en de
 * factuur-/offertenummer-uniciteit verplaatsen van per-user naar per-team
 * (meerdere teamleden delen nu dezelfde nummerreeks).
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
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'team_id')) {
                // SQLite ondersteunt geen ALTER COLUMN, maar MySQL/PostgreSQL wel
                if (DB::getDriverName() !== 'sqlite') {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `team_id` BIGINT UNSIGNED NOT NULL");
                }
            }
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'invoice_number']);
            $table->unique(['team_id', 'invoice_number']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'quote_number']);
            $table->unique(['team_id', 'quote_number']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'invoice_number']);
            $table->unique(['user_id', 'invoice_number']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'quote_number']);
            $table->unique(['user_id', 'quote_number']);
        });

        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'team_id')) {
                if (DB::getDriverName() !== 'sqlite') {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `team_id` BIGINT UNSIGNED NULL");
                }
            }
        }
    }
};
