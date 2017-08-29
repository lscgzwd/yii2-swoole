<?php
return [
    'components' => [
        'log'   => [
            'targets' => [
                'email' => [
                    'message' => [
                        'subject' => 'TEST 日志报警',
                    ],
                ],
            ],
        ],
        'redis' => [],
    ],
];
