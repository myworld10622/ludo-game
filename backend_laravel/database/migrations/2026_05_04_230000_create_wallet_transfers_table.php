<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('transfer_uuid')->unique();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sender_wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('receiver_wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('sender_wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->foreignId('receiver_wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->decimal('amount', 14, 4);
            $table->string('currency', 10)->default('INR');
            $table->string('status', 20)->default('completed');
            $table->string('note', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['sender_user_id', 'created_at']);
            $table->index(['receiver_user_id', 'created_at']);
            $table->index(['currency', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transfers');
    }
};
