<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_recovery_channels')) {
            Schema::create('user_recovery_channels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('channel_type', 32);
                $table->string('channel_value', 190);
                $table->boolean('is_verified')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();

                $table->unique(['user_id', 'channel_type', 'channel_value'], 'user_recovery_unique');
                $table->index(['user_id', 'channel_type'], 'user_recovery_user_type_idx');
            });
        }

        if (! Schema::hasTable('user_security_reminders')) {
            Schema::create('user_security_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('last_shown_at')->nullable();
                $table->timestamp('dismissed_until')->nullable();
                $table->boolean('is_completed')->default(false);
                $table->timestamps();

                $table->unique('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_security_reminders');
        Schema::dropIfExists('user_recovery_channels');
    }
};
