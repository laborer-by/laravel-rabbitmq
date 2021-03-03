<?php

namespace Laborer\LaravelRabbitMQ\Console;

use Exception;
use Illuminate\Console\Command;
use Laborer\LaravelRabbitMQ\Base\RabbitMQConnect;


/**
 * Class ExchangeDeleteCommand
 * @package Laborer\LaravelRabbitMQ\Console
 *
 * php artisan rabbitmq:exchange-delete oc.exchange
 */
class ExchangeDeleteCommand extends Command
{
    protected $signature = 'rabbitmq:exchange-delete
                            {name : The name of the exchange to declare}
                            {vhost? : The vhost of we choose to connect}                           
                            {--unused=0 : Check if exchange is unused}';

    protected $description = 'Delete an exchange';

    /**
     * @param RabbitMQConnector $connector
     * @throws Exception
     */
    public function handle()
    {
        try {
            $vhost = $this->argument('vhost');
            if (!$vhost) $vhost = config('rabbitmq.vhost');

            $channel = new RabbitMQConnect($vhost);
            $channel->exchange_delete(
                $this->argument('name'),
                (bool) $this->option('unused')
            );
            $this->info('Exchange deleted successfully.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
