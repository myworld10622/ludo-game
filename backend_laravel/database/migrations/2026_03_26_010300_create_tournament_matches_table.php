<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->integer('round_number');
            $table->integer('match_number');

            // Node.js socket room ID
            $table->string('room_id', 100)->nullable()->unique();

            $table->enum('status', [
                'scheduled',
                'waiting',
                'in_progress',
                'completed',
                'cancelled',
                'disputed',
                'forfeited',
            ])->default('scheduled')->index();

            // Winner (points to tournament_registrations)
            $table->unsignedBigInteger('winner_registration_id')->nullable();
            $table->foreign('winner_registration_id')
                  ->references('id')->on('tournament_registrations')->nullOnDelete();

            // Match data (sent by Node.js)
            $table->json('player_scores')->nullable();  // [{user_id, score, finish_position}]
            $table->json('game_log')->nullable();        // base64 replay or event log

            // Admin override
            $table->boolean('is_admin_override')->default(false);
            $table->text('admin_override_note')->nullable();

            // Timing
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();

            $table->index(['tournament_id', 'round_number']);
            $table->index(['tournament_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
