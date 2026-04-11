<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_purcharse_ref')) {
            Schema::create('tbl_purcharse_ref', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->default(0);
                $table->unsignedBigInteger('purchase_id')->default(0);
                $table->unsignedBigInteger('purchase_user_id')->default(0);
                $table->decimal('coin', 14, 2)->default(0);
                $table->decimal('purchase_amount', 14, 2)->default(0);
                $table->unsignedTinyInteger('level')->default(0);
            });
        }

        if (! Schema::hasTable('tbl_referral_bonus_log')) {
            Schema::create('tbl_referral_bonus_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->default(0);
                $table->unsignedBigInteger('referred_user_id')->default(0);
                $table->decimal('coin', 14, 2)->default(0);
                $table->timestamp('added_date')->nullable();
            });
        }

        if (! Schema::hasTable('tbl_extra_wallet_log')) {
            Schema::create('tbl_extra_wallet_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->default(0);
                $table->decimal('coin', 14, 2)->default(0);
                $table->unsignedTinyInteger('type')->default(0);
                $table->timestamp('added_date')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_extra_wallet_log');
        Schema::dropIfExists('tbl_referral_bonus_log');
        Schema::dropIfExists('tbl_purcharse_ref');
    }
};
