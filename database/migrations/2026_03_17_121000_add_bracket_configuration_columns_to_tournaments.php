<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            if (! Schema::hasColumn('tournaments', 'match_size')) {
                $table->unsignedTinyInteger('match_size')->default(4)->after('max_total_entries');
            }
            if (! Schema::hasColumn('tournaments', 'advance_count')) {
                $table->unsignedTinyInteger('advance_count')->default(1)->after('match_size');
            }
            if (! Schema::hasColumn('tournaments', 'bracket_size')) {
                $table->unsignedBigInteger('bracket_size')->nullable()->after('advance_count');
            }
            if (! Schema::hasColumn('tournaments', 'bye_count')) {
                $table->unsignedBigInteger('bye_count')->default(0)->after('bracket_size');
            }
            if (! Schema::hasColumn('tournaments', 'seeding_strategy')) {
                $table->string('seeding_strategy', 32)->default('random')->after('bye_count');
            }
            if (! Schema::hasColumn('tournaments', 'bot_fill_policy')) {
                $table->string('bot_fill_policy', 32)->default('fill_after_timeout')->after('seeding_strategy');
            }
        });

        DB::table('tournaments')
            ->select('id', 'match_size', 'advance_count', 'bracket_size', 'bye_count', 'max_total_entries', 'min_total_entries', 'rules', 'meta')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $rules = json_decode($row->rules ?? 'null', true) ?: [];
                    $meta = json_decode($row->meta ?? 'null', true) ?: [];

                    $matchSize = max(2, min(4, (int) (
                        $rules['players_per_match']
                        ?? $meta['players_per_match']
                        ?? $meta['max_players']
                        ?? $row->match_size
                        ?? 4
                    )));

                    if (! in_array($matchSize, [2, 4], true)) {
                        $matchSize = 4;
                    }

                    $advanceCount = (int) ($row->advance_count ?? $rules['advance_count'] ?? $meta['advance_count'] ?? 1);
                    $advanceCount = max(1, min($matchSize - 1, $advanceCount));

                    $entryTarget = (int) ($row->max_total_entries ?? $row->min_total_entries ?? 0);
                    $bracketSize = (int) ($row->bracket_size ?? $this->resolveBracketSize($entryTarget, $matchSize));
                    $byeCount = (int) ($row->bye_count ?? max(0, $bracketSize - $entryTarget));

                    DB::table('tournaments')
                        ->where('id', $row->id)
                        ->update([
                            'match_size' => $matchSize,
                            'advance_count' => $advanceCount,
                            'bracket_size' => $bracketSize > 0 ? $bracketSize : null,
                            'bye_count' => $byeCount,
                            'seeding_strategy' => DB::raw("COALESCE(seeding_strategy, 'random')"),
                            'bot_fill_policy' => DB::raw("COALESCE(bot_fill_policy, 'fill_after_timeout')"),
                        ]);
                }
            });
    }

    public function down(): void
    {
    }

    private function resolveBracketSize(int $entryTarget, int $matchSize): int
    {
        if ($entryTarget <= 0) {
            return 0;
        }

        $base = in_array($matchSize, [2, 4], true) ? $matchSize : 4;
        $bracketSize = $base;

        while ($bracketSize < $entryTarget) {
            $bracketSize *= $base;
        }

        return $bracketSize;
    }
};
