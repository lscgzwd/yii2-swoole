<?php
return [
    'id'                  => 'app-frontend',
    'basePath'            => dirname(__DIR__),
    'bootstrap'           => ['log'],
    'controllerNamespace' => 'apps\controllers',
    'components'          => [
        'request'    => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey'    => 'QuHT8ExNKUvwyfwX_ion1aDHAK3AYUXc',
            'enableCsrfValidation'   => true,
            'enableCookieValidation' => true,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName'  => false,
            'rules'           => [
                'app/<controller:[\w-]+>/<action:[\w-]+>' => '<controller>/<action>',
            ],
        ],
    ],
];
