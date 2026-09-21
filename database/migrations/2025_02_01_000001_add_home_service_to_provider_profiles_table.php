<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            // Whether this provider will travel to a client's home/location at all.
            $table->boolean('offers_home_service')->default(false)->after('is_open');
            // Optional flat callout fee on top of the service price, e.g. for
            // transport/time. Null/0 means no extra charge.
            $table->decimal('home_service_fee', 10, 2)->nullable()->after('offers_home_service');
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropColumn(['offers_home_service', 'home_service_fee']);
        });
    }
};
