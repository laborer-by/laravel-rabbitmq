<?php

namespace Laborer\LaravelRabbitMQ\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laborer\LaravelRabbitMQ\Base\RabbitMQConnect;
use Laborer\LaravelRabbitMQ\Base\RabbitMQService;

/**
 * Class PublishCommand
 * @package Laborer\LaravelRabbitMQ\Console
 *
 * php artisan rabbitmq:produce order.create --test=1
 */
class ProduceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:produce
                            {routing-key : The routing_key to publish a message} 
                            {msg-data?} 
                            {vhost? : The vhost of we choose to connect}
                            {--retry=3 : The number of times to retry}
                            {--test=0 : Whether to test or not}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Produce messages';

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
            $routing_key = $this->argument('routing-key'); // routing_key
            $msg_data = $this->argument('msg-data');  // 消息主体数据，格式为 array

            $vhost = $this->argument('vhost');
            if (!$vhost) $vhost = config('rabbitmq.vhost');

            $service = new RabbitMQService(new RabbitMQConnect($vhost));
            $service->validateRoutingKey($routing_key);

            // 如果是进行测试，就用测试数据
            if ((bool) $this->option('test')) $msg_data = ['order_id'=>1001,'price'=>20.55,'remark'=>'测试数据'];
            if (!$msg_data) throw new Exception('msg-data is required');
            if (!is_array($msg_data)) throw new Exception('msg-data must be array');

            $source = env('SESSION_DOMAIN', '');
            $msg_id = $service->publishMsg(
                $routing_key,
                $msg_data,
                $source,
                null,
                $this->option('retry')
            );

            // 消息发送成功后，输出提示信息，记录到日志
            $info = sprintf($this->description.' success [%s]', $msg_id);
            $this->info($info);
            return $msg_id;
        } catch (Exception $e) {
            $error = sprintf($this->description.' fail. %s', $e->getMessage());
            $this->error($error);
        }

        /** Demo
        \Artisan::call('rabbitmq:produce', [
            'routing-key' => 'order.create',
            'msg-data'    => ['order_id'=>1001,'price'=>20.55,'remark'=>'测试数据'],
        ]);
         **/
    }
}
