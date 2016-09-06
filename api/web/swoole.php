<?php

// variable to control load what environment config. runtime.php has been added to .gitignore , please add it by yourself
$env    = require __DIR__ . '/runtime.php';
$config = [];
switch ($env) {
    case 'beta': // beta
        define('YII_DEBUG', false); // disable debug
        define('YII_ENV', 'beta');
        define('TRACE_LEVEL', 0);
        break;
    case 'prod': // product environment
        define('YII_DEBUG', false); // disable debug
        define('YII_ENV', 'prod');
        define('TRACE_LEVEL', 0);
        break;
    case 'dev':
        // development environment
        $env = 'dev';
        define('YII_DEBUG', true);
        define('YII_ENV', 'dev');
        define('TRACE_LEVEL', 3);
        break;
    default:
        // default local
        $env = 'local';
        define('YII_DEBUG', true);
        define('YII_ENV', 'dev');
        define('TRACE_LEVEL', 3);
        break;
}
define('IN_SWOOLE', true);
define('WEB_PATH', __DIR__);

// set_exception_handler not allowed on swoole, so disable it
define('YII_ENABLE_ERROR_HANDLER', false);

require __DIR__ . '/../../vendor/autoload.php'; // autoload by PSR
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php'; // Yii Core
require __DIR__ . '/../../common/config/bootstrap.php'; // register namespaces

// load configuration
$config = yii\helpers\ArrayHelper::merge(
    $config,
    require __DIR__ . '/../../common/config/main.php', // common config for all app
    require __DIR__ . '/../../common/config/main-' . $env . '.php', // common config for all app with current environment
    require __DIR__ . '/../config/main.php', // app config
    require __DIR__ . '/../config/main-' . $env . '.php', // app environment config
    require __DIR__ . '/../../swoole/config/main.php' // swoole config
);

// load params Yii::$app->params[$key]
$config['params'] = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-' . $env . '.php',
    require __DIR__ . "/../config/params.php",
    require __DIR__ . '/../config/params-' . $env . '.php'
);

new \swoole\SwooleServer($config);
