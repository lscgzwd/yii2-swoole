<?php
return [
    'vendorPath'     => dirname(dirname(__DIR__)) . '/vendor',
    'timeZone'       => 'PRC',
    'language'       => 'zh-CN',
    'sourceLanguage' => 'zh-CN',
    'runtimePath'    => '/data/logs',
    'components'     => [
        'db'           => [
            'class'               => 'yii\db\Connection',
            // 必须为dsn指定字符集，否则有多字节注入漏洞
            'dsn'                 => 'mysql:host=127.0.0.1;dbname=qiye;charset=utf8',
            'username'            => 'root',
            'password'            => '',
            'tablePrefix'         => 'op_',
            'charset'             => 'utf8',
            // 让数据库来执行prepare操作，而不是PDO驱动模拟 true 为模拟 false 为server
            // 'emulatePrepare'      => false,
            'enableSchemaCache'   => false,
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
        'request'      => [
            'enableCookieValidation' => false,
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
