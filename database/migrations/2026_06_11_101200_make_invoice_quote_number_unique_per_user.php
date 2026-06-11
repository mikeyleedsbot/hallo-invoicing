<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unique constraint op invoice_number en quote_number wijzigen
     * van globaal uniek naar uniek per user (composite met user_id).
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->unique(['user_id', 'invoice_number']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique(['quote_number']);
            $table->unique(['user_id', 'quote_number']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'invoice_number']);
            $table->unique('invoice_number');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'quote_number']);
            $table->unique('quote_number');
        });
    }
};
