<?php

namespace Laborer\LaravelRabbitMQ\Tests\Functional;

use Illuminate\Support\Str;
use Laborer\LaravelRabbitMQ\Base\RabbitMQConnect;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use Laborer\LaravelRabbitMQ\Tests\Functional\TestCase as BaseTestCase;

class RabbitMQTest extends BaseTestCase
{
    public function testConnection(): void
    {
        /** @var $c RabbitMQQueue */
        $c = $this->connection();
        $this->assertInstanceOf(RabbitMQConnect::class, $c);
    }

    public function testRerouteFailed(): void
    {
        /** @var $c RabbitMQQueue */
        $c = $this->connection();
        $this->assertFalse($this->callMethod($c, 'isRerouteFailed'));

        $c = $this->connection('rabbitmq-with-options');
        $this->assertTrue($this->callMethod($c, 'isRerouteFailed'));

        $c = $this->connection('rabbitmq-with-options-empty');
        $this->assertFalse($this->callMethod($c, 'isRerouteFailed'));
    }

    public function testPrioritizeDelayed(): void
    {
        /** @var $c RabbitMQQueue */
        $c = $this->connection();
        $this->assertFalse($this->callMethod($c, 'isPrioritizeDelayed'));

        $c = $this->connection('rabbitmq-with-options');
        $this->assertTrue($this->callMethod($c, 'isPrioritizeDelayed'));

        $c = $this->connection('rabbitmq-with-options-empty');
        $this->assertFalse($this->callMethod($c, 'isPrioritizeDelayed'));
    }

    public function testQueueMaxPriority(): void
    {
        /** @var $c RabbitMQQueue */
        $c = $this->connection();
        $this->assertIsInt($this->callMethod($c, 'getQueueMaxPriority'));
        $this->assertSame(2, $this->callMethod($c, 'getQueueMaxPriority'));

        $c = $this->connection('rabbitmq-with-options');
        $this->assertIsInt($this->callMethod($c, 'getQueueMaxPriority'));
        $this->assertSame(20, $this->callMethod($c, 'getQueueMaxPriority'));

        $c = $this->connection('rabbitmq-with-options-empty');
        $this->assertIsInt($this->callMethod($c, 'getQueueMaxPriority'));
        $this->assertSame(2, $this->callMethod($c, 'getQueueMaxPriority'));
    }

    public function testExchangeType(): void
    {
        /** @var $c RabbitMQQueue */
        $c = $this->connection();
        $this->assertSame(AMQPExchangeType::DIRECT, $this->callMethod($c, 'getExchangeType'));
        $this->assertSame(AMQPExchangeType::DIRECT, $this->callMethod($c, 'getExchangeType', ['']));
        $this->assertSame(AMQPExchangeType::DIRECT, $this->callMethod($c, 'getExchangeType', ['test']));
        $this->assertSame(AMQPExchangeType::TOPIC, $this->callMethod($c, 'getExchangeType', ['topic']));

        $c = $this->connection('rabbitmq-with-options');
        $this->assertSame(AMQPExchangeType::TOPIC, $this->callMethod($c, 'getExchangeType'));

        $c = $this->connection('rabbitmq-with-options-empty');
        $this->assertSame(AMQPExchangeType::DIRECT, $this->callMethod($c, 'getExchangeType'));
    }

    public function testExchange(): void
    {
        /** @var $c RabbitMQQueue */
        $c = $this->connection();
        $this->assertSame('test', $this->callMethod($c, 'getExchange', ['test']));
        $this->assertNull($this->callMethod($c, 'getExchange', ['']));
        $this->assertNull($this->callMethod($c, 'getExchange'));

        $c = $this->connection('rabbitmq-with-options');
        $this->assertNotNull($this->callMethod($c, 'getExchange'));
        $this->assertSame('application-x', $this->callMethod($c, 'getExchange'));

        $c = $this->connection('rabbitmq-with-options-empty');
        $this->assertNull($this->callMethod($c, 'getExchange'));
    }

    public function testFailedExchange(): void
    {
        /** @var $c RabbitMQQueue */
        $c = $this->connection();
        $this->assertSame('test', $this->callMethod($c, 'getFailedExchange', ['test']));
        $this->assertNull($this->callMethod($c, 'getExchange', ['']));
        $this->assertNull($this->callMethod($c, 'getFailedExchange'));

        $c = $this->connection('rabbitmq-with-options');
        $this->assertNotNull($this->callMethod($c, 'getFailedExchange'));
        $this->assertSame('failed-exchange', $this->callMethod($c, 'getFailedExchange'));

        $c = $this->connection('rabbitmq-with-options-empty');
        $this->assertNull($this->callMethod($c, 'getFailedExchange'));
    }

    public function testRoutingKey(): void
    {
        /** @var $c RabbitMQQueue */
        $c = $this->connection();
        $this->assertSame('test', $this->callMethod($c, 'getRoutingKey', ['test']));
        $this->assertSame('', $this->callMethod($c, 'getRoutingKey', ['']));

        $c = $this->connection('rabbitmq-with-options');
        $this->assertSame('process.test', $this->callMethod($c, 'getRoutingKey', ['test']));

        $c = $this->connection('rabbitmq-with-options-empty');
        $this->assertSame('test', $this->callMethod($c, 'getRoutingKey', ['test']));
    }

    public function testFailedRoutingKey(): void
    {
        /** @var $c RabbitMQQueue */
        $c = $this->connection();
        $this->assertSame('test.failed', $this->callMethod($c, 'getFailedRoutingKey', ['test']));
        $this->assertSame('failed', $this->callMethod($c, 'getFailedRoutingKey', ['']));

        $c = $this->connection('rabbitmq-with-options');
        $this->assertSame('application-x.test.failed', $this->callMethod($c, 'getFailedRoutingKey', ['test']));

        $c = $this->connection('rabbitmq-with-options-empty');
        $this->assertSame('test.failed', $this->callMethod($c, 'getFailedRoutingKey', ['test']));
    }

    public function testDeclareDeleteExchange(): void
    {
        /** @var $c RabbitMQQueue */
        $c = $this->connection();

        $name = Str::random();

        $this->assertFalse($c->isExchangeExists($name));

        $c->declareExchange($name);
        $this->assertTrue($c->isExchangeExists($name));

        $c->deleteExchange($name);
        $this->assertFalse($c->isExchangeExists($name));
    }

    public function testDeclareDeleteQueue(): void
    {
        /** @var $c RabbitMQQueue */
        $c = $this->connection();

        $name = Str::random();

        $this->assertFalse($c->isQueueExists($name));

        $c->declareQueue($name);
        $this->assertTrue($c->isQueueExists($name));

        $c->deleteQueue($name);
        $this->assertFalse($c->isQueueExists($name));
    }

    public function testQueueArguments(): void
    {
        $name = Str::random();

        /** @var $c RabbitMQQueue */
        $c = $this->connection();
        $actual = $this->callMethod($c, 'getQueueArguments', [$name]);
        $expected = [];
        $this->assertEquals(array_keys($expected), array_keys($actual));
        $this->assertEquals(array_values($expected), array_values($actual));

        $c = $this->connection('rabbitmq-with-options');
        $actual = $this->callMethod($c, 'getQueueArguments', [$name]);
        $expected = [
            'x-max-priority' => 20,
            'x-dead-letter-exchange' => 'failed-exchange',
            'x-dead-letter-routing-key' => sprintf('application-x.%s.failed', $name),
        ];

        $this->assertEquals(array_keys($expected), array_keys($actual));
        $this->assertEquals(array_values($expected), array_values($actual));

        $c = $this->connection('rabbitmq-with-options-empty');
        $actual = $this->callMethod($c, 'getQueueArguments', [$name]);
        $expected = [];

        $this->assertEquals(array_keys($expected), array_keys($actual));
        $this->assertEquals(array_values($expected), array_values($actual));
    }
}
