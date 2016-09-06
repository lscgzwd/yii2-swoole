<?php
return [
    'components' => [
        'db'       => [
            // must set charset for security
            'dsn'               => 'mysql:host=127.0.0.1;dbname=demo;port=13307;charset=utf8',
            'username'          => 'root',
            'password'          => 'root',
            'enableSchemaCache' => true,
        ],
        'passport' => [
            // must set charset for security
            'dsn'      => 'mysql:host=127.0.0.1;dbname=user;port=5623;charset=utf8',
            'username' => 'user',
            'password' => 'user',
        ],
        'log'      => [
            'targets' => [
                'email' => [
                    'message' => [
                        'subject' => 'prod log alert',
                    ],
                ],
            ],
        ],
        'redis'    => [
            'hostname' => '127.0.0.1',
            'port'     => 9100,
            'database' => 0,
        ],
    ],
];
