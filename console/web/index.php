#!/usr/bin/env php70
<?php
// 环境变量
$env    = require __DIR__ . '/runtime.php';
$config = [];
switch ($env) {
    case 'beta': // beta
    case 'prod': // 生产
    case 'stress': // 压测
    case 'docker':
        define('YII_DEBUG', false); // 关闭debug模式
        define('YII_ENV', 'prod');
        define('TRACE_LEVEL', 0);
        break;
    case 'dev':
        // 开发环境
        $env = 'dev';
        define('YII_DEBUG', true);
        define('YII_ENV', 'dev');
        define('TRACE_LEVEL', 3);
        // configuration adjustments for 'dev' environment
        $config['bootstrap'][]      = 'debug';
        $config['modules']['debug'] = 'yii\debug\Module';
        // dev 模式下开启gii模块
        $config['bootstrap'][]    = 'gii';
        $config['modules']['gii'] = [
            'class'      => 'yii\gii\Module',
            'allowedIPs' => ['*'],
        ];

        break;
    default:
        // 默认本地环境
        $env = 'local';
        define('YII_DEBUG', true);
        define('YII_ENV', 'dev');
        define('TRACE_LEVEL', 3);
        // configuration adjustments for 'beta' environment
        $config['bootstrap'][]      = 'debug';
        $config['modules']['debug'] = 'yii\debug\Module';
        // dev 模式下开启gii模块
        $config['bootstrap'][]    = 'gii';
        $config['modules']['gii'] = [
            'class'      => 'yii\gii\Module',
            'allowedIPs' => ['*'],
        ];

        break;
}

// 加载Yii核心
require __DIR__ . '/../../vendor/autoload.php'; // 自动加载
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php'; // Yii核心类
require __DIR__ . '/../../common/config/bootstrap.php'; // 命名空间注册

// 加载公共配置
$config = yii\helpers\ArrayHelper::merge(
    $config,
    require (__DIR__ . '/../../common/config/main.php'), // 公共配置
    require (__DIR__ . '/../../common/config/main-' . $env . '.php'), // 公共配置
    require (__DIR__ . '/../config/main.php'), // 项目配置
    require (__DIR__ . '/../config/main-' . $env . '.php') // 项目配置
);

// 加载全局配置 Yii::$app->params[$key]
$config['params'] = yii\helpers\ArrayHelper::merge(
    require (__DIR__ . '/../../common/config/params.php'),
    require (__DIR__ . '/../../common/config/params-' . $env . '.php'),
    require (__DIR__ . "/../config/params.php"),
    require (__DIR__ . '/../config/params-' . $env . '.php')
);

// fcgi doesn't have STDIN and STDOUT defined by default
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));

unset($config['components']['request']['enableCsrfValidation']);
unset($config['components']['request']['cookieValidationKey']);
unset($config['components']['request']['enableCookieValidation']);
unset($config['components']['request']['parsers']);
unset($config['components']['errorHandler']);
unset($config['components']['response']);

$application = new yii\console\Application($config);
$application->run();
