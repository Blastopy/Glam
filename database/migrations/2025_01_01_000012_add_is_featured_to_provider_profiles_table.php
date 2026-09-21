<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            // Admin-controlled — a featured provider is advertised ahead of
            // others in discovery (see ProviderProfile::scopeFeaturedFirst).
            $table->boolean('is_featured')->default(false)->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
};
