<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=10.10.35.201;dbname=crm',
            'username' => 'crm',
            'password' => 'crm@2015',
            'charset' => 'utf8',
            'attributes' => [
                'PDO::ATTR_PERSISTENT' => true
            ]
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => '10.10.29.177',
            'port' => 6379,
            'database' => 0,
        ]
    ],
];
