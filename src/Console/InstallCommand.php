<?php

namespace Digiworld\DigiChat\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'digichat:install';

    protected $description = 'Install the DigiChat package';

    public function handle()
    {
        $this->info('Installing DigiChat package...');

        // Publish config
        $this->call('vendor:publish', [
            '--provider' => 'Digiworld\DigiChat\DigiChatServiceProvider',
            '--tag' => 'digichat-config'
        ]);

        $this->info('DigiChat package installed successfully!');
        $this->info('Please configure your API keys in the .env file:');
        $this->line('DIGICHAT_API_TOKEN=your_api_key_here');
        $this->line('DIGICHAT_API_SECRET=your_api_secret_here');
    }
}
