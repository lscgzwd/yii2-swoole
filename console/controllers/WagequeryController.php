<?php
/*************************************************************************
 * File Name :    WagequeryController.php
 * Author    :    unasm
 * Mail      :    unasm@sina.cn
 ************************************************************************/

namespace console\controllers;

//use yii\console\Controller;
use apps\lib\Wagelib;
use apps\models\Wage;
use console\controllers\BaseController;
use Yii;

/**
 * 工资查询
 **/
class WagequeryController extends BaseController
{

    /**
     * 通过队列查询状态
     *
     **/
    public function actionQuery()
    {
        $redis = Yii::$app->redis;
        // 出人脸识别成功队列, 调用运营商接口
        $key = Yii::$app->params['redisKey']['wageQuery'];
        while (true) {
            $json = $redis->RPOP($key);
            if (strpos($json, '{') !== false && strpos($json, '}') !== false) {
                $data = json_decode($json, true);
                if (is_array($data) && isset($data['order'])) {
                    $rs = $this->doProcess($data['order']);
                    if ($rs == false) {
                        $redis->LPUSH($key, $json);
                    }
                }
            } else {
                break;
            }
        }
    }

    /**
     * 查询未确定的订单状态，并且更新
     *
     * @return boolen true 代表完成， false代表失败
     **/
    public function doProcess($order)
    {
        $rs = Wagelib::queryBatch($order);
        $rs = Wage::findAll(['wagBatNo' => $order]);
        if ($rs['errno'] == 200) {
            Wagelib::updateStatus($rs['data'], $order);
        }
        return false;
    }

}
