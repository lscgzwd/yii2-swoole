<?php
$env = getenv('RUNTIME_ENVIROMENT');
if(!in_array($env, ['dev', 'test', 'prod', 'live'])){
    $env = 'dev';
}
if (in_array($env, ['dev'])) {
    defined('YII_DEBUG') or define('YII_DEBUG', true);
    defined('YII_ENV') or define('YII_ENV', 'dev');
    error_reporting(E_ALL);
} else if (in_array($env, ['test'])) {
    defined('YII_DEBUG') or define('YII_DEBUG', true);
    defined('YII_ENV') or define('YII_ENV', 'test');
} else if (in_array($env, ['live', 'prod'])) {
    defined('YII_DEBUG') or define('YII_DEBUG', false);
    defined('YII_ENV') or define('YII_ENV', 'prod');
}

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require (__DIR__ . '/../../common/config/main.php'),
    require (__DIR__ . '/../../common/config/main-local.php'),
    require (__DIR__ . '/../config/main.php'),
    require (__DIR__ . '/../config/main-local.php')
);
$envFile = __DIR__ . "/../../common/config/{$env}.php";
if (is_file($envFile)) {
    $config = yii\helpers\ArrayHelper::merge(
        $config,
        require ($envFile)
    );
}
$application = new yii\web\Application($config);
$application->run();
