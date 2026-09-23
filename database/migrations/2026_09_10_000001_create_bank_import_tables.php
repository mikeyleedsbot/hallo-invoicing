<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bankafschriften importeren en koppelen aan verkoopfacturen.
 *
 * Drie tabellen:
 * - bank_import_sessions : één upload, blijft "open" tot het matchen klaar is
 * - bank_transactions    : de regels uit het bestand. Niet-gekoppelde regels
 *                          worden bij het afronden weggegooid; alleen wat aan
 *                          een factuur hangt blijft bewaard
 * - invoice_payments     : de koppeling zelf, met bedrag. Een transactie kan
 *                          over meerdere facturen verdeeld worden en een
 *                          factuur kan in delen betaald zijn
 *
 * Alles hangt aan user_id: bankgegevens van het ene bedrijf mogen nooit
 * zichtbaar zijn voor een ander.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_import_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('format', 32);
            $table->string('account_iban', 34)->nullable();
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->string('status', 16)->default('open');   // open | completed
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_import_session_id')->constrained()->cascadeOnDelete();
            $table->date('booking_date');
            $table->date('value_date')->nullable();
            $table->decimal('amount', 12, 2);                // negatief = af, positief = bij
            $table->string('currency', 3)->default('EUR');
            $table->string('counterparty_name')->nullable();
            $table->string('counterparty_iban', 34)->nullable();
            $table->text('description')->nullable();
            $table->string('bank_reference')->nullable();
            $table->string('fingerprint', 64);               // dubbele import herkennen
            $table->timestamps();

            $table->index(['user_id', 'booking_date']);
            $table->unique(['user_id', 'fingerprint']);
        });

        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_transaction_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('matched_by', 16)->default('manual'); // auto | manual
            $table->timestamps();

            $table->index(['user_id', 'invoice_id']);
            $table->index(['user_id', 'bank_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_import_sessions');
    }
};
