<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_room_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('seat_no');
            $table->string('player_type', 16)->default('human')->index();
            $table->string('bot_code', 64)->nullable()->index();
            $table->string('status', 32)->default('joined')->index();
            $table->boolean('is_host')->default(false);
            $table->string('reconnect_token', 100)->nullable()->unique();
            $table->integer('score')->default(0);
            $table->unsignedTinyInteger('finish_position')->nullable();
            $table->decimal('payout_amount', 16, 4)->default(0);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['game_room_id', 'seat_no']);
            $table->index(['game_room_id', 'status']);
            $table->index(['game_room_id', 'player_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_room_players');
    }
};
