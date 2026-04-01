<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classic_ludo_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('player_count');
            $table->decimal('entry_fee', 16, 4);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'player_count', 'entry_fee'], 'classic_ludo_tables_unique');
            $table->index(['game_id', 'player_count', 'is_active'], 'classic_ludo_tables_lookup');
        });

        $ludoGameId = DB::table('games')->where('slug', 'ludo')->value('id');

        if ($ludoGameId) {
            $seedRows = [
                ['player_count' => 2, 'entry_fee' => 10, 'sort_order' => 10],
                ['player_count' => 2, 'entry_fee' => 50, 'sort_order' => 20],
                ['player_count' => 2, 'entry_fee' => 100, 'sort_order' => 30],
                ['player_count' => 2, 'entry_fee' => 500, 'sort_order' => 40],
                ['player_count' => 4, 'entry_fee' => 10, 'sort_order' => 10],
                ['player_count' => 4, 'entry_fee' => 50, 'sort_order' => 20],
                ['player_count' => 4, 'entry_fee' => 100, 'sort_order' => 30],
                ['player_count' => 4, 'entry_fee' => 500, 'sort_order' => 40],
            ];

            foreach ($seedRows as $row) {
                DB::table('classic_ludo_tables')->updateOrInsert(
                    [
                        'game_id' => $ludoGameId,
                        'player_count' => $row['player_count'],
                        'entry_fee' => number_format((float) $row['entry_fee'], 4, '.', ''),
                    ],
                    [
                        'sort_order' => $row['sort_order'],
                        'is_active' => true,
                        'notes' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('classic_ludo_tables');
    }
};
