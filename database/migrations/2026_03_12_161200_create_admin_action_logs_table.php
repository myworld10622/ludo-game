<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 100)->index();
            $table->string('target_type', 100)->nullable()->index();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('route_name', 150)->nullable()->index();
            $table->string('method', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('performed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['admin_user_id', 'performed_at']);
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_action_logs');
    }
};
