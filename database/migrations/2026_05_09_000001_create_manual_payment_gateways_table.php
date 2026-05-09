<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_name');           // e.g. "UPI", "Bank Transfer"
            $table->enum('type', ['upi', 'bank']);
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('upi_id')->nullable();
            $table->string('qr_image')->nullable();   // stored filename
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Add global manual gateway toggle to tbl_setting
        if (Schema::hasTable('tbl_setting') && !Schema::hasColumn('tbl_setting', 'manual_gateway_enabled')) {
            Schema::table('tbl_setting', function (Blueprint $table) {
                $table->boolean('manual_gateway_enabled')->default(true)->after('upi_gateway_api_key');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_payment_gateways');
        if (Schema::hasTable('tbl_setting') && Schema::hasColumn('tbl_setting', 'manual_gateway_enabled')) {
            Schema::table('tbl_setting', function (Blueprint $table) {
                $table->dropColumn('manual_gateway_enabled');
            });
        }
    }
};
