<?php

namespace Laborer\LaravelRabbitMQ\Tests;


use Laborer\LaravelRabbitMQ\Base\RabbitMQConnect;
use Laborer\LaravelRabbitMQ\LaravelRabbitMQServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelRabbitMQServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('rabbitmq.routing_keys', [
            'order.create' => '创建订单',
            'stock.update' => '更新库存',
        ]);

        // Setup default database
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', ''),
            'database'  => env('DB_DATABASE', ''),
            'username'  => env('DB_USERNAME', ''),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ]);
    }

    protected function connection($vhost)
    {
        return new RabbitMQConnect($vhost);
    }
}
