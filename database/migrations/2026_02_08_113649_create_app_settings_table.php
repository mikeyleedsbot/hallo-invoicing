<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('default_vat_rate', 5, 2)->default(21);
            $table->integer('default_payment_terms')->default(14);
            $table->integer('quote_valid_days')->default(30);
            $table->string('currency')->default('EUR');
            $table->string('currency_symbol')->default('€');
            $table->string('date_format')->default('d-m-Y');
            $table->string('invoice_prefix')->default('INV');
            $table->string('quote_prefix')->default('OFF');
            $table->integer('invoice_number_start')->default(1);
            $table->integer('quote_number_start')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
