<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'payment_terms')) {
                $table->integer('payment_terms')->default(14)->after('due_date');
            }
        });
        
        // Handle vat_amount separately to avoid rename conflicts
        if (Schema::hasColumn('invoices', 'tax') && !Schema::hasColumn('invoices', 'vat_amount')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->renameColumn('tax', 'vat_amount');
            });
        } elseif (!Schema::hasColumn('invoices', 'vat_amount') && !Schema::hasColumn('invoices', 'tax')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('vat_amount', 10, 2)->default(0)->after('subtotal');
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_terms', 'vat_amount']);
        });
    }
};
