<?php
return [
    'id'                  => 'app-api',
    'basePath'            => dirname(__DIR__),
    'bootstrap'           => ['log'],
    'controllerNamespace' => 'api\controllers',
    'modules'             => [
        'v23' => [
            'class' => 'api\modules\v23\Module',
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
                'v23/<controller:[\w-]+>/<action:[\w-]+>' => 'v23/<controller>/<action>',
            ],
        ],
    ],
];
