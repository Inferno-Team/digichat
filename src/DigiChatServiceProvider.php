<?php

namespace Digiworld\DigiChat;

use Illuminate\Support\ServiceProvider;

class DigiChatServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/digichat.php', 'digichat');

        $this->app->singleton('digichat', function ($app) {
            return new DigiChatManager($app);
        });
    }

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
