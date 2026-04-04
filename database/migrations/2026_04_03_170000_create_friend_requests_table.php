<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friend_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_uuid')->unique();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('source', 30)->default('profile');
            $table->string('source_room_uuid', 100)->nullable();
            $table->string('message', 160)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['receiver_user_id', 'status']);
            $table->index(['sender_user_id', 'status']);
            $table->index(['sender_user_id', 'receiver_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friend_requests');
    }
};
