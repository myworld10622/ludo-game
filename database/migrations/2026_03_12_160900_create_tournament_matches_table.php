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
            $table->uuid('match_uuid')->unique();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('round_no')->index();
            $table->unsignedInteger('match_no')->index();
            $table->unsignedInteger('bracket_position')->nullable()->index();
            $table->string('stage', 30)->default('main')->index();
            $table->string('status', 30)->default('pending')->index();
            $table->foreignId('winner_entry_id')->nullable()->constrained('tournament_entries')->nullOnDelete();
            $table->unsignedInteger('max_players')->nullable();
            $table->decimal('table_fee', 16, 4)->default(0);
            $table->string('node_room_id', 100)->nullable()->index();
            $table->string('external_match_ref', 100)->nullable()->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->json('settings')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'round_no', 'match_no', 'stage']);
            $table->index(['tournament_id', 'status', 'scheduled_at']);
            $table->index(['game_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
