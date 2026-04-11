<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_setting')) {
            Schema::create('tbl_setting', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->decimal('referral_amount', 12, 2)->default(0);
                $table->decimal('level_1', 6, 2)->default(0);
                $table->decimal('level_2', 6, 2)->default(0);
                $table->decimal('level_3', 6, 2)->default(0);
                $table->decimal('level_4', 6, 2)->default(0);
                $table->decimal('level_5', 6, 2)->default(0);
                $table->decimal('level_6', 6, 2)->default(0);
                $table->decimal('level_7', 6, 2)->default(0);
                $table->decimal('level_8', 6, 2)->default(0);
                $table->decimal('level_9', 6, 2)->default(0);
                $table->decimal('level_10', 6, 2)->default(0);
                $table->string('referral_id', 190)->default('');
                $table->string('referral_link', 255)->default('');
                $table->string('share_text', 255)->default('');
                $table->decimal('min_withdrawal', 12, 2)->default(0);
                $table->decimal('min_redeem', 12, 2)->default(0);
                $table->decimal('admin_commission', 12, 2)->default(0);
                $table->decimal('distribute_precent', 12, 2)->default(0);
                $table->boolean('bonus')->default(false);
                $table->decimal('bonus_amount', 12, 2)->default(0);
                $table->string('upi_id', 190)->nullable();
                $table->string('usdt_address', 190)->nullable();
                $table->string('upi_gateway_api_key', 255)->nullable();
                $table->decimal('dollar', 12, 2)->default(0);
                $table->string('daily_bonus_status', 50)->default('0');
                $table->string('app_popop_status', 50)->default('0');
                $table->text('fcm_server_key')->nullable();
                $table->string('qr_image', 255)->nullable();
                $table->string('usdt_qr_image', 255)->nullable();
                $table->decimal('admin_coin', 14, 2)->default(0);
                $table->timestamp('added_date')->nullable();
                $table->timestamp('updated_date')->nullable();
                $table->boolean('isDeleted')->default(false);
            });
        }

        if (Schema::hasTable('tbl_setting') && DB::table('tbl_setting')->count() === 0) {
            DB::table('tbl_setting')->insert([
                'referral_amount' => 0,
                'level_1' => 0,
                'level_2' => 0,
                'level_3' => 0,
                'level_4' => 0,
                'level_5' => 0,
                'level_6' => 0,
                'level_7' => 0,
                'level_8' => 0,
                'level_9' => 0,
                'level_10' => 0,
                'referral_id' => 'ROXLUDO',
                'referral_link' => 'https://roxludo.com',
                'share_text' => 'Join RoxLudo',
                'min_withdrawal' => 0,
                'min_redeem' => 0,
                'admin_commission' => 0,
                'distribute_precent' => 0,
                'bonus' => 0,
                'bonus_amount' => 0,
                'upi_id' => null,
                'usdt_address' => null,
                'upi_gateway_api_key' => null,
                'dollar' => 0,
                'daily_bonus_status' => '0',
                'app_popop_status' => '0',
                'fcm_server_key' => '',
                'qr_image' => null,
                'usdt_qr_image' => null,
                'admin_coin' => 0,
                'added_date' => now(),
                'updated_date' => now(),
                'isDeleted' => 0,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_setting');
    }
};
