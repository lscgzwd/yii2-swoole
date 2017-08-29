<?php
/**
 * storage
 * User: lushuncheng<admin@lushuncheng.com>
 * Date: 2017/3/6
 * Time: 15:51
 * @link https://github.com/lscgzwd
 * @copyright Copyright (c) 2017 Lu Shun Cheng (https://github.com/lscgzwd)
 * @licence http://www.apache.org/licenses/LICENSE-2.0
 * @author Lu Shun Cheng (lscgzwd@gmail.com)
 */
declare (strict_types = 1);
define('YII_ENABLE_ERROR_HANDLER', false);
define('WEB_PATH', __DIR__);
define('ROOT_PATH', realpath(__DIR__ . '/../../../../')); // modify it
define('VENDOR_PATH', ROOT_PATH); // modify it
// 配置分离，从OP管理的配置中获取配置
define('JDB_CONF_FILE', '/data/conf/qiye/server.ini');
define('START_CRONTAB', true);
error_reporting(E_ALL);
// composer autoload
require VENDOR_PATH . '/autoload.php';

class Start extends \yiiswoole\Start
{
    /**
     * The files contains Yii2 config
     * @var array
     */
    public $configFiles = [
        ROOT_PATH . '/storage/config/main.php',
        ROOT_PATH . '/storage/config/main-{{env}}.php',
    ];
    /**
     * Array params config files. Yii::$app->params[''key']
     * @var array
     */
    public $paramsConfigFiles = [
        ROOT_PATH . '/storage/config/params.php',
        ROOT_PATH . '/storage/config/params-{{env}}.php',
    ];
    /**
     * Yii2 bootstrap file to register namespaces.
     * @var string
     */
    public $yiiBootstrapFile = ROOT_PATH . '/storage/config/bootstrap.php';
}

(new Start())->run();
//
//class Start extends \yiiswoole\Start
//{
//}
//
//(new Start())->run($config);
