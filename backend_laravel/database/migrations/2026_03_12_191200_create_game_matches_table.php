<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();
            $table->string('match_uuid', 64)->unique();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('pending')->index();
            $table->string('mode', 32)->default('realtime')->index();
            $table->unsignedTinyInteger('max_players')->default(4);
            $table->unsignedTinyInteger('real_players')->default(0);
            $table->unsignedTinyInteger('bot_players')->default(0);
            $table->decimal('entry_fee', 16, 4)->default(0);
            $table->decimal('prize_pool', 16, 4)->default(0);
            $table->string('node_namespace', 100)->nullable();
            $table->string('node_room_id', 100)->nullable()->index();
            $table->string('server_seed', 120)->nullable();
            $table->json('turn_state')->nullable();
            $table->json('result_payload')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'status']);
            $table->index(['game_room_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_matches');
    }
};
