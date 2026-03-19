<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_entry_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('tournament_entry_id')->constrained('tournament_entries')->cascadeOnDelete();
            $table->unsignedInteger('final_rank')->nullable()->index();
            $table->decimal('score', 12, 2)->default(0);
            $table->decimal('prize_amount', 12, 2)->default(0);
            $table->foreignId('wallet_payout_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->string('result_status')->default('pending')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_entry_results');
    }
};
