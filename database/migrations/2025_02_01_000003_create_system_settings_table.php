<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            // Off by default — a fresh install with no M-Pesa credentials
            // configured yet shouldn't lock every provider out of the platform.
            // Admin turns this on once Daraja is actually set up (SETUP.md §6).
            $table->boolean('payments_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
