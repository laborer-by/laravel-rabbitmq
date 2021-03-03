<?php

namespace Laborer\LaravelRabbitMQ\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laborer\LaravelRabbitMQ\Base\RabbitMQConnect;
use Laborer\LaravelRabbitMQ\Base\RabbitMQService;

/**
 * Class ConsumeCommand
 * @package Laborer\LaravelRabbitMQ\Console
 *
 * php artisan rabbitmq:consume
 */
class ConsumeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:consume
                            {vhost? : The vhost of we choose to connect}
                            {--timeout=0 : The number of seconds a child process can run}
                            {--retry=3 : Number of times to retry}
                            {--prefetch_count=1 : The number of messages every time to fetch}
                            {--queue= : The names of the queues to work}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume messages';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $vhost = $this->argument('vhost');
            if (!$vhost) $vhost = config('rabbitmq.vhost');

            $channel = new RabbitMQConnect($vhost);
            $service = new RabbitMQService($channel);

            // 回调函数
            $callback = function ($msg) use ($service) {
                try {
                    // 消息内容
                    $body = $msg->body;
                    $body = json_decode($body, true);

                    $msg_id = $body['msg_id'];
                    $routing_key = $body['routing_key'];
                    $info = sprintf('Received msg_id:%s', $msg_id);
                    $this->info($info);

                    $service->checkLock($msg_id);

                    // 处理消息的业务逻辑
                    $service->consumeHandler($body, (int)$this->option('retry'), 1);

                    // 发送处理成功的回执
                    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

                } catch (Exception $e) {
                    $requeue = false;
                    // 拒绝消息，进入死信队列，人工介入处理
                    $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], $requeue);
                    // 记录异常
                    $error = sprintf($this->description.' fail. %s', $e->getMessage());
                    $this->error($error);

                    $service->delLock($msg_id);
                }
            };

            // 每次预获取的消息数量
            $channel->basic_qos((int)$this->option('prefetch_count'));

            // 启动消费者，开启手动回执
            $channel->basic_consume($this->option('queue'), $callback, (int) $this->option('timeout'));

            // 最后，关闭 channel 和 connection
            $channel->close();
        } catch (Exception $e) {
            $error = sprintf($this->description.' fail. %s', $e->getMessage());
            $this->error($error);
            // 特殊异常时，退出进程，以便消费者守护进程自动重启；Unacked 的消息，会重新回到队列的头部，变为 Ready。
            exit(1);
        }
    }
}
