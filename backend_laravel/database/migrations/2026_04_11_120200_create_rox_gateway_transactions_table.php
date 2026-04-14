<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('rox_gateway_transactions')) {
            return;
        }

        Schema::create('rox_gateway_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('trx')->unique();
            $table->string('type', 32);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('amount', 16, 4)->default(0);
            $table->string('currency', 8)->default('INR');
            $table->string('status', 32)->default('pending');
            $table->string('gateway_status', 64)->nullable();
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->unsignedBigInteger('refund_wallet_transaction_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['type']);
            $table->index(['user_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rox_gateway_transactions');
    }
};
