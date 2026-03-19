<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_match_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('game_room_player_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('seat_no');
            $table->string('player_type', 16)->default('human')->index();
            $table->string('bot_code', 64)->nullable()->index();
            $table->unsignedTinyInteger('finish_position')->nullable();
            $table->integer('score')->default(0);
            $table->boolean('is_winner')->default(false)->index();
            $table->decimal('payout_amount', 16, 4)->default(0);
            $table->string('status', 32)->default('active')->index();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('stats')->nullable();
            $table->timestamps();

            $table->unique(['game_match_id', 'seat_no']);
            $table->index(['game_match_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_match_players');
    }
};
