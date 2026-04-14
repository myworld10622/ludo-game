<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_coin_plan')) {
            return;
        }

        Schema::create('tbl_coin_plan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('coin', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->string('title')->nullable();
            $table->timestamp('added_date')->nullable();
            $table->timestamp('updated_date')->nullable();
            $table->boolean('isDeleted')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_coin_plan');
    }
};
