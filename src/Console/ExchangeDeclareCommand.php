<?php

namespace Laborer\LaravelRabbitMQ\Console;

use Exception;
use Illuminate\Console\Command;
use Laborer\LaravelRabbitMQ\Base\RabbitMQConnect;
use Laborer\LaravelRabbitMQ\Base\RabbitMQConnector;

/**
 * Class ExchangeDeclareCommand
 * @package Laborer\LaravelRabbitMQ\Console
 *
 * php artisan rabbitmq:exchange-declare
 *
 */
class ExchangeDeclareCommand extends Command
{
    protected $signature = 'rabbitmq:exchange-declare
                            {name? : The name of the exchange to declare}
                            {vhost? : The vhost of we choose to connect}
                            {--type=topic}
                            {--durable=1}
                            {--auto-delete=0}';

    protected $description = 'Declare an exchange';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $vhost = $this->argument('vhost');
            if (!$vhost) $vhost = config('rabbitmq.vhost');

            $channel = new RabbitMQConnect($vhost);
            $channel->exchange_declare(
                $this->argument('name'),
                $this->option('type'),
                (bool)$this->option('durable'),
                (bool)$this->option('auto-delete')
            );
            $this->info('Exchange declared successfully.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
