<?php

namespace Laborer\LaravelRabbitMQ\Tests\Functional;

use Laborer\LaravelRabbitMQ\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('rabbitmq.routing_keys', [
            'order.create' => '创建订单',
            'stock.update' => '更新库存',
        ]);
    }

    /**
     * @param $object
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    protected function callMethod($object, string $method, array $parameters = [])
    {
        try {
            $className = get_class($object);
            $reflection = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new \Exception($e->getMessage());
        }

        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
