<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_uuid', 64)->unique();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('queue_key', 100)->nullable()->index();
            $table->string('room_type', 32)->default('public')->index();
            $table->string('play_mode', 32)->default('cash')->index();
            $table->string('status', 32)->default('waiting')->index();
            $table->unsignedTinyInteger('max_players')->default(4);
            $table->unsignedTinyInteger('min_real_players')->default(1);
            $table->unsignedTinyInteger('current_players')->default(0);
            $table->unsignedTinyInteger('current_real_players')->default(0);
            $table->unsignedTinyInteger('current_bot_players')->default(0);
            $table->decimal('entry_fee', 16, 4)->default(0);
            $table->decimal('prize_pool', 16, 4)->default(0);
            $table->boolean('allow_bots')->default(true)->index();
            $table->unsignedSmallInteger('bot_fill_after_seconds')->default(8);
            $table->boolean('started_with_bots')->default(false);
            $table->string('game_mode', 50)->nullable()->index();
            $table->string('node_namespace', 100)->nullable();
            $table->string('node_room_id', 100)->nullable()->index();
            $table->timestamp('registration_closed_at')->nullable();
            $table->timestamp('fill_bots_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('settings')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'status']);
            $table->index(['game_id', 'room_type', 'play_mode', 'status'], 'game_rooms_matchmaking_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_rooms');
    }
};
