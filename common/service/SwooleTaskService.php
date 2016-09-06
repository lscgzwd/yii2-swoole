<?php
/**
 * swoole task回调类，通过swoole\server->task触发.
 * User: lusc
 * Date: 2016/5/19
 * Time: 13:22
 */

namespace common\service;

use common\helpers\Trace;

class SwooleTaskService
{
    public function __construct($taskData)
    {
        $taskData = json_decode($taskData, true);
        $class    = '\\common\service\\swooletask\\' . $taskData['type'];
        try {
            new $class($taskData['data']);
        } catch (\Exception $e) {
            Trace::addLog('execute_task_exception', 'error', ['data' => $taskData, 'exception' => $e->__toString(), 'msg' => 'task execute fail.'], 'swooletask');
        }
    }
}
