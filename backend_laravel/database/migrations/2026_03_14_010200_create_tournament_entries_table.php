<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('entry_no');
            $table->string('ticket_no')->index();
            $table->unsignedInteger('entry_index_for_user')->default(1);
            $table->string('status')->default('joined')->index();
            $table->decimal('entry_fee', 12, 2)->default(0);
            $table->foreignId('wallet_hold_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->foreignId('wallet_capture_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->foreignId('wallet_refund_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('eliminated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'entry_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_entries');
    }
};
