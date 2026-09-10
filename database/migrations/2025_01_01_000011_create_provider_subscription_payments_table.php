<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 10, 2)->default(70.00);
            $table->string('phone');

            // Safaricom Daraja identifiers — needed to reconcile the async callback
            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->nullable()->index();
            $table->string('mpesa_receipt_number')->nullable();

            $table->enum('status', ['pending', 'successful', 'failed', 'cancelled'])->default('pending');
            $table->unsignedInteger('result_code')->nullable();
            $table->string('result_desc')->nullable();
            $table->timestamp('paid_at')->nullable();

            // The 30-day window this specific payment covers, set once successful
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();

            $table->json('raw_callback')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_subscription_payments');
    }
};
