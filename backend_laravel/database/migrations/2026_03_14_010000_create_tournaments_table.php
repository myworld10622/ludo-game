<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('standard');
            $table->string('status')->default('draft')->index();
            $table->string('currency', 16)->default('chips');
            $table->decimal('entry_fee', 12, 2)->default(0);
            $table->boolean('allow_multiple_entries')->default(false);
            $table->unsignedInteger('max_entries_per_user')->default(1);
            $table->unsignedInteger('min_total_entries')->default(2);
            $table->unsignedInteger('max_total_entries')->nullable();
            $table->string('ticket_prefix', 32)->nullable();
            $table->unsignedBigInteger('next_entry_no')->default(1);
            $table->unsignedBigInteger('current_total_entries')->default(0);
            $table->unsignedBigInteger('current_active_entries')->default(0);
            $table->timestamp('entry_open_at')->nullable();
            $table->timestamp('entry_close_at')->nullable();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('rules')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
