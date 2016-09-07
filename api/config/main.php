<?php
return [
    'id'                  => 'app-api',
    'basePath'            => dirname(__DIR__),
    'bootstrap'           => ['log'],
    'controllerNamespace' => 'api\controllers',
    'modules'             => [
        'v01'   => [
            'class' => 'api\modules\v01\Module',
        ],
        'inner' => [
            'class' => 'api\modules\inner\Module',
        ],
        'oss'   => [
            'class' => 'api\modules\oss\Module',
        ],
    ],
    'components'          => [
        'request'    => [
            'enableCsrfValidation'   => false,
            'enableCookieValidation' => false,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName'  => false,
            'rules'           => [
                'oss/<controller:[\w-]+>/<action:[\w-]+>'   => 'oss/<controller>/<action>',
                'inner/<controller:[\w-]+>/<action:[\w-]+>' => 'inner/<controller>/<action>',
                'v01/<controller:[\w-]+>/<action:[\w-]+>'   => 'v01/<controller>/<action>',
            ],
        ],
    ],
];
