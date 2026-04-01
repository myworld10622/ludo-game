<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('bot_start_policy', 32)->default('hybrid')->after('max_bot_pct');
            $table->unsignedTinyInteger('min_real_players_to_start')->default(1)->after('bot_start_policy');
            $table->unsignedSmallInteger('bot_fill_after_seconds')->default(8)->after('min_real_players_to_start');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'bot_start_policy',
                'min_real_players_to_start',
                'bot_fill_after_seconds',
            ]);
        });
    }
};
