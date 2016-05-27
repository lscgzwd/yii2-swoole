<?php
return [
    'components' => [
        'db'           => [
            'class' => 'swoole\yii\db\Connection',
        ],
        'request'      => [
            'class'                  => 'swoole\yii\web\Request',
            'enableCookieValidation' => false,
        ],
        'errorHandler' => [
            'class'       => 'swoole\yii\web\ErrorHandler',
            'errorAction' => 'site/error',
        ],
        'response'     => [
            'class'   => 'swoole\yii\web\Response',
            'charset' => 'UTF-8',
        ],
        'redis'        => [
            'class' => 'swoole\yii\redis\Connection',
        ],
    ],
];
