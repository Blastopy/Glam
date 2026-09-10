<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            // Null = never activated. A provider is "live" (bookable, shown to
            // clients) only while this is in the future — see
            // ProviderProfile::isSubscriptionActive().
            $table->timestamp('subscription_expires_at')->nullable()->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropColumn('subscription_expires_at');
        });
    }
};
