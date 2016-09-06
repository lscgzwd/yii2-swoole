<?php
return [
    'components' => [
        'db'       => [
            // must set charset for security
            'dsn'      => 'mysql:host=127.0.0.1;dbname=demo;charset=utf8',
            'username' => 'root',
            'password' => 'ENTER@123.com',
        ],
        'passport' => [
            // must set charset for security
            'dsn'      => 'mysql:host=127.0.0.1;dbname=user;port=3310;charset=utf8',
            'username' => 'user',
            'password' => 'user',
        ],
        'log'      => [
            'targets' => [
                'email' => [
                    'message' => [
                        'subject' => 'beta log alert',
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
