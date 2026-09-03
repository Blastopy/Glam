<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('rating'); // 1-5, validated in FormRequest
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('provider_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
