<?php
/**
 * SwooleServer，启动http监听
 * User: lusc
 * Date: 2016/5/22
 * Time: 19:51
 */
namespace swoole;

use common\helpers\StringHelper;
use common\service\SwooleTaskService;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server as SwooleHttpServer;
use swoole\yii\Application;
use Yii;
use yii\base\Exception;

/**
 * Class SwooleServer
 * @package swoole
 */
class SwooleServer
{
    /**
     * @var null|SwooleServer
     */
    public static $swooleApp = null;
    /**
     * instance of Swoole\Http\Server
     * @var null
     */
    public static $swooleServer = null;
    /**
     * @var null PID file for storage master pid
     */
    public static $pidFile = null;
    /**
     * @var null log file for swoole server
     */
    public static $logFile = null;
    /**
     * @var null log id,used for follow the request
     */
    public $logTraceId = null;
    /**
     * @var null \Swoole\Http\Request
     */
    public $currentSwooleRequest = null;
    /**
     * @var null \Swoole\Http\Response = null;
     */
    public $currentSwooleResponse = null;
    /**
     * @var array
     */
    public static $config = [];
    /**
     * @var array
     */
    public static $swooleConfig = [];
    /**
     * @var null
     */
    public $webRoot = null;
    public function __construct($config)
    {
        // swoole运行配置
        $swooleConfig       = $config['params']['swoole'];
        self::$swooleConfig = $swooleConfig;
        unset($config['params']['swoole']);
        // YII配置
        self::$config    = $config;
        self::$swooleApp = $this;
        // pid file用来存储主进程的ID，用于reload 所有的worker
        self::$pidFile = $swooleConfig['pidFile'];
        // 日志文件
        self::$logFile = $swooleConfig['setting']['log_file'];
        $masterPid     = file_exists(self::$pidFile) ? file_get_contents(self::$pidFile) : null;
        global $argv;
        if (!isset($argv[1])) {
            print_r("php {$argv[0]} start|reload|stop");
            return;
        }
        switch ($argv[1]) {
            case 'start':
                if ($masterPid > 0) {
                    print_r('Server is already running. Please stop it first.');
                    return;
                }
                $this->startSwooleServer($swooleConfig);
                break;
            case 'reload':
                if (!empty($masterPid)) {
                    posix_kill($masterPid, SIGUSR1);
                } else {
                    print_r('master pid is null, maybe you delete the pid file we created. you can manually kill the master process with signal SIGUSR1.');
                }
                break;
            case 'stop':
                if (!empty($masterPid)) {
                    posix_kill($masterPid, SIGTERM);
                } else {
                    print_r('master pid is null, maybe you delete the pid file we created. you can manually kill the master process with signal SIGTERM.');
                }
                break;
            default:
                print_r("php {$argv[0]} start|reload|stop");
                break;
        }
    }

    public function startSwooleServer($swooleConfig)
    {
        // 创建swoole\http\server实例
        self::$swooleServer = new SwooleHttpServer($swooleConfig['host'], $swooleConfig['port']);
        self::$swooleServer->set($swooleConfig['setting']);
        self::$swooleServer->on('Start', [$this, 'onStart']);
        self::$swooleServer->on('Shutdown', [$this, 'onShutdown']);
        self::$swooleServer->on('Task', [$this, 'onTask']);
        self::$swooleServer->on('Finish', [$this, 'onTaskFinish']);
        self::$swooleServer->on('ManagerStart', [$this, 'onManagerStart']);
        self::$swooleServer->on('WorkerStart', [$this, 'onWorkerStart']);
        self::$swooleServer->on('Request', [$this, 'onRequest']);
        self::$swooleServer->setGlobal(HTTP_GLOBAL_ALL);
        self::$swooleServer->start();
    }

    /**
     * Swoole\Http\Server处理客户端请求回调
     * @param Request $request
     * @param Response $response
     */
    public function onRequest(Request $request, Response $response)
    {
        // 重置yii/web/request yii/web/response类
        $this->clearRequestAndResponse();
        $this->logTraceId            = StringHelper::uuid();
        $this->currentSwooleRequest  = $request;
        $this->currentSwooleResponse = $response;
        // if request uri exist, just send it to client
        if ($this->processStatic()) {
            $this->clearRequestAndResponse();
            return;
        }
        $_GET['REQUEST_TIME_BEGIN'] = $_POST['REQUEST_TIME_BEGIN'] = microtime(true);
        try {
            Yii::$app->getRequest()->setSwooleRequest($request);
            Yii::$app->getResponse()->setSwooleResponse($response);
            // 开启ob缓存，抓取中间的调试输出
            ob_start();
            Yii::$app->run();
            $content = ob_get_clean();
            // 如果OB缓冲区有输出，则输出
            if (isset($content[0])) {
                $response->write($content);
            }
            // 强制输出日志到文件
            Yii::getLogger()->flush();
            // 向浏览器端发送输出结束符
            $response->end();
            $this->clearRequestAndResponse();
        } catch (\Exception $e) {
            if ($e instanceof \yii\base\ExitException) {
                // 记录日志，看是哪里调用的退出
                file_put_contents(self::$logFile, json_encode(
                    [
                        'time'    => date('Y-m-d H:i:s'),
                        'msg'     => $e->getMessage(),
                        'line'    => $e->getLine(),
                        'file'    => $e->getFile(),
                        'request' => $request,
                        'trace'   => $e->getTraceAsString(),
                        'linelog' => __LINE__,
                    ]) . PHP_EOL, FILE_APPEND);
                $response->end();
            } else {
                file_put_contents(self::$logFile, json_encode(
                    [
                        'time'    => date('Y-m-d H:i:s'),
                        'msg'     => $e->getMessage(),
                        'line'    => $e->getLine(),
                        'file'    => $e->getFile(),
                        'trace'   => $e->getTraceAsString(),
                        'request' => $request,
                        'linelog' => __LINE__,
                    ]) . PHP_EOL, FILE_APPEND);
                $data = [
                    'error' => [
                        'returnCode'        => 500,
                        'returnMessage'     => '发生错误',
                        'returnUserMessage' => '发生错误',
                        'request'           => $request,
                    ],
                    'data'  => null,
                ];
                $response->write(json_encode($data));
                $response->end();
                unset($data);
            }
            $this->clearRequestAndResponse();
        }
    }

    public function processStatic()
    {
        $file = $this->currentSwooleRequest->server['request_uri'];
        $file = $this->webRoot . $file;
        if (is_file($file)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $type  = $finfo->file($file);
            $this->currentSwooleResponse->header('Content-Type', $type);
            $this->currentSwooleResponse->sendfile($file);
            return true;
        }
        return false;
    }
    /**
     * task异步任务回调，注意，如果异步任务非常耗时，需要开启足够多的task进程
     * 因为如果当前所有task进程都有任务在处理时，再投递会导致task退化成同步
     * @param Server $serv
     * @param $taskId
     * @param $fromId
     * @param $data
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function onTask(SwooleHttpServer $serv, $taskId, $fromId, $data)
    {
        try {
            $this->logTraceId = StringHelper::uuid();
            new SwooleTaskService($data);
        } catch (Exception $e) {
            file_put_contents(self::$logFile, json_encode(
                [
                    'time'     => date('Y-m-d H:i:s'),
                    'msg'      => $e->getMessage(),
                    'line'     => $e->getLine(),
                    'file'     => $e->getFile(),
                    'taskData' => $data,
                ]) . PHP_EOL, FILE_APPEND);
        }
    }

    public function clearRequestAndResponse()
    {
        // 清空worker的请求和输出缓存
        Yii::$app->getRequest()->clear();
        Yii::$app->getResponse()->clear();
        $this->currentSwooleResponse = null;
        $this->currentSwooleRequest  = null;
    }

    /**
     * task执行完毕回调
     * @param Server $serv
     * @param $taskId
     * @param $data
     */
    public function onTaskFinish(SwooleHttpServer $serv, $taskId, $data)
    {

    }

    /**
     * 当worker进程启动时回调
     * @param Server $serv
     * @param $workerId
     */
    public function onWorkerStart(SwooleHttpServer $serv, $workerId)
    {
        // 设置进程标志
        global $argv;
        if ($workerId >= $serv->setting['worker_num']) {
            swoole_set_process_name("php {$argv[0]} task process");
        } else {
            swoole_set_process_name("php {$argv[0]} work process");
        }
        // require 配置的类，加速后面程序的运行
        $classMap = require __DIR__ . '/classes.php';
        foreach ($classMap as $class => $path) {
            Yii::autoload($class);
        }
        // 实例化Yii::$app
        $application = new Application(self::$config);
        // 初使化组件
        foreach (self::$config['components'] as $id => $_config) {
            $application->get($id);
        }
        $application->swoole = self::$swooleServer;
        $this->webRoot       = Yii::getAlias('@webroot');
        set_error_handler([$this, 'onErrorHandler']);
        register_shutdown_function([$this, 'onFatalErrorShutdown']);
    }

    /**
     * 当manager进程启动时回调
     * @param Server $server
     */
    public function onManagerStart(SwooleHttpServer $server)
    {
        global $argv;
        swoole_set_process_name("php {$argv[0]} manager");
    }

    /**
     * 主进程正常接收kill -SIGTREM 信号退出时调用
     * @param Server $server
     */
    public function onShutdown(SwooleHttpServer $server)
    {
        unlink(self::$pidFile);
    }

    /**
     * swoole下不支持set_exception_handler
     * 此函数为regsiter_shutdown_function调用，程序发生致使错误时调用
     */
    public function onFatalErrorShutdown()
    {
        $error = error_get_last();
        if (isset($error['type'])) {
            switch ($error['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
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
                        if (isset($t['object']) and is_object($t['object'])) {
                            $log .= get_class($t['object']) . '->';
                        }
                        $log .= "{$t['function']}()\n";
                    }
                    if (isset($_SERVER['REQUEST_URI'])) {
                        $log .= '[QUERY] ' . $_SERVER['REQUEST_URI'];
                    }
                    file_put_contents(self::$logFile, json_encode([
                        'time'   => date('Y-m-d H:i:s'),
                        'log'    => $log,
                        'method' => __METHOD__,
                    ]) . PHP_EOL, FILE_APPEND);
                    $data = [
                        'error' => [
                            'returnCode'        => 500,
                            'returnMessage'     => '发生错误',
                            'returnUserMessage' => '发生错误',
                        ],
                    ];
                    try {
                        // 只有当前在worker下才会输出，task中不需要输出
                        if ($this->currentSwooleResponse != null) {
                            // 尝试像客户端输出一个响应，某些情况下比如客户端已经异常断开连接，调用$response->end会报异常
                            $this->currentSwooleResponse->header('Conent-Type', 'application/json; charset=utf-8');
                            $this->currentSwooleResponse->end(json_encode($data));
                            $this->clearRequestAndResponse();
                        }
                    } catch (\Exception $e) {

                    }
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * set_error_handler 异常回调
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @throws Exception
     */
    public function onErrorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            return;
        }
        throw new Exception("[ErrorHandler]Fatal error on line {$errline} in file {$errfile} with message: (code: {$errno}, info: {$errstr})");
    }

    /**
     * 主进程主线程启动调用
     * @param Server $server
     */
    public function onStart(SwooleHttpServer $server)
    {
        global $argv;
        swoole_set_process_name("php {$argv[0]} master process");
        file_put_contents(self::$pidFile, $server->master_pid);
    }
}
