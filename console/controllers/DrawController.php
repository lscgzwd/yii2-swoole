<?php
/*************************************************************************
> File Name :     ./commands/DrawController.php
> Author :        unasm
> Mail :          douunasm@gmail.com
> Last_Modified : 2016-01-19 17:35:08
 ************************************************************************/
namespace console\controllers;

//use yii\console\Controller;
use apps\lib\Payment;
use apps\lib\Trace;
use Yii;
use yii\base\ErrorException;

/**
 * 工资查询
 **/
class DrawController extends BaseController
{
    /**
     * 查询提现的状态
     *
     **/
    public function actionQuery()
    {
        $db = Yii::$app->db;
        $db->open();
        //$rowset = $db->createCommand("select * from draw where id = 55")->queryAll();
        $rowset = $db->createCommand("select * from draw where status = 'X' || status = 'A'")->queryAll();
        foreach ($rowset as $row) {
            $rs     = Payment::queryWithdrawForComp($row['uid'], $row['orderId']);
            $update = [];
            if (isset($rs['msg']) && preg_match('/提现订单号不存在/', $rs['msg'])) {
                if ($row['sTime'] + (5 * 60) < time()) {
                    //已经鉴权完毕3分钟
                    $update = [
                        //'bankName'     => $rs['data']['bankName'],
                        'status' => 'F',
                        //'trans_id'     => $rs['data']['transId'],
                        'uTime'  => time(),
                    ];
                } else {
                    continue;
                }
            } else {
                if ($rs['errno'] != 200) {
                    continue;
                }
                if (!isset($rs['data']['status']) || $rs['data']['status'] == $row['status']) {
                    //如果没有状态，或者是状态不变
                    Trace::addLog('draw_get_ans_data', 'info', ['origin' => $row, 'data' => $rs]);
                    continue;
                }
                $update = [
                    'bankName' => $rs['data']['bankName'],
                    'status'   => $rs['data']['status'],
                    'trans_id' => $rs['data']['transId'],
                    'uTime'    => time(),
                ];
            }

            //$rs['data']['status'] = 'F';

            try {
                $db->createCommand()->update('draw', $update, 'id = ' . $row['id'])->execute();
                Trace::addLog('draw_get_ans', 'info', ['origin' => $row, 'update' => $update]);
            } catch (\ErrorException $e) {
                Trace::addLog('draw_update_exception', 'warning', ['msg' => $e->getMessage(), 'data' => $update]);
                return ['errno' => 500, 'msg' => "写入失败"];
            }
        }
    }

    /**
     * 再一次提交
     *
     **/
    public function actionAgain()
    {
        $db = Yii::$app->db;
        $db->open();
        $rowset = $db->createCommand("select * from draw where status = 'A'")->queryAll();
        foreach ($rowset as $row) {
            $params = [
                'comp_jdbid' => $row['uid'],
                'order_id'   => $row['orderId'],
                'amount'     => $row['amount'],
                'from'       => 'app',
                'cipher'     => '',
                's_key'      => '',
            ];
            $rs = Payment::withdrawForComp($params);
            if (!isset($rs['errno']) || $rs['errno'] != 200) {
                Trace::addLog('draw_get_Pay_exception', 'info', ['rs' => $rs, 'params' => $params]);
                continue;
            }
            if (!isset($rs['data']['status']) || $rs['data']['status'] == $row['status']) {
                Trace::addLog('draw_get_ans_data', 'info', ['origin' => $row, 'data' => $rs]);
            }
            //$rs['data']['status'] = 'F';
            $update = [
                'bankName' => $rs['data']['bankName'],
                'status'   => $rs['data']['status'],
                'trans_id' => $rs['data']['transId'],
                'uTime'    => time(),
            ];
            try {
                $db->createCommand()->update('draw', $update, 'id = ' . $row['id'])->execute();
                Trace::addLog('draw_get_ans', 'info', ['origin' => $row, 'update' => $update]);
            } catch (\ErrorException $e) {
                Trace::addLog('draw_update_exception', 'warning', ['msg' => $e->getMessage(), 'data' => $update]);
                return ['errno' => 500, 'msg' => "写入失败"];
            }
        }
    }
}
