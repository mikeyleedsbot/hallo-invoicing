<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: elke bestaande user wordt eigenaar van een eigen, nieuw team.
 * Alle data die tot nu toe via user_id gescoped was, wordt herparented naar
 * dat team. Geen handmatige admin-actie nodig — bestaande accounts blijven
 * na deze migratie exact dezelfde data zien als daarvoor.
 *
 * Draait in chunks (niet één query per tabel over de hele tabel) zodat dit
 * ook op grotere productiedatasets behapbaar blijft.
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
        DB::table('users')->orderBy('id')->chunkById(200, function ($users) {
            foreach ($users as $user) {
                DB::transaction(function () use ($user) {
                    $teamId = DB::table('teams')->insertGetId([
                        'name'       => $user->company_name ?: ($user->name),
                        'owner_id'   => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('team_user')->insert([
                        'team_id'    => $teamId,
                        'user_id'    => $user->id,
                        'role'       => 'owner',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('users')->where('id', $user->id)->update([
                        'current_team_id' => $teamId,
                    ]);

                    foreach ($this->tables as $table) {
                        DB::table($table)->where('user_id', $user->id)->update([
                            'team_id' => $teamId,
                        ]);
                    }
                });
            }
        });
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            DB::table($table)->update(['team_id' => null]);
        }

        DB::table('users')->update(['current_team_id' => null]);
        DB::table('team_user')->delete();
        DB::table('teams')->delete();
    }
};
