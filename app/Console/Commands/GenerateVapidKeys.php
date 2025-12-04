<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vapid:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate VAPID keys for Web Push notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $keys = VAPID::createVapidKeys();

            $this->info('VAPID Keys generated successfully!');
            $this->line('');
            $this->line('Add these lines to your .env file:');
            $this->line('');
            $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
            $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
            $this->line('VAPID_SUBJECT=mailto:admin@lambda.com');
            $this->line('');
            $this->warn('Important: Keep your VAPID private key secure and never commit it to version control!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error generating VAPID keys: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
