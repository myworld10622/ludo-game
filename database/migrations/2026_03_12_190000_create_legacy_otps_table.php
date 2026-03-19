<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_otps', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 20)->index();
            $table->string('type', 30)->index();
            $table->string('otp_code', 10);
            $table->boolean('is_used')->default(false)->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['mobile', 'type', 'is_used']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_otps');
    }
};
