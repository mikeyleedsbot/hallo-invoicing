<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aparte standaardtemplate voor facturen en voor offertes.
 *
 * De losse `is_default` maakte geen onderscheid tussen beide soorten.
 * Bestaande standaardtemplates worden standaard voor zowel facturen als
 * offertes, zodat er functioneel niets verandert bij het uitrollen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_templates', 'is_default_invoice')) {
                $table->boolean('is_default_invoice')->default(false)->after('name');
            }
            if (! Schema::hasColumn('invoice_templates', 'is_default_quote')) {
                $table->boolean('is_default_quote')->default(false)->after('is_default_invoice');
            }
        });

        if (Schema::hasColumn('invoice_templates', 'is_default')) {
            // Huidige standaard wordt standaard voor beide soorten
            DB::table('invoice_templates')
                ->where('is_default', true)
                ->update([
                    'is_default_invoice' => true,
                    'is_default_quote'   => true,
                ]);

            Schema::table('invoice_templates', function (Blueprint $table) {
                // Index eerst weghalen: SQLite weigert een kolom te droppen
                // zolang er nog een index op staat (MySQL ruimt die zelf op).
                if (Schema::hasIndex('invoice_templates', 'invoice_templates_is_default_index')) {
                    $table->dropIndex(['is_default']);
                }
                $table->dropColumn('is_default');
            });
        }

        Schema::table('invoice_templates', function (Blueprint $table) {
            if (! Schema::hasIndex('invoice_templates', 'invoice_templates_is_default_invoice_index')) {
                $table->index('is_default_invoice');
            }
            if (! Schema::hasIndex('invoice_templates', 'invoice_templates_is_default_quote_index')) {
                $table->index('is_default_quote');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('invoice_templates', 'is_default')) {
            Schema::table('invoice_templates', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('name');
                $table->index('is_default');
            });
        }

        // De factuurstandaard wordt weer de enige standaard
        DB::table('invoice_templates')
            ->where('is_default_invoice', true)
            ->update(['is_default' => true]);

        Schema::table('invoice_templates', function (Blueprint $table) {
            if (Schema::hasIndex('invoice_templates', 'invoice_templates_is_default_invoice_index')) {
                $table->dropIndex(['is_default_invoice']);
            }
            if (Schema::hasIndex('invoice_templates', 'invoice_templates_is_default_quote_index')) {
                $table->dropIndex(['is_default_quote']);
            }
            $table->dropColumn(['is_default_invoice', 'is_default_quote']);
        });
    }
};
