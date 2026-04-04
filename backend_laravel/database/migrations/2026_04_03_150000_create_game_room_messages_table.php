<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_room_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_uuid')->unique();
            $table->foreignId('game_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_match_id')->nullable()->constrained('game_matches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('seat_no')->nullable();
            $table->string('sender_type', 20)->default('human');
            $table->string('message_type', 20)->default('text');
            $table->text('content');
            $table->string('status', 20)->default('visible');
            $table->json('meta')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['game_room_id', 'status']);
            $table->index(['game_room_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_room_messages');
    }
};
