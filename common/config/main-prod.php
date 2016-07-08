<?php

return [
    'vendorPath'     => dirname(dirname(__DIR__)) . '/vendor',
    'timeZone'       => 'PRC',
    'language'       => 'zh-CN',
    'sourceLanguage' => 'zh-CN',
    'components'     => [
        'db'           => [
            'class'               => 'yii\db\Connection',
            // 必须为dsn指定字符集，否则有多字节注入漏洞
            'dsn'                 => 'mysql:host=127.0.0.1;dbname=oss;port=3307;charset=utf8',
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
                'error'   => [
                    'class'          => 'yii\log\FileTarget',
                    'levels'         => ['error', 'warning'],
                    'logFile'        => '@runtime/yii-error.log',
                    'exportInterval' => 100,
                    'maxFileSize'    => 2048000,
                    'maxLogFiles'    => 10,
                    'rotateByCopy'   => false,
                    'fileMode'       => 0777,
                    'logVars'        => [],
                ],
                'info'    => [
                    'class'          => 'yii\log\FileTarget',
                    'levels'         => ['info', 'trace'],
                    'logFile'        => '@runtime/yii-app.log',
                    'exportInterval' => 100,
                    'maxFileSize'    => 2048000,
                    'maxLogFiles'    => 10,
                    'rotateByCopy'   => false,
                    'fileMode'       => 0777,
                    'logVars'        => [],
                ],
                'profile' => [
                    'class'          => 'yii\log\FileTarget',
                    'levels'         => ['profile'],
                    'logFile'        => '@runtime/profile.log',
                    'exportInterval' => 100,
                    'maxFileSize'    => 2048000,
                    'maxLogFiles'    => 10,
                    'rotateByCopy'   => false,
                    'fileMode'       => 0755,
                    'logVars'        => [],
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
            'port'     => 9100,
            'database' => 0,
        ],
    ],
];
