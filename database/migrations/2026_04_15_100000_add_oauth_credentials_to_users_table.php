<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * OAuth-credentials per gebruiker, i.p.v. één globale set in config/services.php.
     * Op die manier kan elke klant z'n eigen Google Cloud Project / Azure App koppelen
     * aan z'n eigen Hallo Invoicing account.
     *
     * client_secret wordt via Laravel's 'encrypted' cast versleuteld opgeslagen,
     * zodat het niet in plaintext in de database staat.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Google Workspace / Gmail API
            $table->string('google_client_id')->nullable()->after('mfa_confirmed_at');
            $table->text('google_client_secret')->nullable()->after('google_client_id');

            // Microsoft 365 / Graph
            $table->string('microsoft_client_id')->nullable()->after('google_client_secret');
            $table->text('microsoft_client_secret')->nullable()->after('microsoft_client_id');
            // 'common' voor multi-tenant, of specifieke tenant-ID voor single-tenant apps
            $table->string('microsoft_tenant_id')->default('common')->after('microsoft_client_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_client_id',
                'google_client_secret',
                'microsoft_client_id',
                'microsoft_client_secret',
                'microsoft_tenant_id',
            ]);
        });
    }
};
