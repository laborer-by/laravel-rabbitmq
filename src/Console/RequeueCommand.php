<?php

namespace Laborer\LaravelRabbitMQ\Console;

use Exception;
use Illuminate\Console\Command;
use Laborer\LaravelRabbitMQ\Base\RabbitMQConnect;
use Laborer\LaravelRabbitMQ\Base\RabbitMQService;
use Illuminate\Support\Facades\DB;

/**
 * Class RequeueCommand
 * @package Laborer\LaravelRabbitMQ\Console
 *
 * php artisan rabbitmq:requeue 209531945275621376 209731905241743360
 */
class RequeueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:requeue
                            {msg-ids?* : The message ID to be requeue}
                            {--vhost= : The vhost of we choose to connect}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Requeue messages';

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

            $service = new RabbitMQService(new RabbitMQConnect($vhost));

            $msg_ids = $this->argument('msg-ids');
            $count = count($msg_ids);
            $count_success = 0;

            $this->output->progressStart($count);
            foreach ($msg_ids as $msg_id)
            {
                try {
                    $v = DB::table($service->rabbitmq_msg_table)->where('id', $msg_id)->first();
                    if(!$v) throw new \Exception('No records');

                    $routing_key = $v->routing_key;
                    $service->validateRoutingKey($routing_key);
                    $service->requeueMsg($v, $routing_key);

                    $count_success++;
                } catch (Exception $e) {
                    $error = sprintf('[msg_id:%s] ' . $e->getMessage(), $msg_id);
                    $this->error($error);
                }
                $this->output->progressAdvance();
            }
            $this->output->progressFinish();

            $info = sprintf($this->description.' success [%s]', $count_success);
            $this->info($info);
        } catch (Exception $e) {
            $error = sprintf($this->description.' fail. %s', $e->getMessage());
            $this->error($error);
        }
    }
}
