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
     * Why: Binding the manager once gives facades, contracts, and direct resolution the same client instance shape.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/digichat.php', 'digichat');

        $this->app->singleton(DigiChatManager::class, function () {
            return new DigiChatManager();
        });

        $this->app->alias(DigiChatManager::class, DigiChatContract::class);
        $this->app->alias(DigiChatManager::class, 'digichat');
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
