<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('rank_from');
            $table->unsignedInteger('rank_to');
            $table->string('reward_type', 30)->default('cash')->index();
            $table->decimal('reward_amount', 16, 4)->default(0);
            $table->string('currency', 10)->default('INR');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tournament_id', 'rank_from', 'rank_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_prizes');
    }
};
