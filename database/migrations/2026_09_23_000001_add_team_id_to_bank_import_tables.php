<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * De bankkoppeling is op main gebouwd met per-user scoping (BelongsToUser).
 * Na de teams-merge hangt alle data aan team_id; deze migratie trekt de
 * banktabellen gelijk. user_id blijft staan als auteurschap.
 */
return new class extends Migration
{
    private array $tables = ['bank_import_sessions', 'bank_transactions', 'invoice_payments'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('team_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            });
        }

        // Betalingen volgen hun factuur; de rest het huidige team van de gebruiker
        DB::table('invoice_payments')->update([
            'team_id' => DB::raw('(select team_id from invoices where invoices.id = invoice_payments.invoice_id)'),
        ]);
        foreach (['bank_import_sessions', 'bank_transactions'] as $table) {
            DB::table($table)->update([
                'team_id' => DB::raw("(select current_team_id from users where users.id = {$table}.user_id)"),
            ]);
        }

        if (DB::getDriverName() !== 'sqlite') {
            foreach ($this->tables as $table) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `team_id` BIGINT UNSIGNED NOT NULL");
            }
        }

        // Dubbele-import-detectie geldt nu per team
        Schema::table('bank_transactions', function (Blueprint $t) {
            $t->unique(['team_id', 'fingerprint']);
            $t->dropUnique(['user_id', 'fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $t) {
            $t->unique(['user_id', 'fingerprint']);
            $t->dropUnique(['team_id', 'fingerprint']);
        });

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('team_id');
            });
        }
    }
};
