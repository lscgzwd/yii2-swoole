<?php
// 控制台任务项目配置文件
return [
    'id'                  => 'app-console',
    'basePath'            => dirname(__DIR__),
    'bootstrap'           => ['log'],
    'controllerNamespace' => 'console\controllers',
    'language'            => 'zh-CN',
    'sourceLanguage'      => 'zh-CN',
    'components'          => [
        'request' => [
            'class' => 'yii\console\Request',
        ],
    ],
];
