<?php

namespace CyberDeep\LaravelAgoraTokenGenerator;

use Illuminate\Support\ServiceProvider;


class LaravelAgoraServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //Register Config file
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-agora-token-generator.php', 'laravel-agora-token-generator');

        //Publish Config
        $this->publishes([
           __DIR__.'/../config/laravel-agora-token-generator.php' => config_path('laravel-agora-token-generator.php'),
        ], 'laravel-agora-token-generator-config');

    }

    public function boot(): void
    {
        // Register commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                \CyberDeep\LaravelAgoraTokenGenerator\Commands\GenerateAgoraTokenCommand::class,
            ]);
        }
    }
}
