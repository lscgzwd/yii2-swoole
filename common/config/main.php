<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'timeZone' => 'PRC',
    'components' => [
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'dateFormat' => 'php:Y-m-d',
            'datetimeFormat' => 'php:Y-m-d H:i:s',
            'timeFormat' => 'php:H:i:s',
            'defaultTimeZone' => 'PRC'
        ],
        'request' => [
            'class' => 'common\vendor\yiisoft\yii2\web\JzRequest',
            'enableCsrfValidation' => true,
            'cookieValidationKey' => '5NcDVPxwFj-tljPG038SFu4i0XTMYFwW',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'text/json' => 'yii\web\JsonParser',
            ],
        ],
        'errorHandler' => [
            'class' => 'common\vendor\yiisoft\yii2\web\JzErrorHandler'
        ],
        'urlManager' => [
            'enablePrettyUrl' => false,
            'showScriptName' => false,
            'rules' => [
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],
];
