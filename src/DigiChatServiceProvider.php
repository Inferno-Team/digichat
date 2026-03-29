<?php

namespace Digiworld\DigiChat;

use Digiworld\DigiChat\Contracts\DigiChatContract;
use Illuminate\Support\ServiceProvider;

/**
 * What: Registers the DigiChat package services with Laravel.
 * When: Loaded automatically by Laravel package discovery or manual provider registration.
 * Why: The service provider is how the package exposes its config, bindings, and install command.
 */
class DigiChatServiceProvider extends ServiceProvider
{
    /**
     * What: Registers the DigiChat manager and its aliases in the Laravel container.
     * When: Called during the application's service registration phase.
     * Why: Keeping the facade as the default config-backed client while allowing runtime manager construction enables multi-session usage.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/digichat.php', 'digichat');

        $this->app->bind(DigiChatManager::class, function ($app, array $parameters = []) {
            return new DigiChatManager(
                $parameters['token'] ?? null,
                $parameters['secret'] ?? null
            );
        });

        $this->app->bind(DigiChatContract::class, function ($app, array $parameters = []) {
            return $app->make(DigiChatManager::class, $parameters);
        });

        $this->app->singleton('digichat', function ($app) {
            return $app->make(DigiChatManager::class);
        });
    }

    /**
     * What: Publishes package assets and registers console-only commands.
     * When: Called after all providers have been registered.
     * Why: Boot-time setup is the right place for publishable config and installation tooling.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/digichat.php' => config_path('digichat.php'),
        ], 'digichat-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\InstallCommand::class,
            ]);
        }
    }
}
