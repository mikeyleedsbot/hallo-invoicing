<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Betalingen die niet uit de bank komen.
 *
 * Als de bank maar een deel van een factuur dekt, moet het restant contant
 * afgerond kunnen worden. Zo'n betaling hoort geen banktransactie te hebben,
 * maar telt wel mee voor het openstaande bedrag en de status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_payments', 'method')) {
                $table->string('method', 16)->default('bank')->after('amount'); // bank | cash
            }
        });

        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->foreignId('bank_transaction_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Betalingen zonder banktransactie kunnen niet blijven bestaan als de
        // kolom weer verplicht wordt
        \Illuminate\Support\Facades\DB::table('invoice_payments')
            ->whereNull('bank_transaction_id')
            ->delete();

        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->foreignId('bank_transaction_id')->nullable(false)->change();
        });

        Schema::table('invoice_payments', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_payments', 'method')) {
                $table->dropColumn('method');
            }
        });
    }
};
