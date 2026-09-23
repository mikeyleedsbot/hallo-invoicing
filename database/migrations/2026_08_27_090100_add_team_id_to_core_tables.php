<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voeg team_id toe aan alle core-tabellen, náást het bestaande user_id.
 * user_id blijft staan als "wie heeft dit aangemaakt" (auteurschap);
 * team_id wordt de nieuwe eigenaarschaps-/zichtbaarheidssleutel.
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
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'team_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('team_id')->nullable()->after('user_id')
                      ->constrained()->cascadeOnDelete();
                    $t->index('team_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'team_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['team_id']);
                    $t->dropColumn('team_id');
                });
            }
        }
    }
};
