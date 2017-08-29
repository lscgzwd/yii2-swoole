<?php
return [
    'components' => [
        'log'   => [
            'targets' => [
                'email' => [
                    'message' => [
                        'subject' => 'dev 日志报警',
                    ],
                ],
            ],
        ],
        'redis' => [],
    ],
];
