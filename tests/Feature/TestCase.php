<?php

namespace Laborer\LaravelRabbitMQ\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Laborer\LaravelRabbitMQ\Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testPublish()
    {
        $msg_data = ['order_id'=>mt_rand(1000, 9999),'price'=>20.55,'remark'=>'测试数据'];
        $msg_id = Artisan::call('rabbitmq:produce', [
            'routing-key' => 'order.create',
            'msg-data'    => $msg_data,
        ]);

        $this->assertTrue((bool)$msg_id);
    }
}
