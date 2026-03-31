<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_match_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('tournament_matches')->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained('tournament_registrations')->cascadeOnDelete();

            $table->integer('slot_number');         // 1, 2, 3, 4
            $table->integer('score')->default(0);   // tokens safely at home
            $table->integer('finish_position')->nullable(); // 1=first to finish, etc.

            $table->enum('result', [
                'win',
                'loss',
                'draw',
                'forfeit',
                'disconnected',
            ])->nullable();

            $table->timestamp('joined_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            $table->unique(['match_id', 'slot_number']);
            $table->unique(['match_id', 'registration_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_match_players');
    }
};
