<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('wallet_type', 30)->default('cash');
            $table->string('currency', 10)->default('INR');
            $table->decimal('balance', 16, 4)->default(0);
            $table->decimal('locked_balance', 16, 4)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_transaction_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'wallet_type', 'currency']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
