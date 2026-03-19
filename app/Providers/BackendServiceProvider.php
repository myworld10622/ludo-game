<?php

namespace App\Providers;

use App\Services\Auth\AuthService;
use App\Services\Auth\AdminAuthService;
use App\Services\Auth\ExternalIdentitySyncService;
use App\Services\Games\GameCatalogService;
use App\Services\Match\MatchService;
use App\Services\Tournament\TournamentService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\ServiceProvider;

class BackendServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AdminAuthService::class);
        $this->app->singleton(ExternalIdentitySyncService::class);
        $this->app->singleton(AuthService::class);
        $this->app->singleton(GameCatalogService::class);
        $this->app->singleton(TournamentService::class);
        $this->app->singleton(WalletService::class);
        $this->app->singleton(MatchService::class);
    }
}
