<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tournaments', 'max_entries') && ! Schema::hasColumn('tournaments', 'max_total_entries')) {
            DB::statement("ALTER TABLE `tournaments` CHANGE `max_entries` `max_total_entries` INT UNSIGNED NULL");
        }

        if (Schema::hasColumn('tournaments', 'format') && ! Schema::hasColumn('tournaments', 'tournament_type')) {
            DB::statement("ALTER TABLE `tournaments` CHANGE `format` `tournament_type` VARCHAR(30) NOT NULL DEFAULT 'knockout'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tournaments', 'max_total_entries') && ! Schema::hasColumn('tournaments', 'max_entries')) {
            DB::statement("ALTER TABLE `tournaments` CHANGE `max_total_entries` `max_entries` INT UNSIGNED NULL");
        }

        if (Schema::hasColumn('tournaments', 'tournament_type') && ! Schema::hasColumn('tournaments', 'format')) {
            DB::statement("ALTER TABLE `tournaments` CHANGE `tournament_type` `format` VARCHAR(30) NOT NULL DEFAULT 'knockout'");
        }
    }
};
