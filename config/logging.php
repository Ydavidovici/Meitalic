<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    'default' => env('LOG_CHANNEL', 'stack'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace'   => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    'channels' => [

        'stack' => [
            'driver'           => 'stack',
            'channels'         => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions'=> false,
        ],

        'single' => [
            'driver'   => 'single',
            'path'     => storage_path('logs/laravel.log'),
            'level'    => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver'   => 'daily',
            'path'     => storage_path('logs/laravel.log'),
            'level'    => env('LOG_LEVEL', 'debug'),
            'days'     => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        // … your existing channels (slack, papertrail, stderr, syslog, errorlog, null, emergency) …

        /*
         * NEW DEDICATED CHANNELS
         */
        'api' => [
            'driver'    => 'daily',
            'path'      => storage_path('logs/api.log'),
            'level'     => 'info',
            'days'      => 14,
            'replace_placeholders' => true,
        ],

        'error' => [
            'driver'    => 'daily',
            'path'      => storage_path('logs/error.log'),
            'level'     => 'error',
            'days'      => 30,
            'replace_placeholders' => true,
        ],

        'audit' => [
            'driver'    => 'daily',
            'path'      => storage_path('logs/audit.log'),
            'level'     => 'info',
            'days'      => 90,
            'replace_placeholders' => true,
        ],

    ],

];
