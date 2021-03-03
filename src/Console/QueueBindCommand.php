<?php

namespace Laborer\LaravelRabbitMQ\Console;

use Exception;
use Illuminate\Console\Command;
use Laborer\LaravelRabbitMQ\Base\RabbitMQConnect;

/**
 * Class QueueBindCommand
 * @package Laborer\LaravelRabbitMQ\Console
 *
 * php artisan rabbitmq:queue-bind --binding-key=order.#
 */
class QueueBindCommand extends Command
{
    protected $signature = 'rabbitmq:queue-bind
                           {queue? : The name of the queue to handle}
                           {exchange? : The name of the queue to bind}
                           {vhost? : The vhost of we choose to connect}   
                           {--binding-key= : Bind queue to exchange via binding_key}';

    protected $description = 'Bind queue to an exchange';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $vhost = $this->argument('vhost');
            if (!$vhost) $vhost = config('rabbitmq.vhost');

            $channel = new RabbitMQConnect($vhost);
            $channel->queue_bind(
                $this->argument('queue'),
                $this->argument('exchange'),
                (string) $this->option('binding-key')
            );

            $this->info('Queue bound to exchange successfully.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
