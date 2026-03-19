<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('entry_uuid')->unique();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->unsignedInteger('entry_no')->default(1);
            $table->string('status', 30)->default('registered')->index();
            $table->unsignedInteger('seed')->nullable()->index();
            $table->unsignedInteger('final_rank')->nullable()->index();
            $table->decimal('entry_fee', 16, 4)->default(0);
            $table->decimal('prize_amount', 16, 4)->default(0);
            $table->json('meta')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('eliminated_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['tournament_id', 'user_id', 'entry_no']);
            $table->index(['tournament_id', 'status']);
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['game_id', 'tournament_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_entries');
    }
};
