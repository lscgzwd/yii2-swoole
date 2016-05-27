<?php
/*************************************************************************
 * File Name :    ../app/lib/Trace.php
 * Author    :    unasm
 * Mail      :    unasm@sina.cn
 ************************************************************************/

namespace apps\lib;

use swoole\SwooleServer;
use Yii;

class Trace
{
    /**
     * 将里面的元素都转化成字符串.
     **/
    public static function toStr($context)
    {
        try {
            if (is_array($context)) {
                $except = [
                    //接口的耗时
                    'elapsed'  => 'elapsed',
                    //外部接口的耗时
                    'timecost' => 'timecost',
                ];
                foreach ($context as $key => $value) {
                    if (isset($except[$key])) {
                        //上面两个都是浮点型的
                        $context[$key] = (float) $value;
                    } elseif (is_array($value)) {
                        $context[$key] = self::toStr($value);
                    } else {
                        $context[$key] = "'" . ($value) . "'";
                    }
                }
            } elseif (is_object($context)) {
                return json_encode($context);
            }
        } catch (\ErrorException $e) {
            Yii::error(['msg' => $e->getMessage(), 'context' => "'" . json_encode($context) . "'"], 'trace_parse_error');
        }

        return $context;
    }

    /**
     * 记录追踪日志.
     *
     *  @param  $message  string  记录的事件名字
     *  @param  $security string  事件的级别
     *  @param  $context  array  上下文
     *  @param  $category string  访问的路径
     **/
    public static function addLog($message, $security, $context = array(), $category = 'default')
    {
        if ($category == 'default') {
            $category = Yii::$app->controller->id . '-' . Yii::$app->controller->action->id;
        }

        // 定义trackid , 方便跟踪请求日志链条
        $trackId = null;
        if (defined('IN_SWOOLE')) {
            $trackId = SwooleServer::$swooleApp->logTraceId;
        } else {
            defined('YII_TRACK_ID') or define('YII_TRACK_ID', md5(microtime(true) . rand(0, 10000)));
            $trackId = YII_TRACK_ID;
        }
        $info = [
            'trackid'    => $trackId,
            '@timestamp' => date('Y-m-d H:i:s'),
            '@message'   => $message,
            'server'     => gethostname(),
            'context'    => $context,
            //'context' => self::toStr($context),
            'level'      => $security,
            'catetory'   => $category,
        ];
        $category = 'activity-' . $category;
        //defined("LOGFILE") OR define('LOGFILE',Yii::$app->basePath . "/runtime/" . date("md") . '_trace_qiye.txt');
        defined('LOGFILE') or define('LOGFILE', '/data/logs/' . date('Ymd') . '_trace_qiye.txt');
        //defined("LOGFILE") OR define('LOGFILE', "/data/logs/trace_qiye_" . date("Ymd") . '.txt');
        if (!file_exists(LOGFILE)) {
            file_put_contents(LOGFILE, json_encode($info, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
            chmod(LOGFILE, 0766);
        } else {
            file_put_contents(LOGFILE, json_encode($info, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        $info['context'] = self::toStr($context);

        if ($security == 'info') {
            Yii::info($info, $category);
        } elseif ($security == 'warning') {
            Yii::warning($info, $category);
        } elseif ($security == 'error') {
            Yii::error($info, $category);
        } else {
            //添加日志
            Yii::trace($info, $category);
        }

        return true;
    }
}
