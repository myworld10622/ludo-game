<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tbl_withdrawal_log')) {
            return;
        }

        Schema::create('tbl_withdrawal_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedBigInteger('redeem_id')->default(0);
            $table->string('bank_name')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('acc_holder_name')->nullable();
            $table->string('acc_no')->nullable();
            $table->string('passbook_img')->nullable();
            $table->string('crypto_wallet_type')->nullable();
            $table->string('crypto_qr')->nullable();
            $table->string('crypto_address')->nullable();
            $table->decimal('coin', 16, 4)->default(0);
            $table->decimal('price', 16, 4)->default(0);
            $table->unsignedBigInteger('agent_id')->default(0);
            $table->string('mobile', 32)->nullable();
            $table->unsignedTinyInteger('type')->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('transaction_id')->nullable();
            $table->text('payout_response')->nullable();
            $table->unsignedTinyInteger('isDeleted')->default(0);
            $table->dateTime('created_date')->nullable();
            $table->dateTime('updated_date')->nullable();

            $table->index(['user_id']);
            $table->index(['status']);
            $table->index(['transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_withdrawal_log');
    }
};
