<?php
return [
    'components' => [
        'db'       => [
            'class' => 'swoole\yii\db\Connection',
        ],
        'request'  => [
            'class'                  => 'swoole\yii\web\Request',
            'enableCookieValidation' => false,
        ],
        'response' => [
            'class'   => 'swoole\yii\web\Response',
            'charset' => 'UTF-8',
        ],
        'redis'    => [
            'class' => 'swoole\yii\redis\Connection',
        ],
        'session'  => [
            'class' => 'swoole\yii\redis\Session',
        ],
    ],
];
