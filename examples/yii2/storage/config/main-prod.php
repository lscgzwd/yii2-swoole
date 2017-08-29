<?php
return [
    'components' => [
        'db'    => [
            'enableSchemaCache' => true,
        ],
        'log'   => [
            'targets' => [
                'email' => [
                    'message' => [
                        'subject' => 'prod 日志报警',
                    ],
                ],
            ],
        ],
        'redis' => [],
    ],
];
