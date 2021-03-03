<?php

/**
 * This is an example of RabbitMQ connection configuration.
 * You need to set proper values in `.env`.
 */
return [
    'host'     => env('RABBITMQ_HOST', '127.0.0.1'),
    'port'     => env('RABBITMQ_PORT', 5672),
    'user'     => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost'    => env('RABBITMQ_VHOST'),  // default vhost

    'connections' => [
        // key = vhost
        env('RABBITMQ_VHOST') => [
            'options' => [
                'queue'                => env('SESSION_DOMAIN') . '.queue', // default queue
                'exchange'             => env('RABBITMQ_VHOST') . '.exchange', // default exchange
                'heartbeat'            => 120, // The number of seconds the heartbeat check
                'dlx_queue'            => env('RABBITMQ_VHOST') . '.dlx.queue',
                'dlx_exchange'         => env('RABBITMQ_VHOST') . '.dlx.exchange',
            ]
        ],
    ],

    // List of available routing_keys to publish messages
    'routing_keys' => [
        'order.create' => '创建订单',
    ],
];
