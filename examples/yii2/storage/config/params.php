<?php
/**
 * storage
 * User: lushuncheng<admin@lushuncheng.com>
 * Date: 2017/3/6
 * Time: 12:27
 * @link https://github.com/lscgzwd
 * @copyright Copyright (c) 2017 Lu Shun Cheng (https://github.com/lscgzwd)
 * @licence http://www.apache.org/licenses/LICENSE-2.0
 * @author Lu Shun Cheng (lscgzwd@gmail.com)
 */
return [
    'buckets' => [
        'lusc' => [
            'operator'        => 'lusc',
            'password'        => '12345678',
            'authCallbackUrl' => '',
            'authParamName'   => 'key',
        ],
        'test' => [

        ],
    ],
    'storage' => ['directory' => ''],
    'env'     => [],
    'idGen'   => [],
    'logger'  => [
        'class'   => 'BriarBear\\Log\\FileLogger',
        'logPath' => '/data/logs/storage/',
    ],
    'server'  => [
        'setting'           => [
            'worker_num'        => 4, //worker process num
            'backlog'           => 16, //listen backlog
            'max_request'       => 5000,
            'task_worker_num'   => 4,
            'dispatch_mode'     => 2, // must been 2, if not ,you can not get a full package for each request
            'open_tcp_nodelay'  => 1,
            'enable_reuse_port' => 1,
            'log_file'          => '/data/logs/storage/BriarBearServer.log',
            'log_level'         => 0,
            'daemonize'         => 1,
            'user'              => 'nginx',
            'group'             => 'nginx',
        ],
        'host'              => '0.0.0.0',
        'port'              => '80',
        'openHttpProtocol'  => true,
        'httpGetMaxSize'    => 8192,
        'httpPostMaxSize'   => 52428800, // 50MB
        'tcpMaxPackageSize' => 1024000, // 1MB
        'pidFile'           => '/data/logs/storage/server.pid',
        'httpStaticRoot'    => WEB_PATH,
        'serverIP'          => '',
        'serverName'        => 'storage', // server process name
        'gzip'              => 1,
        'keepalive'         => 1,
        'webSocket'         => [
            'port' => '9502',
            'host' => '0.0.0.0',
        ],
        'class'             => 'BriarBear\\Server',
        'crontab'           => [
            'cronList'      => [
                [
                    'rule'   => '1 */1 * * * *',
                    'class'  => '\common\crontab\TestCron',
                    'method' => 'test',
                ],
            ],
            'zookeeperHost' => [
                'domain://127.0.0.1:2118'
            ],
        ],
    ],
];
