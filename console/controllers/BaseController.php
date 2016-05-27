<?php
/*************************************************************************
 * File Name :    ./commands/BaseController.php
 * Author    :    unasm
 * Mail      :    unasm@sina.cn
 ************************************************************************/

namespace console\controllers;

use apps\lib\Trace;
use Yii;
use yii\console\Controller;

//use yii\helpers\HtmlPurifier;

class BaseController extends Controller
{
    public function beforeAction($action)
    {
        // 定义trackid , 方便跟踪请求日志链条
        $server  = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : php_uname('n');
        $request = Yii::$app->getRequest();
        $ext     = ['server' => $server];
        $message = 'commandStart_' . Yii::$app->controller->id . '_' . Yii::$app->controller->action->id;
        Trace::addLog($message, 'info', $ext);
        return true;
    }
}
