<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_ludo_tables', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('fee_amount')->default(0);
            $table->unsignedTinyInteger('max_players');
            $table->unsignedTinyInteger('current_players')->default(0);
            $table->unsignedInteger('prize_pool')->default(0);
            $table->enum('status', ['waiting', 'in_progress', 'completed', 'cancelled'])->default('waiting');
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('winner_prize')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('private_ludo_table_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('private_table_id')->constrained('private_ludo_tables')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('fee_paid')->default(0);
            $table->enum('status', ['joined', 'playing', 'left', 'won', 'lost'])->default('joined');
            $table->timestamps();

            $table->unique(['private_table_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_ludo_table_players');
        Schema::dropIfExists('private_ludo_tables');
    }
};
