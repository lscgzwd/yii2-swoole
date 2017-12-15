<?php
/**
 * briabear
 * User: lushuncheng<admin@lushuncheng.com>
 * Date: 2017/3/1
 * Time: 18:17
 * @link https://github.com/lscgzwd
 * @copyright Copyright (c) 2017 Lu Shun Cheng (https://github.com/lscgzwd)
 * @licence http://www.apache.org/licenses/LICENSE-2.0
 * @author Lu Shun Cheng (lscgzwd@gmail.com)
 */
declare (strict_types = 1);

namespace yiiswoole;

use yii\base\ExitException;
use yii\base\InvalidRouteException;
use yii\web\NotFoundHttpException;

defined('YII_ENABLE_ERROR_HANDLER') || define('YII_ENABLE_ERROR_HANDLER', false);
defined('WEB_PATH') || define('WEB_PATH', __DIR__);
defined('ROOT_PATH') || define('ROOT_PATH', realpath(__DIR__ . '/../../'));
defined('VENDOR_PATH') || define('VENDOR_PATH', ROOT_PATH . '/vendor');
// 配置分离，从OP管理的配置中获取配置
defined('JDB_CONF_FILE') || define('JDB_CONF_FILE', '/data/conf/qiye/server.ini');
error_reporting(E_ALL);

class Start
{
    public $yiiConfig    = [];
    public $serverConfig = [];
    public $env          = 'local';
    public $logTrackId   = '';
    /**
     * The files contains Yii2 config
     * @var array
     */
    public $configFiles = [];
    /**
     * Array params config files. Yii::$app->params[''key']
     * @var array
     */
    public $paramsConfigFiles = [];
    /**
     * Yii2 bootstrap file to register namespaces.
     * @var string
     */
    public $yiiBootstrapFile = '';
    /**
     * @var \yiiswoole\web\Application
     */
    public static $app = null;
    /**
     * @var Start
     *
     */
    public static $instance = null;
    public function initEnv()
    {
        switch ($this->env) {
            case 'beta': // beta
                define('YII_DEBUG', false); // 关闭debug模式
                define('YII_ENV', 'beta');
                define('TRACE_LEVEL', 0);
                break;
            case 'prod': // 生产
                define('YII_DEBUG', false); // 关闭debug模式
                define('YII_ENV', 'prod');
                define('TRACE_LEVEL', 0);
                break;
            case 'yace': // 压测
                define('YII_DEBUG', false); // 关闭debug模式
                define('YII_ENV', 'yace');
                define('TRACE_LEVEL', 0);
                break;
            case 'dev':
                // 开发环境
                define('YII_DEBUG', true);
                define('YII_ENV', 'dev');
                define('TRACE_LEVEL', 3);
                break;
            case 'test':
                // 开发环境
                define('YII_DEBUG', true);
                define('YII_ENV', 'test');
                define('TRACE_LEVEL', 3);
                break;
            default:
                // 默认本地环境
                define('YII_DEBUG', true);
                define('YII_ENV', 'dev');
                define('TRACE_LEVEL', 3);
                break;
        }
    }
    public function initConfig()
    {
        if (!file_exists(JDB_CONF_FILE)) {
            die("缺少配置分离文件");
        }
        $arrServerConf = $this->parseIniFileMulti(JDB_CONF_FILE, true, INI_SCANNER_TYPED);
        $this->env     = $arrServerConf['env']['env'];
        $this->initEnv();
        // 初使化
        $config = ['params' => []];
        // 加载公共配置 数据库，组件，redis等
        foreach ($this->configFiles as $file) {
            $file = str_replace('{{env}}', $this->env, $file);
            if (is_file($file)) {
                $config = \BriarBear\Helpers\ArrayHelper::merge($config, require $file);
            }
        }

        // 加载全局配置 Yii::$app->params[$key] 请求第三方项目的接口等
        foreach ($this->paramsConfigFiles as $file) {
            $file = str_replace('{{env}}', $this->env, $file);
            if (is_file($file)) {
                $config['params'] = \BriarBear\Helpers\ArrayHelper::merge($config['params'], require $file);
            }
        }
        // 第三方系统接口地址
        if (!empty($config['params'])) {
            foreach ($config['params'] as $key => $value) {
                if (isset($arrServerConf[$key]) && !empty($arrServerConf[$key])) {
                    if (is_array($arrServerConf[$key])) {
                        $config['params'][$key] = \BriarBear\Helpers\ArrayHelper::merge($value, $arrServerConf[$key]);
                    } else {
                        $config['params'][$key] = $arrServerConf[$key];
                    }
                }
            }
        }
        // 数据库,codis等组件配置
        if (!empty($config['components'])) {
            foreach ($config['components'] as $key => $value) {
                if (isset($arrServerConf[$key]) && !empty($arrServerConf[$key])) {
                    $config['components'][$key] = \BriarBear\Helpers\ArrayHelper::merge($value, $arrServerConf[$key]);
                }
            }
        }
        unset($key, $value);
        unset($arrServerConf);
        $this->serverConfig = [
            'logger' => $config['params']['logger'] ?? [],
            'server' => $config['params']['server'] ?? [],
        ];
        $this->serverConfig['server']['callback'] = [
            'httpRequest' => [$this, 'httpRequest'],
            'tcpReceive'  => [$this, 'tcpRequest'],
            'workerStart' => [$this, 'workStart'],
            'task'        => [$this, 'task'],
        ];
        unset($config['params']['logger'], $config['params']['server']);
        $this->yiiConfig = $config;
        unset($config);
        return $this;
    }
    public function run($config = null)
    {
        $server = new \BriarBear\BriarBear();
        if ($config === null) {
            $this->initConfig();
        } else {
            $this->processConfigSection($config);
            $this->serverConfig = [
                'logger' => $config['params']['logger'] ?? [],
                'server' => $config['params']['server'] ?? [],
            ];
            $this->serverConfig['server']['callback'] = [
                'httpRequest' => [$this, 'httpRequest'],
                'tcpReceive'  => [$this, 'tcpRequest'],
                'workerStart' => [$this, 'workStart'],
                'task'        => [$this, 'task'],
            ];
            unset($config['params']['logger'], $config['params']['server']);
            $this->yiiConfig = $config;
            unset($config);
        }

        $this->loadYii();
        static::$app = new \yiiswoole\web\Application($this->yiiConfig);
        // init all yii components
        foreach ($this->yiiConfig['components'] as $id => $_config) {
            if (in_array($id, ['user', 'session'])) {
                continue;
            }
            static::$app->get($id);
        }

        $this->logTrackId = uniqid('', true) . '00';
        static::$app->getRequest()->setUrl(null);
        static::$app->getRequest()->setPathInfo(null);
        static::$app->getResponse()->clear();
        if (static::$app->has('session', true)) {
            static::$app->getSession()->close();
            static::$app->getSession()->clear();
        }
        if (static::$app->has('db', true)) {
            static::$app->getDb()->close();
        }
        if (static::$app->has('redis', true)) {
            static::$app->redis->close();
        }
        static::$instance = $this;

        $server->run($this->serverConfig);
    }

    /**
     *
     */
    public function loadYii()
    {
        // 加载Yii核心
        require_once rtrim(VENDOR_PATH, '/') . '/yiisoft/yii2/Yii.php'; // Yii核心类
        if ($this->yiiBootstrapFile) {
            require $this->yiiBootstrapFile;
        }
        \Yii::$container = new \yiiswoole\di\Container();
        // require all classes, speed the request
        $classMap       = require __DIR__ . '/classes.php';
        \Yii::$classMap = array_unique(array_merge(\Yii::$classMap, $classMap));
        \BriarBear\Autoload::getInstance()->addClassMap(\Yii::$classMap);
        foreach (\Yii::$classMap as $class => $path) {
            if (class_exists($class) === false && trait_exists($class) === false && interface_exists($class) === false) {
                \Yii::autoload($class);
            }
        }
        // swoole fix
        $_SERVER['PHP_SELF']        = '/' . basename($_SERVER['PHP_SELF']);
        $_SERVER['SCRIPT_NAME']     = $_SERVER['PHP_SELF'];
        $_SERVER['SCRIPT_FILENAME'] = rtrim(WEB_PATH, '/') . $_SERVER['PHP_SELF'];
    }

    /**
     * @param \BriarBear\Server $server
     * @param \Swoole\Server $sw
     * @param $fd
     * @param \BriarBear\Request $request
     * @return \BriarBear\Response\HttpResponse
     */
    public function httpRequest(\BriarBear\Server $server, \Swoole\Server $sw, $fd, \BriarBear\Request $request)
    {
        /**
         * @var \yiiswoole\web\Application $app
         */
        $app = clone static::$app;
        // do some init jobs before call yii
        $this->beforeRequest($app);
        $this->logTrackId = $_SERVER['HTTP_JDB_HEADER_RID'] ?? md5(uniqid('', true) . gethostname());
        /**
         * @var \yiiswoole\web\Request $app->getRequest()
         */
        $app->getRequest()->setBriabearRequest($request);
        /**
         * @var \yiiswoole\web\Response $app->getResponse()
         */
        $app->getResponse()->setRequestType(\BriarBear\Server::REQUEST_TYPE_HTTP);
        \Yii::$app = $app;
        $app::setInstance($app);
        try {
            $response = $app->run();

            $this->afterRequest($app);

            return $response;
        } catch (\Throwable $exception) {
            if ($exception instanceof NotFoundHttpException || $exception instanceof InvalidRouteException || $exception instanceof ExitException) {
                try {
                    return \Yii::$app->getErrorHandler()->handleHttpException($exception);
                } catch (\Throwable $ex) {
                    return \Yii::$app->getErrorHandler()->handleException($ex);
                }
            }
            return \Yii::$app->getErrorHandler()->handleException($exception);
        }
    }
    protected function beforeRequest(\yiiswoole\web\Application $app)
    {
        $app->set('request', clone static::$app->getRequest());
        $app->set('response', clone static::$app->getResponse());
        if ($app->has('session', true)) {
            $app->set('session', clone static::$app->getSession());
        }
        if ($app->has('user', true)) {
            $app->set('user', clone static::$app->getUser());
        }
    }

    /**
     * @param \yiiswoole\web\Application $app
     */
    protected function afterRequest(\yiiswoole\web\Application $app)
    {
        \Yii::getLogger()->flush();
        \Yii::getLogger()->flush(true);

        $app->set('request', null);
        $app->set('response', null);
        if ($app->has('session', true)) {
            $app->getSession()->close();
            $app->getSession()->clear();
            $app->set('session', null);
        }
        if ($app->has('user', true)) {
            $app->set('user', null);
        }

        $app::setInstance(null);
        \Yii::$app = null;

        unset($app);
    }

    /**
     * @param \BriarBear\Server $server
     * @param \Swoole\Server $sw
     * @param $fd
     * @param \BriarBear\Request $request
     * @return \BriarBear\Response\TcpResponse
     */
    public function tcpRequest(\BriarBear\Server $server, \Swoole\Server $sw, $fd, \BriarBear\Request $request)
    {
        $this->logTrackId = uniqid('', true) . $fd;

        $app = clone static::$app;
        // do some init jobs before call yii
        $this->beforeRequest($app);

        $app->getRequest()->setBriabearRequest($request);
        $app->getResponse()->setRequestType(\BriarBear\Server::REQUEST_TYPE_TCP);
        \Yii::$app = $app;
        $app::setInstance($app);

        try {
            $response = $app->run();

            $this->afterRequest($app);

            return $response;
        } catch (\Throwable $exception) {
            if ($exception instanceof NotFoundHttpException || $exception instanceof InvalidRouteException || $exception instanceof ExitException) {
                throw  $exception;
            }
            return \Yii::$app->getErrorHandler()->handleException($exception);
        }
    }
    public function workStart()
    {

    }

    /**
     * <?php
     *
     * [normal]
     * foo = bar
     * ; use quotes to keep your key as it is
     * 'foo.with.dots' = true
     *
     * [array]
     * foo[] = 1
     * foo[] = 2
     *
     * [dictionary]
     * foo[debug] = false
     * foo[path] = /some/path
     *
     * [multi]
     * foo.data.config.debug = true
     * foo.data.password = 123456
     *
     * ?>
     *
     * will result in:
     * <?php
     * parse_ini_file_multi('file.ini', true);
     *
     * Array
     * (
     * [normal] => Array
     * (
     * [foo] => bar
     * [foo.with.dots] => 1
     * )
     * [array] => Array
     * (
     * [foo] => Array
     * (
     * [0] => 1
     * [1] => 2
     * )
     * )
     * [dictionary] => Array
     * (
     * [foo] => Array
     * (
     * [debug] =>
     * [path] => /some/path
     * )
     * )
     * [multi] => Array
     * (
     * [foo] => Array
     * (
     * [data] => Array
     * (
     * [config] => Array
     * (
     * [debug] => 1
     * )
     * [password] => 123456
     * )
     * )
     * )
     * )
     * ?>
     * @param $file
     * @param bool $processSections
     * @param int $scannerMode
     * @return array|mixed
     */
    protected function parseIniFileMulti(string $file, bool $processSections = false, int $scannerMode = INI_SCANNER_NORMAL)
    {
        // load ini file the normal way
        $data = parse_ini_file($file, $processSections, $scannerMode);
        if (!$processSections) {
            $data = array($data);
        }
        $this->processConfigSection($data);
        if (!$processSections) {
            $data = $data[0];
        }
        return $data;
    }
    protected function processConfigSection(&$data)
    {

        $explodeStr = '.';
        $escapeChar = "'";
        foreach ($data as $sectionKey => $section) {
            // loop inside the section
            if (!is_array($section)) {
                continue;
            }
            foreach ($section as $key => $value) {
                if (is_string($key) && strpos($key, $explodeStr)) {
                    if (substr($key, 0, 1) !== $escapeChar) {
                        // key has a dot. Explode on it, then parse each subkeys
                        // and set value at the right place thanks to references
                        $subKeys = explode($explodeStr, $key);
                        $subs    = &$data[$sectionKey];
                        foreach ($subKeys as $subKey) {
                            if (!isset($subs[$subKey])) {
                                $subs[$subKey] = [];
                            }
                            $subs = &$subs[$subKey];
                        }
                        // set the value at the right place
                        $subs = $value;
                        // unset the dotted key, we don't need it anymore
                        unset($data[$sectionKey][$key]);
                    }
                    // we have escaped the key, so we keep dots as they are
                    else {
                        $newKey                     = trim($key, $escapeChar);
                        $data[$sectionKey][$newKey] = $value;
                        unset($data[$sectionKey][$key]);
                    }
                }
            }
        }
    }

    /**
     * @param \BriarBear\Server $bs
     * @param \Swoole\Server $ss
     * @param int $workerId
     * @param int $taskId
     * @param $taskParams
     */
    public function task(\BriarBear\Server $bs, \Swoole\Server $ss, int $workerId, int $taskId, $taskParams)
    {
        $this->logTrackId = uniqid('', true) . $taskId;
        $class            = $taskParams['class'];
        $method           = $taskParams['method'] ?? null;
        $params           = $taskParams['params'] ?? null;
        try {
            if ($method === null) {
                new $class($params);
            } else {
                (new $class)->$method($params);
            }
        } catch (\Throwable $exception) {
            $this->addLog('exec_task_fail, exception:'.$exception->__toString(), 'error', $taskParams);
        }
        \Yii::getLogger()->flush();
        \Yii::getLogger()->flush(true);
    }
    /**
     * common method to add a log
     * @param string $message log message
     * @param string $security log level
     * @param array  $context  params to log
     * @param string $category log category
     * @return bool
     */
    public function addLog($message, string $security, array $context = array(), string $category = 'default')
    {
        // check category
        if ($category == 'default' && \Yii::$app->controller) {
            // if client request not exist controller or action, then exception
            try {
                $category = \Yii::$app->controller->id . '-' . \Yii::$app->controller->action->id;
            } catch (\Throwable $e) {

            }
        }

        $info = [
            '@timestamp' => date('Y-m-d H:i:s'),
            '@message'   => $message,
            'context'    => $context,
            'level'      => $security,
            'category'   => $category,
            'traceId'    => $this->logTrackId,
        ];
        $category = 'activity-' . $category;

        switch ($security) {
            case 'info':
            case 'error':
            case 'warning':
                \Yii::$security($info, $category);
                break;
            default:
                \Yii::trace($info, $category);
                break;
        }

        return true;
    }
}
