<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tbl_users')) {
            return;
        }

        Schema::create('tbl_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('mobile', 32)->nullable();
            $table->string('email')->nullable();
            $table->decimal('wallet', 16, 4)->default(0);
            $table->decimal('bonus_wallet', 16, 4)->default(0);
            $table->decimal('winning_wallet', 16, 4)->default(0);
            $table->string('bank_detail')->nullable();
            $table->string('adhar_card')->nullable();
            $table->string('upi')->nullable();
            $table->unsignedBigInteger('referred_by')->default(0);
            $table->unsignedInteger('game_played')->default(0);
            $table->unsignedTinyInteger('isDeleted')->default(0);
            $table->dateTime('created_date')->nullable();
            $table->dateTime('updated_date')->nullable();

            $table->index(['mobile']);
            $table->index(['email']);
            $table->index(['isDeleted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_users');
    }
};
