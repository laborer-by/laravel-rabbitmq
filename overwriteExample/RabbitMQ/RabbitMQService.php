<?php

namespace App\Console\Commands\RabbitMQ;

use Exception;
use Laborer\LaravelRabbitMQ\Base\RabbitMQService as BaseRabbitMQService;

class RabbitMQService extends BaseRabbitMQService
{
    /**
     * 消息处理程序
     * // todo ... 这个函数需要被重写
     * @param $body
     * @param int $retry
     * @param int $sleep
     * @throws Exception
     */
    public function consumeHandler($body, $retry=3, $sleep=1)
    {
        try {
            $msg_id   = $body['msg_id'];
            $routing_key = $body['routing_key'];
            $msg_data = $body['msg_data'];
            $msg_data = json_decode($msg_data, true);

            switch ($routing_key) {  // 根据 routing_key 区分场景来消费
                case 'order.create': // todo ... 具体的消费处理逻辑
                    var_dump($msg_data);
                    break;

                default:
                    throw new \Exception('Routing_key is invalid!');
                    break;
            }
        } catch (Exception $e) {
            $error = sprintf('[msg_id:%s,routing_key:%s] ' . $e->getMessage(), $msg_id, $routing_key);
            if ($retry--) {
                echo $error.PHP_EOL;
                sleep($sleep); // 阻塞几秒后，再重试
                self::consumeHandler($body, $retry, $sleep);
            } else {
                throw new Exception($error);
            }
        }
    }
}
