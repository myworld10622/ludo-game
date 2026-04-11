<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_statement')) {
            Schema::create('tbl_statement', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->default(0);
                $table->string('source', 191)->nullable();
                $table->unsignedBigInteger('source_id')->default(0);
                $table->unsignedTinyInteger('user_type')->default(0);
                $table->decimal('amount', 14, 2)->default(0);
                $table->decimal('current_wallet', 14, 2)->default(0);
                $table->decimal('admin_commission', 14, 2)->default(0);
                $table->decimal('admin_coin', 14, 2)->default(0);
                $table->timestamp('added_date')->nullable();
            });
        }

        if (! Schema::hasTable('tbl_direct_admin_profit_statement')) {
            Schema::create('tbl_direct_admin_profit_statement', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('source', 191)->nullable();
                $table->unsignedBigInteger('source_id')->default(0);
                $table->decimal('admin_coin', 14, 2)->default(0);
                $table->decimal('admin_commission', 14, 2)->default(0);
                $table->timestamp('added_date')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_direct_admin_profit_statement');
        Schema::dropIfExists('tbl_statement');
    }
};
