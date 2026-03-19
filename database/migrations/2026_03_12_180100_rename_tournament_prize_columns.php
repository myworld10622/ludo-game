<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tournament_prizes', 'reward_type') && ! Schema::hasColumn('tournament_prizes', 'prize_type')) {
            DB::statement("ALTER TABLE `tournament_prizes` CHANGE `reward_type` `prize_type` VARCHAR(30) NOT NULL DEFAULT 'cash'");
        }

        if (Schema::hasColumn('tournament_prizes', 'reward_amount') && ! Schema::hasColumn('tournament_prizes', 'prize_amount')) {
            DB::statement("ALTER TABLE `tournament_prizes` CHANGE `reward_amount` `prize_amount` DECIMAL(16,4) NOT NULL DEFAULT 0");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tournament_prizes', 'prize_type') && ! Schema::hasColumn('tournament_prizes', 'reward_type')) {
            DB::statement("ALTER TABLE `tournament_prizes` CHANGE `prize_type` `reward_type` VARCHAR(30) NOT NULL DEFAULT 'cash'");
        }

        if (Schema::hasColumn('tournament_prizes', 'prize_amount') && ! Schema::hasColumn('tournament_prizes', 'reward_amount')) {
            DB::statement("ALTER TABLE `tournament_prizes` CHANGE `prize_amount` `reward_amount` DECIMAL(16,4) NOT NULL DEFAULT 0");
        }
    }
};
