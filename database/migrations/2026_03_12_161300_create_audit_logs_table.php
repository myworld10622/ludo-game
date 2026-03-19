<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event', 100)->index();
            $table->string('actor_type', 50)->nullable()->index();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('auditable_type', 100)->index();
            $table->unsignedBigInteger('auditable_id')->index();
            $table->string('source', 50)->default('system')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_event_at')->nullable()->index();
            $table->timestamps();

            $table->index(['actor_type', 'actor_id']);
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
