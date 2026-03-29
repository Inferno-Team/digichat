<?php

namespace Digiworld\DigiChat\Console;

use Illuminate\Console\Command;

/**
 * What: Console installer for publishing the DigiChat package configuration.
 * When: Run by package users after installation with `php artisan digichat:install`.
 * Why: A dedicated install command gives users a simple way to publish the config they need to get started.
 */
class InstallCommand extends Command
{
    protected $signature = 'digichat:install';

    protected $description = 'Install the DigiChat package';

    /**
     * What: Publishes the package config and prints the required environment keys.
     * When: Called when the install command is executed from Artisan.
     * Why: The installer shortens setup time and reduces mistakes during first-time package configuration.
     */
    public function handle()
    {
        $this->info('Installing DigiChat package...');

        $this->call('vendor:publish', [
            '--provider' => 'Digiworld\DigiChat\DigiChatServiceProvider',
            '--tag' => 'digichat-config',
        ]);

        $this->info('DigiChat package installed successfully!');
        $this->info('Please configure your API keys in the .env file:');
        $this->line('DIGICHAT_API_TOKEN=your_api_key_here');
        $this->line('DIGICHAT_API_SECRET=your_api_secret_here');
    }
}
