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
            $table->integer('position');                        // 1, 2, 3, 4, 5
            $table->decimal('prize_pct', 5, 2);                // e.g. 50.00, 25.00
            $table->decimal('prize_amount', 10, 2)->default(0); // calculated after registration closes

            // Payout tracking
            $table->unsignedBigInteger('winner_user_id')->nullable();
            $table->foreign('winner_user_id')->references('id')->on('users')->nullOnDelete();
            $table->enum('payout_status', ['pending', 'paid', 'disputed', 'forfeited', 'cascaded'])
                  ->default('pending');
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->unique(['tournament_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_prizes');
    }
};
