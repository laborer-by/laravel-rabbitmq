<?php

namespace Laborer\LaravelRabbitMQ\Console;

use Exception;
use Illuminate\Console\Command;
use Laborer\LaravelRabbitMQ\Base\RabbitMQConnect;

/**
 * Class DLXDeclareCommand
 * @package Laborer\LaravelRabbitMQ\Console
 *
 * php artisan rabbitmq:dlx-declare
 */
class DLXDeclareCommand extends Command
{
    protected $signature = 'rabbitmq:dlx-declare
                           {vhost? : The vhost of we choose to connect}';

    protected $description = 'Declare Dead-Letter-Exchange,queue and establish a binding relationship';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $vhost = $this->argument('vhost');
            if (!$vhost) $vhost = config('rabbitmq.vhost');

            $channel = new RabbitMQConnect($vhost);
            $channel->dlx_declare();
            $this->info('dlx declared successfully.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
