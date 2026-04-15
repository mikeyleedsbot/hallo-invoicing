<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Status van een account in het approval-proces.
            // - pending  : nieuw aangemaakt via publieke registratie, wacht op admin
            // - approved : door admin goedgekeurd, kan inloggen
            // - rejected : door admin geweigerd
            $table->string('status', 20)->default('pending')->after('is_admin');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->string('rejection_reason', 500)->nullable()->after('approved_by');
        });

        // Alle bestaande gebruikers krijgen status 'approved' zodat niemand wordt uitgesloten.
        DB::table('users')->whereNull('approved_at')->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status', 'approved_at', 'approved_by', 'rejection_reason']);
        });
    }
};
