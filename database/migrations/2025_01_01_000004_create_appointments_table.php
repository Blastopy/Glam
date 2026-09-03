<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();

            $table->dateTime('scheduled_at'); // date + time of appointment
            $table->dateTime('checked_in_at')->nullable(); // when client marks "on my way / arrived"
            $table->decimal('price_at_booking', 10, 2); // snapshot in case service price changes later

            $table->enum('status', [
                'pending',    // awaiting provider confirmation
                'confirmed',
                'in_progress',
                'completed',
                'cancelled',
                'no_show',
            ])->default('pending');

            $table->text('client_notes')->nullable();
            $table->text('provider_notes')->nullable();
            $table->timestamps();

            $table->index(['provider_profile_id', 'scheduled_at']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
