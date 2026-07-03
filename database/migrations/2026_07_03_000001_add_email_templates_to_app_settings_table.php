<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            // Opmaakbare e-mailteksten voor factuur- en offertemails.
            // NULL = gebruik de standaardtekst (zie AppSetting-model).
            $table->string('invoice_email_subject')->nullable()->after('quote_number_start');
            $table->text('invoice_email_body')->nullable()->after('invoice_email_subject');
            $table->string('quote_email_subject')->nullable()->after('invoice_email_body');
            $table->text('quote_email_body')->nullable()->after('quote_email_subject');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_email_subject',
                'invoice_email_body',
                'quote_email_subject',
                'quote_email_body',
            ]);
        });
    }
};
