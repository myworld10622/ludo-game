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
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('banner_image')->nullable();

            // Creator
            $table->enum('creator_type', ['admin', 'user'])->default('admin');
            $table->unsignedBigInteger('creator_user_id')->nullable(); // null = admin created
            $table->foreign('creator_user_id')->references('id')->on('users')->nullOnDelete();

            // Type & Format
            $table->enum('type', ['public', 'private'])->default('public')->index();
            $table->enum('format', ['knockout', 'double_elim', 'round_robin', 'group_knockout'])->default('knockout');
            $table->enum('bracket_mode', ['auto', 'manual'])->default('auto');

            // Status
            $table->enum('status', [
                'draft',
                'registration_open',
                'registration_closed',
                'in_progress',
                'completed',
                'cancelled',
            ])->default('draft')->index();

            // Entry & Players
            $table->decimal('entry_fee', 10, 2)->default(0);
            $table->integer('max_players')->default(16); // 4, 8, 16, 32, 64, 128
            $table->integer('current_players')->default(0);
            $table->integer('players_per_match')->default(2); // 1v1 or 4-player FFA

            // Prize & Fees
            $table->decimal('total_prize_pool', 10, 2)->default(0);
            $table->decimal('platform_fee_pct', 5, 2)->default(20.00);
            $table->decimal('platform_fee_amount', 10, 2)->default(0);

            // Match Config
            $table->integer('turn_time_limit')->default(30);  // seconds
            $table->integer('match_timeout')->default(2700);  // seconds (45 min)
            $table->integer('disconnect_grace')->default(120); // seconds (2 min)

            // Bot Config
            $table->boolean('bot_allowed')->default(false);
            $table->integer('max_bot_pct')->default(5); // max 5% of slots

            // Private Tournament
            $table->string('invite_code', 10)->nullable()->unique();
            $table->string('invite_password')->nullable();

            // Approval (for user-created large prize pools)
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_approved')->default(true); // admin-created auto-approved

            // Terms
            $table->text('terms_conditions')->nullable();

            // Timestamps for lifecycle
            $table->timestamp('registration_start_at')->nullable();
            $table->timestamp('registration_end_at')->nullable();
            $table->timestamp('tournament_start_at')->nullable();
            $table->timestamp('tournament_end_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status']);
            $table->index(['status', 'registration_start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
