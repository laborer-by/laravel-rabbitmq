<?php

namespace Laborer\LaravelRabbitMQ;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Laborer\LaravelRabbitMQ\Console\DLXDeclareCommand;
use Laborer\LaravelRabbitMQ\Console\ExchangeDeclareCommand;
use Laborer\LaravelRabbitMQ\Console\ExchangeDeleteCommand;
use Laborer\LaravelRabbitMQ\Console\QueueBindCommand;
use Laborer\LaravelRabbitMQ\Console\QueueDeclareCommand;
use Laborer\LaravelRabbitMQ\Console\QueueDeleteCommand;
use Laborer\LaravelRabbitMQ\Console\ProduceCommand;
use Laborer\LaravelRabbitMQ\Console\ConsumeCommand;

class LaravelRabbitMQServiceProvider extends ServiceProvider
{
    // php artisan vendor:publish --provider="Laborer\LaravelRabbitMQ\LaravelRabbitMQServiceProvider"

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/rabbitmq.php', 'rabbitmq'
        );
        
        $this->commands([
            ExchangeDeclareCommand::class,
            ExchangeDeleteCommand::class,
            QueueDeclareCommand::class,
            QueueBindCommand::class,
            QueueDeleteCommand::class,
            DLXDeclareCommand::class,
        ]);

        // Overwrite the following command if necessary
        $this->commands([
            ProduceCommand::class,
            ConsumeCommand::class,
        ]);
    }

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot()
    {
        $config_file = __DIR__ . '/../config/rabbitmq.php';
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $config_file => config_path('rabbitmq.php'),
            ], 'rabbitmq');

            $this->mergeConfigFrom(
                $config_file, 'rabbitmq'
            );
        }
    }
}
