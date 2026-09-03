<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('price_at_booking');
            $table->boolean('is_deposit_paid')->default(false)->after('deposit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['deposit_amount', 'is_deposit_paid']);
        });
    }
};
