<?php
return [
    'adminEmail'                    => 'admin@example.com',
    'supportEmail'                  => 'support@example.com',
    'user.passwordResetTokenExpire' => 3600,
    'staff_upload_excel_max_row'    => 5000,
    'swoole'                        => [
        'setting' => [
            'worker_num'        => 128, //worker process num
            'backlog'           => 128, //listen backlog
            'max_request'       => 5000,
            'dispatch_mode'     => 3,
            'user'              => 'rrxuser',
            'group'             => 'rrxuser',
            'open_tcp_nodelay'  => 1,
            'enable_reuse_port' => 1,
            'task_worker_num'   => 128,
            'task_worker_max'   => 512,
            'log_file'          => '/data/logs/swoole.log',
            'log_level'         => 0,
            'daemonize'         => 1,
        ],
        'host'    => '0.0.0.0',
        'port'    => 9501,
        'pidFile' => defined('SWOOLE_PID_FILE') ? SWOOLE_PID_FILE : sys_get_temp_dir() . '/swooleserver.pid',
    ],
];
