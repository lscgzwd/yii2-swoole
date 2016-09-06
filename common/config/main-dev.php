<?php
return [
    'components' => [
        'db'       => [
            'dsn'      => 'mysql:host=127.0.0.1;dbname=demo;port=3306;charset=utf8',
            'username' => 'root',
            'password' => 'root',
        ],
        'passport' => [
            'dsn'      => 'mysql:host=127.0.0.1;dbname=user;port=3310;charset=utf8',
            'username' => 'user',
            'password' => 'user',
        ],
        'log'      => [
            'targets' => [
                'profile' => [
                    'class'          => 'yii\log\FileTarget',
                    'levels'         => ['profile', 'info'],
                    'logFile'        => '@runtime/' . date('Ymd') . '_profile.log',
                    'exportInterval' => 100,
                    'maxFileSize'    => 2048000,
                    'maxLogFiles'    => 10,
                    'rotateByCopy'   => false,
                    'fileMode'       => 0777,
                    'logVars'        => [],
                ],
                'email'   => [
                    'message' => [
                        'subject' => 'dev log alert',
                    ],
                ],
            ],
        ],
        'redis'    => [
            'hostname' => '127.0.0.1',
            'port'     => 6379,
            'database' => 0,
        ],
    ],
];
