<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Een nieuw teamlid wordt aangevraagd, niet direct uitgenodigd: een admin
 * regelt eerst de facturatie in Salesforce en keurt dan goed. Pas daarna
 * gaat de uitnodigingsmail de deur uit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_invitations', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('invited_by');
        });

        // Bestaande uitnodigingen zijn al verstuurd, die tellen als goedgekeurd
        DB::table('team_invitations')->update(['approved_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('team_invitations', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
