<?php

namespace Laborer\LaravelRabbitMQ\Tests\Functional;

use Illuminate\Support\Str;
use Laborer\LaravelRabbitMQ\Base\RabbitMQConnect;
use Laborer\LaravelRabbitMQ\Tests\Functional\TestCase as BaseTestCase;

class RabbitMQTest extends BaseTestCase
{
    public function testConnection()
    {
        /** @var $c RabbitMQConnect */
        $c = $this->connection(config('rabbitmq.vhost'));
        $this->assertInstanceOf(RabbitMQConnect::class, $c);
    }

//    public function testQueueMaxPriority()
//    {
//        $c = $this->connection(config('rabbitmq.vhost'));
//        $this->assertIsInt($this->callMethod($c, 'getQueueMaxPriority'));
//        $this->assertSame(2, $this->callMethod($c, 'getQueueMaxPriority'));
//    }

    public function testExchange()
    {
        $c = $this->connection(config('rabbitmq.vhost'));
        $this->assertNull($this->callMethod($c, 'exchange_declare', ['test123.exchange']));
//        $this->assertNull($this->callMethod($c, 'exchange_delete', ['test123.exchange']));
    }

    public function testQueue()
    {
        $c = $this->connection(config('rabbitmq.vhost'));
        $this->assertArrayHasKey(0, $this->callMethod($c, 'queue_declare', ['test123.queue']));
        $this->assertNull($this->callMethod($c, 'queue_bind', ['test123.queue', 'test123.exchange', 'test123.#']));
    }
}
