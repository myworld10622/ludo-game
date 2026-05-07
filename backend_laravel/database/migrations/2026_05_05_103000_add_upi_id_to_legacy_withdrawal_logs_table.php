<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('tbl_withdrawal_log') || Schema::hasColumn('tbl_withdrawal_log', 'upi_id')) {
            return;
        }

        Schema::table('tbl_withdrawal_log', function (Blueprint $table) {
            $table->string('upi_id', 190)->nullable()->after('passbook_img');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tbl_withdrawal_log') || ! Schema::hasColumn('tbl_withdrawal_log', 'upi_id')) {
            return;
        }

        Schema::table('tbl_withdrawal_log', function (Blueprint $table) {
            $table->dropColumn('upi_id');
        });
    }
};
