<?php

namespace Laborer\LaravelRabbitMQ\Tests\Feature;

use Laborer\LaravelRabbitMQ\Base\RabbitMQConnect;

class ConnectorTest extends \Laborer\LaravelRabbitMQ\Tests\TestCase
{
    public function testConnection()
    {
        $app['config']->set('rabbitmq.routing_keys', [
            'order.create' => '创建订单',
            'stock.update' => '更新库存',
        ]);


        /** @var RabbitMQConnect $connection */
        $connection = $this->connection();
        $this->assertInstanceOf(RabbitMQConnect::class, $connection);
    }
}
