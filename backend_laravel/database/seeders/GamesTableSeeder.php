<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

class GamesTableSeeder extends Seeder
{
    public function run(): void
    {
        $ludoOnlyVisible = config('platform.games.seed_ludo_only_visible', true);

        $games = [
            [
                'code' => 'ludo',
                'name' => 'Ludo',
                'slug' => 'ludo',
                'description' => 'Core multiplayer board game.',
                'sort_order' => 1,
                'launch_type' => 'node_room',
                'client_route' => 'ludo',
                'socket_namespace' => '/ludo',
                'tournaments_enabled' => true,
            ],
            [
                'code' => 'rummy',
                'name' => 'Rummy',
                'slug' => 'rummy',
                'description' => 'Future-ready card game module.',
                'sort_order' => 2,
                'launch_type' => 'internal',
                'client_route' => 'rummy',
                'socket_namespace' => '/rummy',
                'tournaments_enabled' => false,
            ],
            [
                'code' => 'teen_patti',
                'name' => 'Teen Patti',
                'slug' => 'teen-patti',
                'description' => 'Future-ready card game module.',
                'sort_order' => 3,
                'launch_type' => 'internal',
                'client_route' => 'teen-patti',
                'socket_namespace' => '/teen-patti',
                'tournaments_enabled' => false,
            ],
            [
                'code' => 'poker',
                'name' => 'Poker',
                'slug' => 'poker',
                'description' => 'Future-ready card game module.',
                'sort_order' => 4,
                'launch_type' => 'internal',
                'client_route' => 'poker',
                'socket_namespace' => '/poker',
                'tournaments_enabled' => false,
            ],
            [
                'code' => 'aviator',
                'name' => 'Aviator',
                'slug' => 'aviator',
                'description' => 'Future-ready crash game module.',
                'sort_order' => 5,
                'launch_type' => 'external',
                'client_route' => 'aviator',
                'socket_namespace' => '/aviator',
                'tournaments_enabled' => false,
            ],
            [
                'code' => 'andar_bahar',
                'name' => 'Andar Bahar',
                'slug' => 'andar-bahar',
                'description' => 'Future-ready card game module.',
                'sort_order' => 6,
                'launch_type' => 'internal',
                'client_route' => 'andar-bahar',
                'socket_namespace' => '/andar-bahar',
                'tournaments_enabled' => false,
            ],
            [
                'code' => 'dragon_tiger',
                'name' => 'Dragon Tiger',
                'slug' => 'dragon-tiger',
                'description' => 'Future-ready card game module.',
                'sort_order' => 7,
                'launch_type' => 'internal',
                'client_route' => 'dragon-tiger',
                'socket_namespace' => '/dragon-tiger',
                'tournaments_enabled' => false,
            ],
            [
                'code' => 'baccarat',
                'name' => 'Baccarat',
                'slug' => 'baccarat',
                'description' => 'Future-ready card game module.',
                'sort_order' => 8,
                'launch_type' => 'internal',
                'client_route' => 'baccarat',
                'socket_namespace' => '/baccarat',
                'tournaments_enabled' => false,
            ],
        ];

        foreach ($games as $game) {
            $isVisible = $ludoOnlyVisible ? $game['code'] === 'ludo' : true;

            Game::updateOrCreate(
                ['code' => $game['code']],
                array_merge($game, [
                    'is_active' => true,
                    'is_visible' => $isVisible,
                    'published_at' => $isVisible ? now() : null,
                    'metadata' => [
                        'seeded' => true,
                    ],
                ])
            );
        }
    }
}
