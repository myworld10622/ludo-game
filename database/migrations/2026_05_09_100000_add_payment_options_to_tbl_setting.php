<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_setting', function (Blueprint $table) {
            // Option 1 already exists as manual_gateway_enabled
            $table->boolean('option_2_enabled')->default(true)->after('manual_gateway_enabled'); // Automatic Gateway
            $table->boolean('option_3_enabled')->default(true)->after('option_2_enabled');       // USDT Manual
            $table->boolean('option_4_enabled')->default(true)->after('option_3_enabled');       // BEP20 USDT
        });
    }

    public function down(): void
    {
        Schema::table('tbl_setting', function (Blueprint $table) {
            $table->dropColumn(['option_2_enabled', 'option_3_enabled', 'option_4_enabled']);
        });
    }
};
