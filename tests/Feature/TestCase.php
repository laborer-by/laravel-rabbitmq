<?php

namespace Laborer\LaravelRabbitMQ\Tests\Feature;

use Illuminate\Support\Str;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use Laborer\LaravelRabbitMQ\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @throws AMQPProtocolChannelException
     */
    public function setUp()
    {
        parent::setUp();
    }
}
