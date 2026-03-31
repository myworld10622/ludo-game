<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->enum('type', [
                'entry_fee',          // Player pays entry fee
                'prize_credit',       // Player wins prize
                'refund',             // Tournament cancelled / player withdrew
                'platform_fee',       // 20% platform cut (credited to platform account)
                'creation_deposit',   // User-created tournament deposit
            ])->index();

            $table->decimal('amount', 10, 2);

            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('completed');

            $table->string('reference_id', 100)->nullable(); // links to wallet_transactions.id
            $table->text('description')->nullable();

            $table->foreignId('registration_id')
                  ->nullable()
                  ->constrained('tournament_registrations')
                  ->nullOnDelete();

            $table->foreignId('match_id')
                  ->nullable()
                  ->constrained('tournament_matches')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index(['tournament_id', 'user_id']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_wallet_transactions');
    }
};
