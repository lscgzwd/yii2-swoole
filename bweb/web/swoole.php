<?php
ini_set('memory_limit', '1024M');
class HttpServer
{
    public $http;
    public static $instance;
    public static $yii;
    public static $runtimeEnviroment = 'test';
    public static $level = 1; //压缩等级，范围是1-9，等级越高压缩后的尺寸越小，但CPU消耗更多。默认为1

    /**
     * 初始化
     */
    private function __construct()
    {
        register_shutdown_function(array($this, 'handleFatal'));
        $http = new swoole_http_server("0.0.0.0", 9501);
        $http->set(array(
            'worker_num'    => 12, //worker进程数量
            'daemonize'     => false, //守护进程设置成true
            'max_request'   => 128, //最大请求次数，当请求大于它时，将会自动重启该worker
            'dispatch_mode' => 1,
            'log_file'      => '/home/bweb/logs/swoole.log',
            'open_tcp_nodelay' => true,
        ));
        $http->on('WorkerStart', array($this, 'onWorkerStart'));
        $http->on('request', array($this, 'onRequest'));
        $http->on('start', array($this, 'onStart'));

        self::getYii();

        $http->start();        
    }

    protected static function getYii()
    {
        $env = self::$runtimeEnviroment;
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
                require ($envFile),
                require (__DIR__ . '/../../common/config/swoole.php')
            );
        }
        self::$yii = new yii\web\Application($config);
    }

    /**
     * server start的时候调用
     * @param unknown $serv
     */
    public function onStart($serv)
    {
        echo 'swoole version' . swoole_version() . PHP_EOL;
    }
    /**
     * worker start时调用
     * @param unknown $serv
     * @param int $worker_id
     */
    public function onWorkerStart($serv, $worker_id)
    {
        // global $argv;
        // if ($worker_id >= $serv->setting['worker_num']) {
        //     swoole_set_process_name("php {$argv[0]}: task");
        // } else {
        //     swoole_set_process_name("php {$argv[0]}: worker");
        // }
        // echo "WorkerStart: MasterPid={$serv->master_pid}|Manager_pid={$serv->manager_pid}|WorkerId={$serv->worker_id}|WorkerPid={$serv->worker_pid}\n";
    }

    /**
     * 当request时调用
     * @param unknown $request
     * @param unknown $swoole_http_response
     */
    public function onRequest($swoole_http_request, $swoole_http_response)
    {
        try {            
            $_GET = [];
            $_POST = array();
            if (isset($swoole_http_request->server)) {
                $_SERVER = array_merge($_SERVER, array_change_key_case($swoole_http_request->server, CASE_UPPER));
            }
            $_SERVER['SCRIPT_FILENAME'] = __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
            $_SERVER['PHP_SELF'] = '';
            $_SERVER['SCRIPT_NAME'] = '/index.php';
            $_SERVER['DOCUMENT_ROOT'] = __DIR__;
            $_SERVER['SERVER_PORT'] = 80;
            $_SERVER['HOST'] = 'd.tlan.com.cn';
            $_SERVER['HTTP_HOST'] = 'd.tlan.com.cn';
            $_SERVER['SERVER_NAME'] = 'd.tlan.com.cn';
            $_SERVER["QUERY_STRING"] = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
            $_SERVER['DOCUMENT_URI'] = $_SERVER['REQUEST_URI'];
            $_SERVER["REQUEST_URI"] = $_SERVER['REQUEST_URI'] . ($_SERVER["QUERY_STRING"] ? '?' . $_SERVER["QUERY_STRING"] : '');
            
            putenv('IN_SWOOLE=1');
            if (isset($swoole_http_request->header)) {
                $_SERVER['server_head'] = $swoole_http_request->header;
                $_SERVER = array_merge($_SERVER, array_change_key_case($_SERVER['server_head'], CASE_UPPER));
                $_SERVER['HTTP_ACCEPT'] = $_SERVER['ACCEPT'];
                $_SERVER['HTTP_USER_AGENT'] = $_SERVER['USER-AGENT'];
                $_SERVER['HTTP_ACCEPT_ENCODING'] = isset($_SERVER['ACCEPT-ENCODING']) ? $_SERVER['ACCEPT-ENCODING'] : '';
                $_SERVER['HTTP_ACCEPT_LANGUAGE'] = isset($_SERVER['ACCEPT-LANGUAGE']) ? $_SERVER['ACCEPT-LANGUAGE'] : '';
            }

            $_SERVER["CONTENT_TYPE"] = isset($_SERVER['CONTENT-TYPE']) ? $_SERVER['CONTENT-TYPE'] : '';
            $_SERVER["X_REQUESTED_WITH"] = isset($_SERVER['X-REQUESTED-WITH']) ? $_SERVER['X-REQUESTED-WITH'] : '';
            if (isset($swoole_http_request->get)) {
                $_GET = $swoole_http_request->get;
            }
            if (isset($swoole_http_request->post)) {
                $_POST = $swoole_http_request->post;
            }
            if (isset($swoole_http_request->cookie)) {
                $_COOKIE = $swoole_http_request->cookie;
                foreach ($_COOKIE as $key => $value) {
                    $_COOKIE[$key] = urldecode($value);
                }
            }
            if (isset($swoole_http_request->files)) {
                $_FILES = $swoole_http_request->files;
            }
            ob_start();
            self::$yii->getResponse()->clear();
            self::$yii->getResponse()->setSwooleHttpResponse($swoole_http_response);
            self::$yii->getRequest()->clear();
            self::$yii->getRequest()->setRawBody($swoole_http_request->rawContent());            
            self::$yii->run();
            $result = ob_get_contents();
            ob_end_clean();
            // $swoole_http_response->header("Content-Type", "application/json;charset=utf-8");
            $result = empty($result) ? 'No message' : $result;
            $swoole_http_response->end($result);

            unset($result);
            unset($_GET);
            unset($_POST);
            unset($GLOBALS);

            gc_collect_cycles();

        } catch (Exception $e) {
            $swoole_http_response->end($e->getMessage());
        }
    }

    /**
     * 致命错误处理
     */
    public function handleFatal()
    {
        $error = error_get_last();
        if (isset($error['type'])) {
            switch ($error['type']) {
                case E_ERROR:
                    $severity = 'ERROR:Fatal run-time errors. Errors that can not be recovered from. Execution of the script is halted';
                    break;
                case E_PARSE:
                    $severity = 'PARSE:Compile-time parse errors. Parse errors should only be generated by the parser';
                    break;
                case E_DEPRECATED:
                    $severity = 'DEPRECATED:Run-time notices. Enable this to receive warnings about code that will not work in future versions';
                    break;
                case E_CORE_ERROR:
                    $severity = 'CORE_ERROR :Fatal errors at PHP startup. This is like an E_ERROR in the PHP core';
                    break;
                case E_COMPILE_ERROR:
                    $severity = 'COMPILE ERROR:Fatal compile-time errors. This is like an E_ERROR generated by the Zend Scripting Engine';
                    break;
                default:
                    $severity = 'OTHER ERROR';
                    break;
            }
            $message = $error['message'];
            $file    = $error['file'];
            $line    = $error['line'];
            $log     = "$message ($file:$line)\nStack trace:\n";
            $trace   = debug_backtrace();
            foreach ($trace as $i => $t) {
                if (!isset($t['file'])) {
                    $t['file'] = 'unknown';
                }
                if (!isset($t['line'])) {
                    $t['line'] = 0;
                }
                if (!isset($t['function'])) {
                    $t['function'] = 'unknown';
                }
                $log .= "#$i {$t['file']}({$t['line']}): ";
                if (isset($t['object']) && is_object($t['object'])) {
                    $log .= get_class($t['object']) . '->';
                }
                $log .= "{$t['function']}()\n";
            }
            if (isset($_SERVER['REQUEST_URI'])) {
                $log .= '[QUERY] ' . $_SERVER['REQUEST_URI'];
            }
            ob_start();
            include 'error_php.php';
            $log = ob_get_contents();
            ob_end_clean();
            $GLOBALS['RESPONSE']->end($log);
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
HttpServer::getInstance();
