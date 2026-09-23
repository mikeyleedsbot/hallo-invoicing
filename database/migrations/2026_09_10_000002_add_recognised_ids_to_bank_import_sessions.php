<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bij een import met een overlappende periode worden regels die al eerder
 * geïmporteerd én gekoppeld zijn overgeslagen. Hier onthouden we welke dat
 * waren, zodat het matchscherm ze kan tonen als "al gekoppeld" in plaats van
 * alleen een aantal te noemen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_import_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_import_sessions', 'recognised_transaction_ids')) {
                $table->json('recognised_transaction_ids')->nullable()->after('skipped_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_import_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('bank_import_sessions', 'recognised_transaction_ids')) {
                $table->dropColumn('recognised_transaction_ids');
            }
        });
    }
};
