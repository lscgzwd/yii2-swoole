<?php
/**
 * SwooleServer，start listen
 * User: lusc
 * Date: 2016/5/22
 * Time: 19:51
 */
namespace swoole;

use common\constants\ModelConst;
use common\helpers\StringHelper;
use common\helpers\Trace;
use common\service\SwooleTaskService;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server as SwooleHttpServer;
use swoole\yii\Application;
use Yii;
use yii\base\Exception;
use yii\base\ExitException;

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
    public $swooleServer = null;
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
        // get swoole run configuration
        $swooleConfig       = $config['params']['swoole'];
        self::$swooleConfig = $swooleConfig;
        unset($config['params']['swoole']);
        // YII config
        self::$config    = $config;
        self::$swooleApp = $this;
        // pid file to save swoole master process id
        self::$pidFile = $swooleConfig['pidFile'];
        // swoole log file
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
                    posix_kill($masterPid, SIGUSR1); // reload all worker
                    posix_kill($masterPid, SIGUSR2); // reload all task
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

    /**
     * start the swoole instance
     * @param $swooleConfig
     */
    public function startSwooleServer($swooleConfig)
    {
        // start the http server
        $swooleServer = new SwooleHttpServer($swooleConfig['host'], $swooleConfig['port']);
        $swooleServer->set($swooleConfig['setting']);
        $swooleServer->on('Start', [$this, 'onStart']);
        $swooleServer->on('Shutdown', [$this, 'onShutdown']);
        $swooleServer->on('Task', [$this, 'onTask']);
        $swooleServer->on('Finish', [$this, 'onTaskFinish']);
        $swooleServer->on('ManagerStart', [$this, 'onManagerStart']);
        $swooleServer->on('WorkerStart', [$this, 'onWorkerStart']);
        $swooleServer->on('Request', [$this, 'onRequest']);
        $this->swooleServer = $swooleServer;
        $this->swooleServer->start();
    }

    /**
     * Swoole\Http\Server http request callback
     * @param Request $request
     * @param Response $response
     */
    public function onRequest(Request $request, Response $response)
    {
        $_GET    = isset($request->get) ? $request->get : [];
        $_POST   = isset($request->post) ? $request->post : [];
        $_SERVER = array_change_key_case($request->server, CASE_UPPER);
        $_FILES  = isset($request->files) ? $request->files : [];
        $_COOKIE = isset($request->cookie) ? $request->cookie : [];
        if (isset($request->header)) {
            foreach ($request->header as $key => $value) {
                $key           = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
                $_SERVER[$key] = $value;
            }
        }
        // swoole fix
        $_SERVER['PHP_SELF']        = '/index.php';
        $_SERVER['SCRIPT_NAME']     = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = WEB_PATH . '/index.php';
        $_SERVER['SERVER_NAME']     = '127.0.0.1';

        $this->logTraceId            = StringHelper::uuid();
        $this->currentSwooleRequest  = $request;
        $this->currentSwooleResponse = $response;
        // if request uri exist, just send it to client
        if ($this->processStatic()) {
            $this->clearRequestAndResponse();
            return;
        }

        $this->currentSwooleResponse->header('Access-Control-Allow-Origin', '*');
        $this->currentSwooleResponse->header('Access-Control-Allow-Credentials', 'true');
        $this->currentSwooleResponse->header('Conent-Type', 'application/json; charset=utf-8');

        try {
            Yii::$app->getRequest()->setSwooleRequest($request);
            Yii::$app->getResponse()->setSwooleResponse($response);

            Yii::$app->run();
            // force flush the log
            Yii::getLogger()->flush();
            Yii::getLogger()->flush(true);
            $this->clearRequestAndResponse();
        } catch (\Exception $e) {
            if ($e instanceof ExitException) {
                // add log to track where throw this exception
                Trace::addLog('catch_exit_exception', 'info',
                    [
                        'msg'     => $e->getMessage(),
                        'line'    => $e->getLine(),
                        'file'    => $e->getFile(),
                        'request' => get_object_vars($request),
                        'trace'   => $e->getTraceAsString(),
                        'linelog' => __LINE__,
                    ]);
            } elseif ($e instanceof EndException) {
                // if you called Swoole::sendfile or manually end the response, throw this exception to end the request
            } else {
                // other exceptions
                Trace::addLog('Exception_when_request', 'error', [
                    'msg'     => $e->getMessage(),
                    'line'    => $e->getLine(),
                    'file'    => $e->getFile(),
                    'trace'   => $e->getTraceAsString(),
                    'SERVER'  => $_SERVER,
                    'request' => get_object_vars($request),
                    'linelog' => __LINE__,
                ]);
                $data = ModelConst::getResult(ModelConst::G_SYS_ERR);
                $this->currentSwooleResponse->header('Conent-Type', 'application/json; charset=utf-8');
                $response->end(json_encode($data));
                unset($data);
            }
            $this->clearRequestAndResponse();
            Yii::getLogger()->flush();
            Yii::getLogger()->flush(true);
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
     * async task callback
     * must set enough task work number, if tasks great than task work number, then the work will been block
     * @param SwooleHttpServer $serv
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
            Trace::addLog('task_execute_fail', 'error',
                [
                    'msg'      => $e->getMessage(),
                    'line'     => $e->getLine(),
                    'file'     => $e->getFile(),
                    'taskData' => $data,
                ]);
        }
        // force to log
        Yii::getLogger()->flush();
        Yii::getLogger()->flush(true);
    }

    public function clearRequestAndResponse()
    {
        // re init the yii request and response when finish the request
        $config = Yii::$app->getComponents(true);
        Yii::$app->set('request', $config['request']);
        Yii::$app->set('response', $config['response']);
        $this->currentSwooleResponse = null;
        $this->currentSwooleRequest  = null;
        unset($config);
    }

    /**
     * task执行完毕回调
     * @param SwooleHttpServer $serv
     * @param $taskId
     * @param $data
     */
    public function onTaskFinish(SwooleHttpServer $serv, $taskId, $data)
    {

    }

    /**
     * callback for worker start
     * @param SwooleHttpServer $serv
     * @param $workerId
     */
    public function onWorkerStart(SwooleHttpServer $serv, $workerId)
    {
        // swoole fix
        $_SERVER['PHP_SELF']        = '/index.php';
        $_SERVER['SCRIPT_NAME']     = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = WEB_PATH . '/index.php';

        // set process mark
        global $argv;
        if ($workerId >= $serv->setting['worker_num']) {
            swoole_set_process_name("php {$argv[0]} task process");
        } else {
            swoole_set_process_name("php {$argv[0]} work process");
        }
        // require all classes, speed the request
        $classMap = require __DIR__ . '/classes.php';
        foreach ($classMap as $class => $path) {
            Yii::autoload($class);
        }
        // get the instance of Application
        $application = new Application(self::$config);
        // init all yii components
        foreach (self::$config['components'] as $id => $_config) {
            // error handler not work on swoole
            if (in_array($id, ['errorHandler'])) {
                continue;
            }
            $application->get($id);
        }
        $this->webRoot = WEB_PATH;
        // if you use php7 , use can and try catch for throwable in request callback
        // no need for set error handler
        set_error_handler([$this, 'onErrorHandler']);
        register_shutdown_function([$this, 'onFatalErrorShutdown']);
    }

    /**
     * callback for manager process start
     * @param SwooleHttpServer $server
     */
    public function onManagerStart(SwooleHttpServer $server)
    {
        global $argv;
        swoole_set_process_name("php {$argv[0]} manager");
    }

    /**
     * callback when master process get SIGTREM
     * @param Server $server
     */
    public function onShutdown(SwooleHttpServer $server)
    {
        unlink(self::$pidFile);
    }

    /**
     *
     * callback regsiter_shutdown_function
     * set exception not work on swoole
     * if you use php7, use can and try catch for throwable
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
                    Trace::addLog('Fatal_error', 'error', [
                        'log'    => $log,
                        'method' => __METHOD__,
                    ]);
                    $data = ModelConst::getResult(ModelConst::G_SYS_ERR);
                    try {
                        // try to send something to the client if current is work
                        if ($this->currentSwooleResponse != null) {
                            $this->currentSwooleResponse->header('Conent-Type', 'application/json; charset=utf-8');
                            $this->currentSwooleResponse->end(json_encode($data));
                            $this->clearRequestAndResponse();
                        }
                    } catch (\Exception $e) {
                        file_put_contents(self::$logFile, json_encode([
                            'trace' => $e->getTraceAsString(),
                            'time'  => date('Y-m-d H:i:s'),
                            'msg'   => $e->getMessage(),
                        ]) . PHP_EOL, FILE_APPEND);
                    }
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * set_error_handler callback
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
        throw new Exception("[ErrorHandler]error on line {$errline} in file {$errfile} with message: (code: {$errno}, info: {$errstr})");
    }

    /**
     * master process start callback
     * @param SwooleHttpServer $server
     */
    public function onStart(SwooleHttpServer $server)
    {
        global $argv;
        swoole_set_process_name("php {$argv[0]} master process");
        file_put_contents(self::$pidFile, $server->master_pid);
    }
}
