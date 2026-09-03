<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A provider's "my clients" book. Populated automatically after a completed
        // appointment, or manually by the provider ("add upon request", feature #7).
        Schema::create('client_provider', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            $table->text('notes')->nullable(); // e.g. preferred style, allergy notes
            $table->enum('source', ['auto_after_booking', 'manual_add', 'client_request'])->default('manual_add');
            $table->timestamps();

            $table->unique(['client_id', 'provider_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_provider');
    }
};
