# laravel-rabbitmq

[![Latest Stable Version](https://poser.pugx.org/laborer-by/laravel-rabbitmq/v)](//packagist.org/packages/laborer-by/laravel-rabbitmq) [![Total Downloads](https://poser.pugx.org/laborer-by/laravel-rabbitmq/downloads)](//packagist.org/packages/laborer-by/laravel-rabbitmq) [![Latest Unstable Version](https://poser.pugx.org/laborer-by/laravel-rabbitmq/v/unstable)](//packagist.org/packages/laborer-by/laravel-rabbitmq) [![License](https://poser.pugx.org/laborer-by/laravel-rabbitmq/license)](//packagist.org/packages/laborer-by/laravel-rabbitmq)

RabbitMQ driver for Laravel.

## Installation

(1) Install this package via composer using:

```bash
composer require laborer-by/laravel-rabbitmq
```

(2) Add these properties to `.env` with proper values:

```
;RABBITMQ dev
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=oc
```

(3) Create a table:

```sql
CREATE TABLE `tmp_rabbitmq_msg` (
  `msg_id` char(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '消息的唯一id',
  `routing_key` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'routing_key 消息的路由键',
  `source` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '消息的来源',
  `msg_data` mediumtext COLLATE utf8_unicode_ci NOT NULL COMMENT '消息的主体数据',
  `extra_data` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '额外的数据',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  UNIQUE KEY `index_msg_id` (`msg_id`) USING BTREE,
  KEY `index_routing_key` (`routing_key`) USING BTREE,
  KEY `index_created_at` (`created_at`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='rabbitmq消息表';
```

### Config

Register `LaravelRabbitMQServiceProvider` via config/app.php:

```
'providers' => [
    // others ...

    Laborer\LaravelRabbitMQ\LaravelRabbitMQServiceProvider::class,
],
```

To publish the config file, run the following:

```
php artisan vendor:publish --provider="Laborer\LaravelRabbitMQ\LaravelRabbitMQServiceProvider"
```

## Changelog

You will find a complete changelog history within the [CHANGELOG](CHANGELOG.md) file.

## Testing

Run tests with PHPUnit:

```bash
vendor/bin/phpunit
```

OR

```bash
composer test
```
