<?php

namespace Laborer\LaravelRabbitMQ\Console;

use Exception;
use Illuminate\Console\Command;
use Laborer\LaravelRabbitMQ\Base\RabbitMQConnect;
use Laborer\LaravelRabbitMQ\Queue\Connectors\RabbitMQConnector;

/**
 * Class QueueDeleteCommand
 * @package Laborer\LaravelRabbitMQ\Console
 *
 * php artisan rabbitmq:queue-delete oc.test.queue
 */
class QueueDeleteCommand extends Command
{
    protected $signature = 'rabbitmq:queue-delete
                           {name : The name of the queue to delete}
                           {vhost? : The vhost of we choose to connect}   
                           {--unused=0 : Check if queue has no consumers}
                           {--empty=0 : Check if queue is empty}';

    protected $description = 'Delete a queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $vhost = $this->argument('vhost');
            if (!$vhost) $vhost = config('rabbitmq.vhost');

            $channel = new RabbitMQConnect($vhost);
            $channel->queue_delete(
                $this->argument('name'),
                (bool) $this->option('unused'),
                (bool) $this->option('empty')
            );

            $this->info('Queue deleted successfully.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
