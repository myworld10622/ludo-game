<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100);
            $table->json('value')->nullable();
            $table->string('value_type', 30)->default('json');
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();

            $table->unique(['game_id', 'key']);
            $table->index(['game_id', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_settings');
    }
};
