<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('status', 30)->default('draft')->index();
            $table->string('visibility', 30)->default('public')->index();
            $table->string('format', 30)->default('knockout')->index();
            $table->unsignedInteger('max_entries_per_user')->default(1);
            $table->unsignedInteger('max_entries')->nullable();
            $table->unsignedInteger('min_players')->default(2);
            $table->unsignedInteger('max_players')->nullable();
            $table->decimal('entry_fee', 16, 4)->default(0);
            $table->decimal('platform_fee', 16, 4)->default(0);
            $table->decimal('prize_pool', 16, 4)->default(0);
            $table->string('currency', 10)->default('INR');
            $table->boolean('allow_re_entry')->default(false)->index();
            $table->timestamp('registration_starts_at')->nullable()->index();
            $table->timestamp('registration_ends_at')->nullable()->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'status', 'starts_at']);
            $table->index(['game_id', 'visibility', 'registration_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
