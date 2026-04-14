<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_purchase')) {
            return;
        }

        Schema::create('tbl_purchase', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('plan_id')->default(0)->index();
            $table->decimal('coin', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->tinyInteger('payment')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->string('transaction_id')->nullable();
            $table->integer('transaction_type')->default(0);
            $table->string('utr')->nullable();
            $table->string('photo')->nullable();
            $table->string('razor_payment_id')->nullable();
            $table->text('json_response')->nullable();
            $table->text('extra')->nullable();
            $table->timestamp('added_date')->nullable();
            $table->timestamp('updated_date')->nullable();
            $table->boolean('isDeleted')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_purchase');
    }
};
