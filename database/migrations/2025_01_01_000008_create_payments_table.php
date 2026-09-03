<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();

            $table->enum('type', ['deposit', 'balance', 'refund'])->default('deposit');
            $table->decimal('amount', 10, 2);
            $table->string('phone'); // MSISDN used for STK push, e.g. 2547XXXXXXXX

            // Safaricom Daraja identifiers — needed to reconcile the async callback
            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->nullable()->index();
            $table->string('mpesa_receipt_number')->nullable();

            $table->enum('status', ['pending', 'successful', 'failed', 'cancelled'])->default('pending');
            $table->unsignedInteger('result_code')->nullable();
            $table->string('result_desc')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->json('raw_callback')->nullable(); // full callback payload, useful for support/debugging
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
