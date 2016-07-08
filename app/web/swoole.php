<?php
// 环境变量 控制是加载哪个环境的配置文件runtime.php已经加入.gitignore文件
$env    = require __DIR__ . '/runtime.php';
$config = [];
switch ($env) {
    case 'beta': // beta
        define('YII_DEBUG', false); // 关闭debug模式
        define('YII_ENV', 'beta');
        define('TRACE_LEVEL', 0);
        break;
    case 'prod': // 生产
    case 'stress': // 压测
    case 'docker':
    case 'yz':
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
        break;
    default:
        // 默认本地环境
        $env = 'local';
        define('YII_DEBUG', true);
        define('YII_ENV', 'dev');
        define('TRACE_LEVEL', 3);
        break;
}
define('IN_SWOOLE', true);
define('WEB_PATH', __DIR__);

// swoole中不支持set_exception_handler所以禁用
define('YII_ENABLE_ERROR_HANDLER', false);

require __DIR__ . '/../../vendor/autoload.php'; // PSR自动加载
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php'; // Yii核心类
require __DIR__ . '/../../common/config/bootstrap.php'; // 命名空间注册

// 加载公共配置
$config = yii\helpers\ArrayHelper::merge(
    $config,
    require __DIR__ . '/../../common/config/main.php', // 公共配置
    require __DIR__ . '/../../common/config/main-' . $env . '.php', // 公共配置
    require __DIR__ . '/../config/main.php', // 项目配置
    require __DIR__ . '/../config/main-' . $env . '.php', // 项目配置
    require __DIR__ . '/../../swoole/config/main.php'
);

// 加载全局配置 Yii::$app->params[$key]
$config['params'] = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-' . $env . '.php',
    require __DIR__ . "/../config/params.php",
    require __DIR__ . '/../config/params-' . $env . '.php'
);

new \swoole\SwooleServer($config);
