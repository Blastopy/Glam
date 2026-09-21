<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Optional — captured via the browser's geolocation API when the
            // client checks "book as a home visit". Falls back gracefully to
            // just the typed home_address if they decline location permission.
            $table->decimal('home_latitude', 10, 7)->nullable()->after('home_address');
            $table->decimal('home_longitude', 10, 7)->nullable()->after('home_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['home_latitude', 'home_longitude']);
        });
    }
};
