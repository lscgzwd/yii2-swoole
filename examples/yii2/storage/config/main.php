<?php
/**
 * storage
 * User: lushuncheng<admin@lushuncheng.com>
 * Date: 2017/3/6
 * Time: 12:27
 * @link https://github.com/lscgzwd
 * @copyright Copyright (c) 2017 Lu Shun Cheng (https://github.com/lscgzwd)
 * @licence http://www.apache.org/licenses/LICENSE-2.0
 * @author Lu Shun Cheng (lscgzwd@gmail.com)
 */
return [
    'id'                  => 'app-storage',
    'basePath'            => dirname(__DIR__),
    'bootstrap'           => ['log'],
    'controllerNamespace' => 'storage\controllers',
    'vendorPath'          => dirname(dirname(__DIR__)) . '/vendor',
    'timeZone'            => 'PRC',
    'language'            => 'zh-CN',
    'sourceLanguage'      => 'zh-CN',
    'defaultRoute'        => 'demo/index',
    'runtimePath'         => '/tmp/storage',
    'modules'             => [
        'v1' => [
            'class' => 'storage\modules\v1\Module',
        ],
    ],
    'components'          => [
        'db'          => [
            'class' => 'yiiswoole\db\Connection',
        ],
        'request'     => [
            'enableCsrfValidation'   => false,
            'enableCookieValidation' => false,
            'class'                  => 'yiiswoole\web\Request',
        ],
        'urlManager'  => [
            'enablePrettyUrl' => true,
            'showScriptName'  => false,
            'cache'           => 'schemaCache',
            'rules'           => [
                'v1/demo/<action:[\w-]+>' => 'v1/demo/<action>',
                'PUT v1/<uri:.+>'         => 'v1/storage/put',
                'DELETE v1/<uri:.+>'      => 'v1/storage/delete',
                'GET v1/<uri:.+>'         => 'v1/storage/get',
                'HEAD v1/<uri:.+>'        => 'v1/storage/head',
                'POST v1/<uri:.+>'        => 'v1/storage/post',
            ],
        ],
        'cache'       => [
            'class'     => 'yii\redis\Cache',
            'redis'     => 'redis',
            'keyPrefix' => 'jdb_enterprise:cache',
        ],
        'schemaCache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'response'    => [
            'charset' => 'UTF-8',
            'class'   => 'yiiswoole\web\Response',
            'format'  => 'json',
        ],
        'session'     => [
            'class'     => 'yiiswoole\redis\Session',
            'keyPrefix' => 'jdb_enterprise:session',
        ],
        'formatter'   => [
            'class'           => 'yii\i18n\Formatter',
            'dateFormat'      => 'php:Y-m-d',
            'datetimeFormat'  => 'php:Y-m-d H:i:s',
            'timeFormat'      => 'php:H:i:s',
            'defaultTimeZone' => 'PRC',
        ],
        'redis'       => [
            'class'    => 'yiiswoole\redis\Connection',
            'hostname' => '127.0.0.1',
            'port'     => 6379,
            'database' => 0,
        ],
        'smtp'        => [
            'class'     => 'yii\swiftmailer\Mailer',
            'transport' => [
                'class'    => 'Swift_SmtpTransport',
                'host'     => 'smtp.google.com',
                'username' => 'lscgzwd@gmail.com',
                'password' => '123456',
                'port'     => '25',
            ],
        ],
        'log'         => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets'    => [
                'logStash' => [
                    'class'          => 'yiilog\LogstashFileTarget',
                    'categories'     => ['application*', 'yii*', 'apps*', 'api*', 'common*', 'console*', 'activity*'],
                    'logFile'        => '',
                    'logPath'        => '@runtime',
                    'logFileSuffix'  => '_logstash',
                    'logFileExt'     => '.log',
                    'logFilePrefix'  => 'storage_',
                    'levels'         => ['info', 'error', 'warning'],
                    'logVars'        => [],
                    'exportInterval' => 100,
                    'maxFileSize'    => 2048000,
                    'maxLogFiles'    => 10,
                    'rotateByCopy'   => false,
                    'fileMode'       => 0777,
                ],
                'email'    => [
                    'class'      => 'yiilog\EmailTarget',
                    'categories' => ['application', 'yii*', 'apps*', 'storage*', 'common*', 'console*', 'activity*'],
                    'except'     => ['yii\web\HttpException:404'],
                    'levels'     => ['error'],
                    'mailer'     => 'smtp',
                    'logVars'    => [],
                    'message'    => [
                        'subject' => '日志报警',
                        'from'    => ['lscgzwd@gmail.com'],
                        'to'      => ['lscgzwd@gmail.com'],
                    ],
                ],
            ],
        ],
    ],
];
