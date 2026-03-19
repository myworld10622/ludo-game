<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            $duplicates = DB::table('tournament_match_links')
                ->select('tournament_id', 'tournament_entry_id', 'round_no', 'table_no', DB::raw('MAX(id) as keep_id'))
                ->groupBy('tournament_id', 'tournament_entry_id', 'round_no', 'table_no')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicates as $duplicate) {
                DB::table('tournament_match_links')
                    ->where('tournament_id', $duplicate->tournament_id)
                    ->where('tournament_entry_id', $duplicate->tournament_entry_id)
                    ->where('round_no', $duplicate->round_no)
                    ->where('table_no', $duplicate->table_no)
                    ->where('id', '!=', $duplicate->keep_id)
                    ->delete();
            }
        });

        Schema::table('tournament_match_links', function (Blueprint $table) {
            $table->unique(
                ['tournament_id', 'tournament_entry_id', 'round_no', 'table_no'],
                'tml_tournament_entry_round_table_unique'
            );
            $table->index(
                ['tournament_id', 'round_no', 'table_no', 'status'],
                'tml_tournament_round_table_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tournament_match_links', function (Blueprint $table) {
            $table->dropUnique('tml_tournament_entry_round_table_unique');
            $table->dropIndex('tml_tournament_round_table_status_index');
        });
    }
};
