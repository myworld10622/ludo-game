<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_match_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_match_id')->constrained('tournament_matches')->cascadeOnDelete();
            $table->foreignId('tournament_entry_id')->constrained('tournament_entries')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('seat_no')->nullable();
            $table->unsignedInteger('position')->nullable()->index();
            $table->unsignedInteger('score')->default(0);
            $table->boolean('is_winner')->default(false)->index();
            $table->string('status', 30)->default('pending')->index();
            $table->json('stats')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['tournament_match_id', 'tournament_entry_id'], 'tm_entries_match_entry_unique');
            $table->unique(['tournament_match_id', 'seat_no'], 'tm_entries_match_seat_unique');
            $table->index(['user_id', 'status']);
            $table->index(['tournament_entry_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_match_entries');
    }
};
