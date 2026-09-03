<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('business_name');
            // barber, salon, manicurist, etc - lets one platform serve all provider types
            $table->enum('provider_type', ['barber', 'salon', 'manicurist', 'other'])->default('barber');
            $table->text('bio')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('cover_image')->nullable();

            // open/closed toggle, feature #5
            $table->boolean('is_open')->default(true);
            $table->string('closed_reason')->nullable();

            // cached aggregate stats, updated on review/appointment events
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('completed_appointments_count')->default(0);

            $table->json('business_hours')->nullable(); // {"mon":["09:00","18:00"], ...}
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index('provider_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_profiles');
    }
};
