<?php

namespace Laborer\LaravelRabbitMQ\Base;

use Exception;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQService
{
    protected $channel;
    protected $lock_prefix = 'rabbitmq:lock:';

    public function __construct(RabbitMQConnect $channel)
    {
        $this->channel = $channel;
    }

    /**
     * Verify whether the routing_key is valid
     * 验证路由键是否有效
     * @param $routing_key
     * @return bool
     */
    public function validateRoutingKey($routing_key)
    {
        $routing_keys = $this->channel->routing_keys;
        if (in_array($routing_key, array_keys($routing_keys))) return true;
        throw new Exception(sprintf('routing_key[%s] is invalid', $routing_key));
    }

    /**
     * get the value of a routing_key
     * @param $msg_type
     * @return mixed
     */
    public function getRoutingValue($routing_key)
    {
        $routing_keys = $this->channel->routing_keys;
        return $routing_keys[$routing_key];
    }

    /**
     * Generate a unique ID
     * @return string
     */
    public function genUniqueId()
    {
        $snowflake = new Snowflake();
        return $snowflake->id();
    }

    /**
     * 将消息记录到数据表，并生成消息id
     * @param $routing_key
     * @param array $msg_data
     * @param string $source
     * @param string $extra_data
     * @return array
     */
    public function saveMsg($routing_key, array $msg_data = [], $source = '', $extra_data = null)
    {
        $msg_id = $this->genUniqueId();
        // 将待发送的消息记录到数据表
        $payload = [
            'msg_id'      => $msg_id,
            'routing_key' => $routing_key,
            'source'      => $source ?: '',
            'msg_data'    => json_encode($msg_data, JSON_UNESCAPED_UNICODE),
            'extra_data'  => $extra_data ?: '',
            'created_at'  => date('Y-m-d H:i:s')
        ];
        DB::table('tmp_rabbitmq_msg')->insert($payload);
        return $payload;
    }

    /**
     * 发布（生产）消息
     * @param $routing_key
     * @param array $msg_data
     * @param string $source
     * @param string $extra_data
     * @param int $retry
     * @param int $sleep
     * @return mixed
     * @throws Exception
     */
    public function publishMsg($routing_key, array $msg_data = [], $source = '', $extra_data = null, $retry = 3, $sleep = 1)
    {
        try {
            $payload = $this->saveMsg($routing_key, $msg_data, $source, $extra_data);

            $this->channel->basic_publish($payload, $routing_key);

            // 最后，关闭 channel 和 connection
            $this->channel->close();

            return $payload['msg_id'];
        } catch (\Exception $e) {
            $error = sprintf('[msg_id:%s,routing_key:%s] ' . $e->getMessage(), $payload['msg_id'], $routing_key);
            if ($retry--) {
                sleep($sleep); // 阻塞几秒后，再重试
                self::publishMsg($routing_key, $msg_data, $source, $extra_data, $retry, $sleep);
            } else {
                throw new Exception($error);
            }
        }
    }

    public function checkLock($msg_id)
    {
        $lockKey = $this->lock_prefix.$msg_id;
        if (!Redis::set($lockKey, 1, "nx", "ex", 3600)) throw new Exception(sprintf('Do not repeat consumption.msg_id.%s', $msg_id));
    }

    /**
     * Release the lock
     * @param $msg_id
     */
    public function delLock($msg_id)
    {
        $lockKey = $this->lock_prefix.$msg_id;
        Redis::del($lockKey);
    }

    /**
     * 消息处理程序
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

            switch ($routing_key) {
                case 'order.create': // todo
                    break;

                default:
                    throw new \Exception('msg_type异常');
                    break;
            }
        } catch (\Exception $e) {
            $error = sprintf('[msg_id:%s,routing_key:%s] ' . $e->getMessage(), $msg_id, $routing_key);
            if ($retry--) {
                echo $error.PHP_EOL;
                sleep($sleep); // 阻塞几秒后，再重试
                self::consumeHandler($body, $retry, $sleep);
            } else {
                throw new \Exception($error);
            }
        }
    }

    /**
     * Get the headers from the rabbitMQ message.
     * @param $msg
     * @return null
     */
    public function getMsgHeaders($msg)
    {
        if (! $headers = Arr::get($msg->get_properties(), 'application_headers')) {
            return null;
        }
        return $headers->getNativeData();
    }
}
