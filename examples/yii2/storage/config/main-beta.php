<?php
return [
    'components' => [
        'log'   => [
            'targets' => [
                'email' => [
                    'message' => [
                        'subject' => 'beta 日志报警',
                    ],
                ],
            ],
        ],
        'redis' => [],
    ],
];
