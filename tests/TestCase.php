<?php

namespace Laborer\LaravelRabbitMQ\Tests;


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

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('rabbitmq.routing_keys', [
            'order.create' => '创建订单',
            'stock.update' => '更新库存',
        ]);
    }

    protected function connection()
    {
        
    }
}
