<?php
$params =  require (__DIR__ . '/../../common/config/params.php');

return [
    'id' => 'app-bweb',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'bweb\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'language' => 'zh-CN',
    'sourceLanguage' => 'zh-CN',
    'components' => [
        'user' => [
            'enableAutoLogin' => false,
            'class' => 'common\vendor\yiisoft\yii2\web\JzUser',
            'identityClass' => 'common\models\SysUser',
            'idParam' => '_aId',
            'absoluteAuthTimeout' => '7200',
            'identityCookie' => ['name' => '_sysuser', 'httpOnly' => true],
        ],   
    ],
    'params' => $params,
];
