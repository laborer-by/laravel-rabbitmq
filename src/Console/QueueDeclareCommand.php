<?php

namespace Laborer\LaravelRabbitMQ\Console;

use Exception;
use Illuminate\Console\Command;
use Laborer\LaravelRabbitMQ\Base\RabbitMQConnect;

/**
 * Class QueueDeclareCommand
 * @package Laborer\LaravelRabbitMQ\Console
 *
 * php artisan rabbitmq:queue-declare
 */
class QueueDeclareCommand extends Command
{
    protected $signature = 'rabbitmq:queue-declare
                           {name? : The name of the queue to declare}
                           {vhost? : The vhost of we choose to connect}                          
                           {--durable=1}
                           {--auto-delete=0}
                           {--enable-dlx=1 : Whether to enable Dead-Letter-Exchange}';

    protected $description = 'Declare a queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $vhost = $this->argument('vhost');
            if (!$vhost) $vhost = config('rabbitmq.vhost');

            $channel = new RabbitMQConnect($vhost);
            $channel->queue_declare(
                $this->argument('name'),
                (bool) $this->option('durable'),
                (bool) $this->option('auto-delete'),
                (bool) $this->option('enable-dlx')
            );
            $this->info('Queue declared successfully.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
