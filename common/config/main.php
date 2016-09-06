<?php
return [
    'vendorPath'     => dirname(dirname(__DIR__)) . '/vendor',
    'timeZone'       => 'PRC',
    'language'       => 'zh-CN',
    'sourceLanguage' => 'zh-CN',
    'runtimePath'    => '/data/logs',
    'components'     => [
        'db'                 => [
            'class'               => 'yii\db\Connection',
            'tablePrefix'         => 'jdb_',
            'charset'             => 'utf8',
            'enableSchemaCache'   => false,
            // Duration of schema cache.
            'schemaCacheDuration' => 3600,
            // Name of the cache component used to store schema information
            'schemaCache'         => 'schemaCache',
        ],
        'passport'           => [
            'class'               => 'yii\db\Connection',
            'tablePrefix'         => 'jdb_',
            'charset'             => 'utf8',
            'enableSchemaCache'   => false,
            // Duration of schema cache.
            'schemaCacheDuration' => 3600,
            // Name of the cache component used to store schema information
            'schemaCache'         => 'schemaCache',
        ],
        'cache'              => [
            'class' => 'yii\redis\Cache',
            'redis' => 'redis',
        ],
        'schemaCache'        => [
            'class' => 'yii\caching\FileCache',
        ],
        'request'            => [
            'enableCookieValidation' => false,
        ],
        'errorHandler'       => [
            'errorAction' => 'site/error',
        ],
        'response'           => [
            'charset' => 'UTF-8',
        ],
        'session'            => [
            'class' => 'yii\redis\Session',
        ],
        'formatter'          => [
            'class'           => 'yii\i18n\Formatter',
            'dateFormat'      => 'php:Y-m-d',
            'datetimeFormat'  => 'php:Y-m-d H:i:s',
            'timeFormat'      => 'php:H:i:s',
            'defaultTimeZone' => 'PRC',
        ],
        'redis'              => [
            'class' => 'yii\redis\Connection',
        ],
        'smtp'               => [
            'class'     => 'yii\swiftmailer\Mailer',
            'transport' => [
                'class'    => 'Swift_SmtpTransport',
                'host'     => 'smtp.qq.com',
                'username' => 'qq@qq.com',
                'password' => 'qq123456',
                'port'     => '25',
//                'encryption' => 'tls',
            ],
        ],
        'log'                => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets'    => [
                'logStash' => [
                    'class'          => 'common\vendor\log\LogstashFileTarget',
                    'categories'     => ['application*', 'yii*', 'apps*', 'api*', 'common*', 'console*', 'activity*'],
                    'logFile'        => '',
                    'logPath'        => '@runtime',
                    'logFileSuffix'  => '_logstash',
                    'logFileExt'     => '.log',
                    'logFilePrefix'  => '',
                    'levels'         => ['info', 'error', 'warning'],
                    'logVars'        => [],
                    'exportInterval' => 100,
                    'maxFileSize'    => 2048000,
                    'maxLogFiles'    => 10,
                    'rotateByCopy'   => false,
                    'fileMode'       => 0777,
                ],
                'email'    => [
                    'class'      => 'yii\log\EmailTarget',
                    'categories' => ['application', 'yii*', 'apps*', 'api*', 'common*', 'console*', 'activity*'],
                    'except'     => ['yii\web\HttpException:404'],
                    'levels'     => ['error'],
                    'mailer'     => 'smtp',
                    'logVars'    => [],
                    'message'    => [
                        'subject' => '日志报警',
                        'from'    => ['jdb_openplatform@jiedaibao.com'],
                        'to'      => ['lusc@jiedaibao.com', 'liubin@jiedaibao.com', 'xingjq@jiedaibao.com', 'xiedl@jiedaibao.com', 'yanxr@jiedaibao.com'],
                    ],
                ],
            ],
        ],
    ],
];
