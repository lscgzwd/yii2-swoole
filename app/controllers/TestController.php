<?php
/*************************************************************************
 ************************************************************************/
namespace apps\controllers;

use Yii;

/**
 * 测试类
 **/
class TestController extends BaseController
{

    public function actionLusc()
    {
        Yii::$app->redis->SET('KEY:KEY:KEY:AAA', time());
        return [
            'time'   => microtime(true),
            'class'  => __METHOD__,
            'server' => $_SERVER,
            'redis'  => Yii::$app->redis->GET('KEY:KEY:KEY:AAA'),
        ];
    }
}
