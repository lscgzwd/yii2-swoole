<?php
return [
    'vendorPath'     => dirname(dirname(__DIR__)) . '/vendor',
    'timeZone'       => 'PRC',
    'language'       => 'zh-CN',
    'sourceLanguage' => 'zh-CN',
    'components'     => [
        'db'           => [
            'class'               => 'yii\db\Connection',
            'dsn'                 => 'mysql:host=127.0.0.1;dbname=qiye;port=3306',
            'username'            => 'root',
            'password'            => '',
            'tablePrefix'         => 'jdb_',
            'charset'             => 'utf8',
            'enableSchemaCache'   => false,
            // Duration of schema cache.
            'schemaCacheDuration' => 3600,
            // Name of the cache component used to store schema information
            'schemaCache'         => 'schemaCache',
        ],
        'passport'     => [
            'class'               => 'yii\db\Connection',
            'dsn'                 => 'mysql:host=127.0.0.1;dbname=passport;port=3310',
            'username'            => 'root',
            'password'            => '',
            'tablePrefix'         => 'jdb_',
            'charset'             => 'utf8',
            'enableSchemaCache'   => true,
            // Duration of schema cache.
            'schemaCacheDuration' => 3600,
            // Name of the cache component used to store schema information
            'schemaCache'         => 'schemaCache',
        ],
        'cache'        => [
            'class' => 'yii\redis\Cache',
            'redis' => 'redis',
        ],
        'schemaCache'  => [
            'class' => 'yii\caching\FileCache',
        ],
        'log'          => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets'    => [
                [
                    'class'   => 'yii\log\FileTarget',
                    'levels'  => ['error', 'warning'],
                    'logFile' => '@runtime/error.log',
                ],
                [
                    'class'  => 'yii\log\FileTarget',
                    'levels' => ['info', 'trace'],
                ],
                // 请求记录日志
                [
                    'class'       => 'yii\log\FileTarget',
                    'levels'      => ['info', 'error', 'trace', 'warning'],
                    'categories'  => ['request'],
                    'logFile'     => '@runtime/request.log',
                    'logVars'     => [], // 关闭输入参数日志, 手动记录
                    'maxFileSize' => 1024 * 4,
                    'maxLogFiles' => 2000,
                ],
                // 输出记录日志
                [
                    'class'       => 'yii\log\FileTarget',
                    'levels'      => ['info', 'error', 'trace', 'warning'],
                    'categories'  => ['response'],
                    'logFile'     => '@runtime/response.log',
                    'logVars'     => [], // 关闭输入参数日志, 手动记录
                    'maxFileSize' => 1024 * 4,
                    'maxLogFiles' => 2000,
                ], [
                    'class'          => 'apps\logstash\LogstashFileTarget',
                    'categories'     => ['activity-*'],
                    'logFile'        => '@runtime/' . date("Ymd") . '_logstash.log',
                    //'logFile' => '@runtime/logs/' . date("Ymd"). '_logstash.log',
                    'levels'         => ['info', 'error', 'warning'],
                    'logVars'        => [],
                    'exportInterval' => 100,
                    'maxFileSize'    => 2048000,
                    'maxLogFiles'    => 365,
                    'rotateByCopy'   => false,
                    'fileMode'       => 0755,
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'response'     => [
            'charset' => 'UTF-8',
        ],
        'session'      => [
            'class' => 'yii\redis\Session',
        ],
        'formatter'    => [
            'class'           => 'yii\i18n\Formatter',
            'dateFormat'      => 'php:Y-m-d',
            'datetimeFormat'  => 'php:Y-m-d H:i:s',
            'timeFormat'      => 'php:H:i:s',
            'defaultTimeZone' => 'PRC',
        ],
        'redis'        => [
            'class'    => 'yii\redis\Connection',
            'hostname' => '127.0.0.1',
            'port'     => 6379,
            'database' => 0,
        ],
    ],
];
