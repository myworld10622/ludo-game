<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->unsignedInteger('rank_from');
            $table->unsignedInteger('rank_to');
            $table->string('prize_type')->default('fixed');
            $table->decimal('prize_amount', 12, 2)->default(0);
            $table->decimal('prize_percent', 8, 2)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_prizes');
    }
};
