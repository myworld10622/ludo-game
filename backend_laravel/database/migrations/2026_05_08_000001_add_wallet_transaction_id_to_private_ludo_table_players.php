<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('private_ludo_table_players', function (Blueprint $table) {
            $table->unsignedBigInteger('wallet_transaction_id')->nullable()->after('fee_paid');
        });
    }

    public function down(): void
    {
        Schema::table('private_ludo_table_players', function (Blueprint $table) {
            $table->dropColumn('wallet_transaction_id');
        });
    }
};
