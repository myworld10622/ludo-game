<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_match_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('tournament_entry_id')->constrained('tournament_entries')->cascadeOnDelete();
            $table->foreignId('game_match_id')->nullable()->constrained('game_matches')->nullOnDelete();
            $table->string('external_match_uuid')->nullable()->index();
            $table->unsignedInteger('round_no')->default(1);
            $table->unsignedInteger('table_no')->nullable();
            $table->string('status')->default('queued');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_match_links');
    }
};
