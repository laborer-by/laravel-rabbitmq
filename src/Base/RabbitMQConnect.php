<?php

namespace Laborer\LaravelRabbitMQ\Base;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQConnect
{
    protected $exchange;
    protected $queue;

    protected $dlx_exchange;
    protected $dlx_queue;
    protected $dlx_binding_key;
    protected $connection;
    protected $channel;

    public $routing_keys;

    protected $cache_exchange;
    protected $cache_queue;
    protected $delay_exchange;
    protected $delay_queue;

    /**
     * Establish a connection and channel
     * RabbitMQConnect constructor.
     * @param $vhost
     */
    public function __construct($vhost)
    {
        $host = config('rabbitmq.host');
        $port = config('rabbitmq.port');
        $user = config('rabbitmq.user');
        $password = config('rabbitmq.password');

        $options = config('rabbitmq.connections.'.$vhost.'.options');
        $this->exchange = $options['exchange'];  // 交换机名称
        $this->queue = $options['queue']; // 队列名称

        $this->dlx_exchange = $options['dlx_exchange']; // 死信交换机
        $this->dlx_queue = $options['dlx_queue']; // 死信队列
        $this->dlx_binding_key = '#'; //死信队列的 binding_key

        $this->routing_keys = config('rabbitmq.routing_keys');

        // 连接到指定的 RabbitMQ 服务器（Broker），并创建 channel，设置 vhost 和 heartbeat
        // disable heartbeat when not configured, so long-running tasks will not fail
        $heartbeat = $options['heartbeat']; // 心跳检测的时间
        $this->connection = new AMQPStreamConnection(
            $host,
            $port,
            $user,
            $password,
            $vhost,
            $insist = false,
            $login_method = 'AMQPLAIN',
            $locale_response = null,
            $locale = 'en_US',
            $connection_timeout = 3,
            $read_write_timeout = 3,
            $context = null,
            $keepalive = false,
            $heartbeat = 120
        );

        // 创建信道
        $this->channel = $this->connection->channel();
        return $this->channel;
    }

    /**
     * 声明创建交换机（幂等）
     * @param string $exchange
     * @param string $exchange_type
     * @param bool $durable
     * @param bool $auto_delete
     * @return mixed|null
     */
    public function exchange_declare($exchange='', $exchange_type='topic', $durable=true, $auto_delete=false)
    {
        $exchange = $exchange ? : $this->exchange;
        return $this->channel->exchange_declare($exchange, $exchange_type, false, true, false);
    }

    /**
     * Delete an exchange
     * @param $exchange
     * @param bool $if_unused
     * @return mixed|null
     */
    public function exchange_delete($exchange, $if_unused = false)
    {
        return $this->channel->exchange_delete($exchange, $if_unused);
    }

    /**
     * Declares queue, creates if needed
     * 方法是幂等的，即只有当指定的 queue 不存在时才会创建
     * @param string $queue
     * @param bool $durable
     * @param bool $auto_delete
     * @param bool $enable_dlx 是否开启死信功能
     * @return mixed|null
     */
    public function queue_declare($queue='', $durable=true, $auto_delete=false, $enable_dlx=true)
    {
        $queue = $queue ? : $this->queue;
        if ($enable_dlx) {
            $arguments = [
                'x-dead-letter-exchange' => $this->dlx_exchange,
            ];
            $arguments = new AMQPTable($arguments);

            // 声明创建业务队列，同时指定死信交换机
            return $this->channel->queue_declare($queue, false, $durable, false, $auto_delete, false, $arguments);
        } else {
            return $this->channel->queue_declare($queue, false, $durable, false, $auto_delete, false);
        }
    }

    /**
     * Bind queue to an exchange
     * 绑定业务队列到业务交换机，队列的 binding_key 可以有多个
     * @param string $queue
     * @param string $exchange
     * @param $binding_key
     * @return mixed|null
     */
    public function queue_bind($queue='', $exchange='', $binding_key)
    {
        $queue = $queue ? : $this->queue;
        $exchange = $exchange ? : $this->exchange;
        return $this->channel->queue_bind($queue, $exchange, $binding_key);
    }

    /**
     * Deletes a queue
     * @param string $queue
     * @param bool $if_unused
     * @param bool $if_empty
     * @param bool $nowait
     * @param null $ticket
     */
    public function queue_delete($queue = '', $if_unused = false, $if_empty = false, $nowait = false, $ticket = null)
    {
        $this->channel->queue_delete($queue, $if_unused, $if_empty, false, null);
    }

    /**
     * Declare Dead-Letter-Exchange,queue and establish a binding relationship
     * 声明死信交换机、死信队列，并建立它们的绑定关系
     */
    public function dlx_declare()
    {
        $this->channel->exchange_declare($this->dlx_exchange, 'topic', false, true, false);
        $this->channel->queue_declare($this->dlx_queue, false, true, false, false);
        $this->channel->queue_bind($this->dlx_queue, $this->dlx_exchange, $this->dlx_binding_key);
    }

    /**
     * 指定每次预获取消息的数量
     */
    public function basic_qos($prefetch_count=1)
    {
        $prefetch_count = max($prefetch_count, 1);
        $this->channel->basic_qos(null, $prefetch_count, null);
    }

    /**
     * 启动消费者，开启手动回执
     * @param $queue
     * @param $callback
     * @param int $timeout The number of seconds a child process can run, 0 means no limit
     * @throws \ErrorException
     */
    public function basic_consume($queue, $callback, $timeout=0)
    {
        $queue = $queue ? : $this->queue;
        $this->channel->basic_consume($queue, '', false, false, false, false, $callback);
        while ($this->channel->is_consuming()) { // 如果消费者正在运行
            $this->channel->wait(null, false, $timeout); // 等待
        }
    }

    /**
     * 启动缓存队列的消费者，开启手动回执
     * @param $callback
     */
    public function basic_consume_cache($callback)
    {
        $this->channel->basic_consume($this->cache_queue, '', false, false, false, false, $callback);
    }

    /**
     * 发布（生产）消息，将消息发送给交换机
     * @param $payload
     * @param string $routing_key
     * @return mixed
     */
    public function basic_publish($payload = [], $routing_key = '', $attempts=0)
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $properties = [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'content_type'  => 'application/json',
//            'message_id'    => $payload['msg_id'],
        ];
        $msg = new AMQPMessage($body, $properties);

        $msg->set('application_headers', new AMQPTable([
            'laravel' => [
                'attempts' => $attempts,
            ],
        ]));

        // 将消息发送给交换机
        $this->channel->basic_publish($msg, $this->exchange, $routing_key);
        return $payload['msg_id'];
    }

    /**
     * Close channel and connection
     * @throws \Exception
     */
    public function close()
    {
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * 声明创建延迟消息队列的前置队列（缓存队列）
     */
    public function cache_declare()
    {
        $arguments = [
            'x-dead-letter-exchange'    => $this->delay_exchange,
        ];
        $arguments = new AMQPTable($arguments);

        $this->channel->exchange_declare($this->cache_exchange, 'fanout', false, true, false);
        // 声明前置队列，同时指定死信交换机
        $this->channel->queue_declare($this->cache_queue, false, true, false, false, false, $arguments);
        $this->channel->queue_bind($this->cache_queue, $this->cache_exchange);
    }

    /**
     * 声明创建延迟消息队列的后置队列（延迟后的队列）
     */
    public function delay_declare()
    {
        $arguments = [
            'x-dead-letter-exchange'    => $this->dlx_exchange,
        ];
        $arguments = new AMQPTable($arguments);

        $this->channel->exchange_declare($this->delay_exchange, 'topic', false, true, false);

        // 声明创建业务队列，同时指定死信交换机
        $this->channel->queue_declare($this->delay_queue, false, true, false, false, false, $arguments);
    }

    /**
     * 绑定业务队列到业务交换机，队列的 binding_key 可以有多个
     * @param $binding_key
     */
    public function queue_bind_delay($binding_key)
    {
        $this->channel->queue_bind($this->delay_queue, $this->delay_exchange, $binding_key);
    }

    /**
     * 发布延迟消息，将消息发送给交换机，并将消息记录到数据表
     * // todo 延迟消息相关的功能需要优化调试
     * @param $msg_type
     * @param $payload
     * @param $expiration 有效期（延迟时间），单位：秒
     * @param int $msg_id
     * @return int
     */
    public function basic_publish_delay($msg_type, $payload, $expiration, $msg_id=0)
    {
        if ($expiration<1) throw new Exception('过期时间必须大于0');

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // 注意：这里是针对每一条消息单独设置过期时间
        $properties = [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'content_type'  => 'application/json',
            'expiration'    => strval($expiration*1000)
        ];
        $msg = new AMQPMessage($body, $properties);

        // 将消息发送给交换机
        $this->channel->basic_publish($msg, $this->cache_exchange, $msg_type);
        return $msg_id;
    }

    /**
     * 启动消费者，开启手动回执
     * @param $callback
     * @throws \ErrorException
     */
    public function basic_consume_delay($callback)
    {
        $this->channel->basic_consume($this->delay_queue, '', false, false, false, false, $callback);

        while ($this->channel->is_consuming()) { // 如果消费者正在运行
            $this->channel->wait(); // 等待
        }
    }
}
