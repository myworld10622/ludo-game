<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();

            // Player (null if bot)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // Bot fields
            $table->boolean('is_bot')->default(false);
            $table->integer('bot_difficulty')->nullable(); // 1=easy, 2=medium, 3=hard
            $table->string('bot_name')->nullable();

            // Seeding
            $table->integer('seed_number')->nullable();

            // Entry fee
            $table->decimal('entry_fee_paid', 10, 2)->default(0);

            // Status
            $table->enum('status', [
                'registered',
                'checked_in',
                'playing',
                'eliminated',
                'winner',
                'disqualified',
                'refunded',
            ])->default('registered')->index();

            // Result
            $table->integer('final_position')->nullable();
            $table->decimal('prize_won', 10, 2)->default(0);

            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('eliminated_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // One real user per tournament (bots can have multiple entries, user_id null)
            $table->unique(['tournament_id', 'user_id']);
            $table->index(['tournament_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_registrations');
    }
};
