<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 'google' of 'microsoft'
            $table->string('provider', 20);

            // Het e-mailadres dat via OAuth is verbonden (bijv. jan@bedrijf.nl).
            $table->string('from_email');
            $table->string('from_name')->nullable();

            // Versleuteld opgeslagen OAuth tokens (via Laravel's encrypted cast).
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            // Heeft de gebruiker deze als actieve verzender aangewezen?
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->unique(['user_id', 'from_email']);
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_accounts');
    }
};
