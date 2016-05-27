<?php
/*************************************************************************
 * File Name :    WagequeryController.php
 * Author    :    unasm
 * Mail      :    unasm@sina.cn
 ************************************************************************/

namespace console\controllers;

use apps\lib\Friendsapi;
use apps\lib\Payment;
//use yii\console\Controller;
use apps\lib\Redis;
use apps\lib\SmsApi;
use apps\lib\Trace;
use apps\lib\User;
use apps\lib\Wagelib;
use apps\models\Auth;
use apps\models\Batch;
use apps\models\Wage;
use Yii;
use yii\base\Exception;

/**
 * 工资查询.
 **/
class WageController extends BaseController
{
    const TIMES = 10;
    /**
     * 通过队列查询状态
     *
     * 之所以选择总的批次，而不是分的批次id，
     * 主要是担心发工资入队列的时候发生异常，导致工资发放不完整
     **/
    public function actionQuery()
    {
        $redis = Yii::$app->redis;
        // 出人脸识别成功队列, 调用运营商接口
        $key = Yii::$app->params['redisKey']['wageQuery'];
        $len = $redis->LLEN($key);
        //$len = 1;
        $conn = Yii::$app->db;
        $conn->open();
        try {
            while ($len--) {
                $json = $redis->RPOP($key);
                Trace::addLog('wage_query_batch', 'info', ['json' => $json]);
                if (strpos($json, '{') !== false && strpos($json, '}') !== false) {
                    $data = json_decode($json, true);
                    //var_dump($data);
                    if (is_array($data) && isset($data['order'])) {
                        Trace::addLog('wage_query_batch', 'info', ['data' => $data]);
                        if ($data['subQuery']) {
                            //如果是子查询的话，而不是总的批次
                            $this->doProcess($data['order'], $data['compId'], $data);
                        } else {
                            $ordersSql = "select compId, wagBatNo from batch where allBatch = '{$data['order']}'";
                            //$ordersSql = "select compId, wagBatNo from batch where allBatch = '{$order}'";
                            $orderSet = $conn->createCommand($ordersSql)->queryAll();
                            foreach ($orderSet as $row) {
                                $rs = $this->doProcess($row['wagBatNo'], $row['compId'], $data);
                            }
                        }
                    }
                } else {
                    break;
                }
                //sleep(10);
            }
        } catch (\ErrorException $e) {
            $log = [
                'msg' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];
            Trace::addLog('queryBatch_exception', 'warning', $log);
        }
    }

    /**
     * 查询未确定的订单状态，并且更新.
     *
     * @param   string
     *
     * @return boolen true 代表完成， false代表失败
     **/
    public function doProcess($wagBatNo, $compId, $data)
    {
        $redis = Yii::$app->redis;
        // 出人脸识别成功队列, 调用运营商接口
        $key = Yii::$app->params['redisKey']['wageQuery'];
        $rs = Wagelib::queryBatch($wagBatNo, $compId);
        $log = [
            'wagBatNo' => $wagBatNo,
            'uid' => $compId,
            'data' => $data,
            'rs' => $rs,
        ];
        Trace::addLog('wage_query_doProcess', 'info', $log);
        if ($rs['errno'] == 200) {
            $rs = Wagelib::updateStatus($rs['data'], $wagBatNo, $compId);
            if ($rs['errno'] == 200) {
                return true;
            }
        }

        if (isset($data['cnt'])) {
            if ($data['cnt'] < self::Times) {
                ++$data['cnt'];
                $redis->LPUSH($key, json_encode($data));
                //$redis->LPUSH($key, $json);
            }
        } else {
            $subData = [
                'subQuery' => 1,
                'order' => $wagBatNo,
                'compId' => $compId,
                'cnt' => 1,
            ];
            //$data['cnt'] = 1;
            $redis->LPUSH($key, json_encode($subData));
        }

        return false;
    }

    /**
     * 定时重新发工资.
     *
     * @return array
     **/
    public function actionResend()
    {
        $conn = Yii::$app->db;
        $conn->open();
        $time = time() - 86400 * 180;
        //获取所有发送失败的，再次重发
        $maxTable = Yii::$app->params['maxTable'];
        for ($i = 0; $i < $maxTable; ++$i) {
            //遍历所有的表
            $tabName = Wage::choseTable($i);
            $failStatus = Yii::$app->params['wageStatusKey']['fail'];
            $pendingStatus = Yii::$app->params['wageStatusKey']['pending'];
            $status = array_merge($failStatus, $pendingStatus);
            $statusStr = "'".implode("','", $status)."'";
            $sql = "select id,createtime,wagBatNo, cusName, cusPhone, compUsrNo, status from {$tabName} where status in({$statusStr}) && createtime >= '{$time}'";
            //$sql = "select id, cusPhone, status from wage where status != 'S' && createtime >= '{$time}'";
            $command = $conn->createCommand($sql);
            $rowset = $command->queryAll();
            foreach ($rowset as $order) {
                //if ($order['status'] == 'F') {
                if (in_array($order['status'], $failStatus)) {
                    $rs = Wagelib::checkStatus($order['cusPhone'], $order['cusName']);
                    //查询用户中心状态
                    if ($rs['errno'] != 200) {
                        $log = [
                            'rs' => $rs,
                            'params' => $order,
                        ];
                        Trace::addLog('daily_sendWage_data', 'info', $log);
                        continue;
                    }
                    Trace::addLog('daily_resend_wage', 'info', ['order' => $order, 'rs' => $rs]);
                    //更新状态
                    try {
                        $conn->createCommand()->update($tabName, $rs['data'], 'id = '.$order['id'])->execute();
                        //$conn->createCommand()->update('wage', $rs['data'], 'id = ' . $line['id'])->execute();
                    } catch (\ErrorException $e) {
                        $log = [
                            'msg' => $e->getMessage(),
                            'data' => $rs['data'],
                            'old' => $order,
                        ];
                        Trace::addLog('update_wageStatus_exception', 'info', $log);
                    }
                    if ($rs['data']['status'] == 'R') {
                        //满足发放条件
                        $rs = Payment::sendSingle($order['id'], $order['compUsrNo']);
                    } else {
                        //否则插入状态变化的队列
                        if ($order['status'] != $rs['data']['status']) {
                            Payment::wageToQueue($order, $rs['data']['status']);
                        }
                    }
                } else {
                    if ($order['status'] == 'A') {
                        //鉴权完毕，尚未发放就中断了
                        $rs = Payment::sendSingle($order['id'], $order['compUsrNo']);
                    }
                    if ($order['status'] == 'R') {
                        //曾经满足过发放条件，但是发放失败
                        $rs = Payment::sendSingle($order['id'], $order['compUsrNo']);
                    }
                }
            }
        }
    }

    /**
     * 定时发送通知
     * 一定要晚于每天的 定时重发.
     *
     * @author
     **/
    public function actionNotice()
    {
        $conn = Yii::$app->db;
        $conn->open();
        $time = time() - 86400 * 180;
        //获取所有发送失败的，再次重发

        $tabNum = Yii::$app->params['maxTable'];
        for ($i = 0; $i < $tabNum; ++$i) {
            $tabName = Wage::choseTable($i);
            $sql = "select id, cusPhone, compUsrNo, status, cusAmount, remk2 from {$tabName} where status in('N', 'U')";
            $command = $conn->createCommand($sql);
            $rowset = $command->queryAll();
            foreach ($rowset as $order) {
                $userInfo = User::getUserInfo($order['compUsrNo']);
                if (!isset($userInfo['data']) || !$userInfo['data']) {
                    return ['errno' => 404, 'msg' => '企业不存在'];
                }
                $company = $userInfo['data']['ext']['company_name'];
                $msg = '';
                $amount = sprintf('%.2f', $order['cusAmount'] * 0.01);
                if ($order['status'] == 'N') {
                    //如果用户尚未注册借贷宝
                    if ($order['remk2']) {
                        $msg = $company.'给您发了'.$amount."元工资（{$order['remk2']}），用手机号".$order['cusPhone'].'注册借贷宝即可领取，下载地址:'.Yii::$app->params['jdbDownload'];
                    } else {
                        $msg = $company.'给您发了'.$amount.'元工资，用手机号'.$order['cusPhone'].'注册并完成身份认证后即可领取，下载地址:'.Yii::$app->params['jdbDownload'];
                    }
                }
                if ($order['status'] == 'U') {
                    //如果用户已经注册了，但是尚未实名
                    if ($order['remk2']) {
                        $msg = $company.'给您发了'.$amount."元工资（{$order['remk2']}），请登录借贷宝，进入“钱包-我-实名认证”，完成认证后，一天之内即可领取。";
                    } else {
                        $msg = $company.'给您发了'.$amount.'元工资，请登录借贷宝，进入“钱包-我-实名认证”，完成认证后，一天之内即可领取。';
                    }
                }
                if ($msg) {
                    SmsApi::send($order['cusPhone'], $msg);
                }
            }
        }
    }

    /**
     * 清理过期的订单.
     *
     * @author Me
     **/
    public function actionDelete()
    {
        $start = time() - 3600 * 24 * 180;
        $end = time() - 2 * 60;
        $tabNum = Yii::$app->params['maxTable'];
        for ($i = 0; $i < $tabNum; ++$i) {
            $tabName = Wage::choseTable($i);
            $sql = "select id, createtime, cusPhone, status, wagBatNo from {$tabName} where status = ''";
            //$sql = "select id, createtime, cusPhone, status, wagBatNo from wage where status = '' && createtime <= '{$end}' && createtime >= '{$start}'";
            $conn = Yii::$app->db;
            $conn->open();
            $command = $conn->createCommand($sql);
            $rs = $command->queryAll();
            $hourTime = time() - 60 * 60;
            foreach ($rs as $order) {
                if ($order['status'] == '' && $order['createtime'] < $hourTime) {
                    //过期删除
                    try {
                        $ans = $conn->createCommand()->update($tabName, ['status' => 'D'], 'id = '.$order['id'])->execute();
                        Trace::addLog('wage_markDelete_data', 'info', ['id' => $order['id'], 'tabName' => $tabName, 'status' => 'D', 'ans' => $ans]);
                    } catch (\ErrorException $e) {
                        Trace::addLog('wage_markDelete_exception', 'info', ['data' => $order, 'msg' => $e->getMessage()]);
                    }
                }
            }
        }
    }

    /**
     * 查询 用户中心的手机号与姓名与支付中心数据不一致的情况.
     *
     * 用户信息不一致会导致 加好友失败
     *
     * @author doujm
     **/
//    public function actionCheckUserInfo()
    //    {
    //        $start = time() - 3600 * 24 * 3;
    //        $tabNum = Yii::$app->params['maxTable'];
    //        for ($i = 0; $i < $tabNum; $i++) {
    //            $tabName = Wage::choseTable($i);
    //            //$sql = "select id, createtime, cusPhone, status, wagBatNo from {$tabName} where status = ''";
    //            $sql = "select id, createtime, cusName, cusPhone, wagBatNo from wage where status = 'S' && createtime >= '{$start}'";
    //            $conn = Yii::$app->db;
    //            $conn->open();
    //            $command =  $conn->createCommand($sql);
    //            $rs = $command->queryAll();
    //            $hourTime = time() - 60 * 60;
    //            foreach ($rs as $order) {
    //                    //过期删除
    //                try {
    //                    $user = User::GetUlistByPhoneNumArr([$row['cusPhone']]);
    //                    //$userData = $user['data'];
    //                    $tmp = array_keys($usr['data']);
    //                    var_dump($tmp);
    //                    if (count($tmp) == 0) {
    //                        //一个用户中心不存在的用户发工资ok了!!!!
    //                        Trace::addLog("wage_checkUserInfo_error", 'error',['wage' => $row]);
    //                    }
    //                    $userData = $tmp[0];
    //                    if ($userData['user_name'] != $order['cusName']) {
    //                        Trace::addLog("wage_checkUserInfo_name_error", 'error',['wage' => $row, 'name' => $userData['user_name']]);
    //                        //与用户中心的姓名不不相同
    //                    }
    //                } catch (\ErrorException $e) {
    //                    Trace::addLog("wage_getUserInfo_exception", 'warning',['data' => $row, 'msg' => $e->getMessage()]);
    //                }
    //            }
    //        }
    //    }

    /**
     * 查询一小时，3分钟内的订单的状态
     **/
    public function actionCheckstatus()
    {
        $conn = Yii::$app->db;
        $conn->open();
        $now = time();
        $start = $now - 3600 * 24 * 40;
        $end = $now - 2 * 60;
        $maxTable = Yii::$app->params['maxTable'];
        $list = Yii::$app->params['wageStatusKey']['pending'];
        $pendStr = "'".implode("','", $list)."'";
        try {
            for ($i = 0; $i < $maxTable; ++$i) {
                //遍历所有的表
                $tabName = Wage::choseTable($i);
                //获取所有发送失败的，再次重发
                $sql = "select distinct wagBatNo , compUsrNo, createtime from {$tabName} where status in({$pendStr}) && createtime <= '{$end}' && createtime >= '{$start}'";
                //$sql = "select distinct wagBatNo , compUsrNo, createtime from {$tabName} where (status = 'R' || status = 'A') && createtime <= '{$end}' && createtime >= '{$start}'";
                $command = $conn->createCommand($sql);
                $rs = $command->queryAll();
                $conn = Yii::$app->db;
                $conn->open();
                foreach ($rs as $order) {
                    $toCheck = 0;
                    $distance = ($now - $order['createtime']);
                    //$distance = 7320;
                    //$distance = 60220;
                    //$distance = 60020;
                    //$distance = 2100;
                    //线上为2分钟一次的查询频率
                    if ($distance < 4600) {
                        //一个小时内的请求,每次都查，频率尽可能高
                        $toCheck = 1;
                    } elseif ($distance < 86400) {
                        //10分钟查询一次
                        if (($distance % 600) <= 130) {
                            $toCheck = 1;
                        }
                    } else {
                        //一个小时查询一次
                        if (($distance % 3600) <= 130) {
                            $toCheck = 1;
                        }
                    }
                    if ($toCheck == 0) {
                        continue;
                    }
                    $rs = Wagelib::queryBatch($order['wagBatNo'], $order['compUsrNo']);
                    Trace::addLog('wage_getBatch_data', 'info', ['rs' => $rs, 'order' => $order, 'distance' => $distance, 'toCheck' => $toCheck]);
                    if ($rs['errno'] == 200) {
                        $rs = Wagelib::updateStatus($rs['data'], $order['wagBatNo'], $order['compUsrNo']);
                        Trace::addLog('wage_update_data', 'info', ['rs' => $rs, 'order' => $order]);
                        if ($rs['errno'] == 200) {
                            continue;
                            //return true;
                        }
                    }
                }
            }
        } catch (\ErrorException $e) {
            Trace::addLog('wageCommand_checkStatus', 'error', ['rs' => $rs, 'order' => $order]);
        }
    }

    /**
     * 第一次发工资，获取用户的状态
     *
     * @return array
     **/
    public function actionSendfirst()
    {
        $repeatTimes = 5;
        $key = Yii::$app->params['redisKey']['toSendWage'];
        $redis = Yii::$app->redis;
        while ($repeatTimes--) {
            $len = $redis->LLEN($key);
            $doubleKey = Yii::$app->params['redisKey']['doubleWrite'];
            while ($len--) {
                $json = $redis->RPOP($key);
                Trace::addLog('wage_pop_queue', 'info', ['rs' => $json]);
                $data = json_decode($json, true);
                /*
                $row = Auth::findOne('1613');
                $auc = json_decode($row->auc_ext, true);
                $data = [
                'order'=> '78961e847e780e12e8b2d414619b5b01',
                //包含了cipher等鉴权中心返回的内容
                'auc' =>  $auc,
                ];
                 */

                $md5 = Redis::createDoubleKey($json);
                $rs = $this->preData($data, $md5);
                if ($rs['errno'] != 200) {
                    continue;
                }
                try {
                    $rs = Wagelib::processAddWage($data, $key);
                    //顺利的走到这里之后，清除双写的数据
                    if (isset($rs['errno']) && $rs['errno'] == 200) {
                        if (!$redis->HGET($doubleKey, $md5)) {
                            Trace::addLog('no_double_write', 'warning', ['key' => $doubleKey, 'key' => $md5, 'json' => $json]);
                            continue;
                        }
                        $redis->HDEL($doubleKey, $md5);
                    }
                } catch (\ErrorException $e) {
                    Trace::addLog('add_wage_exception', 'error', ['params' => $data, 'msg' => $e->getMessage(), 'line' => $e->getLine()]);
                    continue;
                }
            }
            //休息10S，尽量保证1S内的尽可能快的发出去
            sleep(10);
        }
        try {
            //遍历
            $allData = $redis->HGETAll($doubleKey);
            for ($i = 0, $end = count($allData); $i < $end; $i += 2) {
                $key = $allData[$i];
                $data = json_decode($allData[$i + 1], true);
                if (!isset($data['sendTimes'])) {
                    $data['sendTimes'] = 0;
                }
                $rs = $this->preData($data, $key);
                if ($rs['errno'] != 200) {
                    continue;
                }
                $rs = Wagelib::processAddWage($data, $key);
                //顺利的走到这里之后，清除双写的数据
                if (isset($rs['errno']) && $rs['errno'] == 200) {
                    $rs = $redis->HDEL($doubleKey, $key);
                }
            }
        } catch (\ErrorException $e) {
            Trace::addLog('add_wage_exception', 'error', ['params' => $data, 'msg' => $e->getMessage(), 'line' => $e->getLine()]);
        }
    }

    /**
     * 发工资之前对数据进行判断.
     *
     * @return array
     **/
    public function preData($data, $md5)
    {
        $redis = Yii::$app->redis;
        $doubleKey = Yii::$app->params['redisKey']['doubleWrite'];
        if (!$redis->HGET($doubleKey, $md5)) {
            //已经处理过了
            Trace::addLog('add_wage_processed_already', 'warning', $data);

            return ['errno' => 400, 'msg' => '处理完毕'];
        }
        if (!isset($data['auc']['ext'])) {
            Trace::addLog('add_wage_no_ext', 'warning', $data);

            return ['errno' => 400, 'msg' => 'no ext'];
        }

        if (!is_array($data) || !isset($data['order'])) {
            Trace::addLog('add_wage_data_error', 'error', $data);

            return ['errno' => 400, 'msg' => 'no order'];
        }

        return ['errno' => 200, 'msg' => 'ok'];
    }
    /**
     * 添加好友，测试.
     **/
    public function actionAdd()
    {
        /*
        $user = User::GetUlistByPhoneNumArr([19100000201]);
        var_dump($user);
        die;
         */
        $order = '28c13530d9920e65e00e6ffc56b2b1f5';
        $uid = '551120284602606092';
        $CompArr = User::getUserInfo($uid);
        $rs = Wage::find()->where(['wagBatNo' => $order])->all();
        $db = Yii::$app->db;
        $db->open();
        $tabName = Wage::choseTable($uid);
        $sql = "select cusPhone, compUsrNo from {$tabName}";
        //$sql = "select cusPhone, compUsrNo from {$tabName} where wagBatNo ='{$order}'";
        $rowset = $db->createCommand($sql)->queryAll();
        foreach ($rowset as $row) {
            $user = User::GetUlistByPhoneNumArr([19200001125]);

            //$user = User::GetUlistByPhoneNumArr([$row['cusPhone']]);
            $userData = $user['data'];
            $tmp = array_keys($userData);
            var_dump($tmp);
            if (count($tmp) == 0) {
                continue;
            }
            $uuid = $tmp[0];
            var_dump($uuid);
            $userData = $userData[$uuid];
            var_dump($userData);
            $Friends = [
                'companyname' => $CompArr['data']['ext']['company_name'],
                'companyid' => $row['compUsrNo'],
                'userid' => $userData['user_id'],
                'username' => $userData['user_name'],
                //'username' => $row->cusName,
            ];
            var_dump($user);
            var_dump($Friends);
            $rs = Friendsapi::addFriend($Friends);
            var_dump($rs);
            die;
        }
    }

    /**
     * undocumented function.
     *
     * @author Me
     **/
    public function actionTest()
    {
        $row = Auth::findOne('286');
        //var_dump($row->auc_ext);
        //var_dump(json_decode($row->auc_ext, true));
        $auc = json_decode($row->auc_ext, true);
        var_dump($auc['ext']);
        var_dump(json_decode($auc['ext'], true));
    }

    /**
     * 创建数据库表.
     *
     * @return bool
     **/
    public function actionCreate()
    {
        $tabNum = Yii::$app->params['maxTable'];
        $db = Yii::$app->db;
        $db->open();
        for ($i = 0; $i < $tabNum; ++$i) {
            $sql = "SHOW TABLES LIKE 'wage_{$i}'";
            $res = $db->createCommand($sql)->queryOne();
            $createDb = "
                 CREATE TABLE `wage_{$i}` (
                   `id` int(10) NOT NULL AUTO_INCREMENT,
                    `orderId` varchar(20) DEFAULT '' COMMENT '代发工资流水号',
                    `sign` char(32) DEFAULT '' COMMENT 'MD5签名结果',
                    `compUsrNo` varchar(20) DEFAULT '' COMMENT '企业用户号：企业作为用户在支付平台的唯一用户号',
                    `wagBatNo` varchar(50) NOT NULL DEFAULT '' COMMENT '批次id',
                    `createtime` int(10) DEFAULT '0' COMMENT '创建时间',
                    `updatetime` int(10) DEFAULT '0' COMMENT '当前修改时间',
                    `paytime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '支付返回的payTime',
                    `cusName` varchar(30) DEFAULT '' COMMENT '姓名',
                    `cusPhone` varchar(11) DEFAULT '' COMMENT '手机号',
                    `cusIdNo` varchar(18) DEFAULT '' COMMENT '身份证号',
                      `UsrNo` varchar(20) DEFAULT '' COMMENT '用户在支付平台的唯一用户号',
                      `cusAmount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '发放的工资金额，单位为分',
                      `status` char(2) DEFAULT '' COMMENT 'S 发工资成功, F发失败, R 满足了发放条件，可以发送或者已经发送尚未查询到结果,U 已经注册，尚未实名认证,N 未注册借贷宝, A已经授权完毕, D已经被删除',
                      `tranDate` int(10) DEFAULT '0' COMMENT '工资代发时间也就是工资隶属年月即请求日期',
                      `rspCode` varchar(10) DEFAULT '' COMMENT '应答码',
                      `rspMessage` varchar(255) DEFAULT '' COMMENT '应答信息',
                      `source` varchar(10) DEFAULT '' COMMENT '发工资来源（web app ....）',
                      `remk` varchar(255) DEFAULT '' COMMENT '备注',
                      `remk1` varchar(255) DEFAULT '' COMMENT '备注1',
                      `remk2` varchar(255) DEFAULT '' COMMENT '备注2',
                      `remk3` varchar(255) DEFAULT '' COMMENT '备注3',
                      `tradeId` varchar(20) NOT NULL DEFAULT '' COMMENT '支付的订单id',
                      `transId` char(100) NOT NULL DEFAULT '' COMMENT '支付的id',
                      `uuid` char(50) NOT NULL DEFAULT '' COMMENT '用户在用户中心的uid',
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `orderId` (`orderId`),
                      KEY `wagBatNo` (`wagBatNo`)
                    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='发工资记录表'";
            if (!$res) {
                $exec = $db->createCommand($createDb)->execute();
                var_dump($exec);
            }
        }
    }

    /**
     * 将数据从wage 迁移到分表之中.
     **/
    public function actionMoveData()
    {
        $conn = Yii::$app->db;
        $conn->open();
        $str = '`orderId` ,`sign` , `compUsrNo` , `wagBatNo` , `createtime` ,`updatetime` ,`paytime` ,`cusName` , `cusPhone` , `cusIdNo` , `UsrNo` , `cusAmount` , `status` , `tranDate` , `rspCode` , `rspMessage` , `source` , `remk`, `remk1` , `remk2` , `remk3` , `tradeId` , `transId` , `uuid` ';
        //`orderId` ,`sign` , `compUsrNo` , `wagBatNo` , `createtime` ,`updatetime` ,`paytime` ,`cusName` , `cusPhone` , `cusIdNo` , `UsrNo` , `cusAmount` , `status` , `tranDate` , `rspCode` , `rspMessage` , `source` , `remk`, `remk1` , `remk2` , `remk3` , `tradeId` , `transId` , `uuid`
        $sql = 'select * from wage';
        $rowset = $conn->createCommand($sql)->queryAll();
        try {
            foreach ($rowset as $row) {
                $tabName = Wage::choseTable($row['compUsrNo']);
                $copy = "insert into {$tabName}({$str}) select {$str}  from wage where id = {$row['id']}";
                $rs = $conn->createCommand($copy)->execute();
                var_dump($rs);
            }
        } catch (\ErrorException $e) {
            var_dump($e->getMessage());
            die;
        }
        /*
    insert into wage_0({$str}) select {$str}  from Table1 where id = {};
    a,c,5
    from Table1";
     */
    }

    /**
     * 将wage表数据数据更新到batch表.
     */
    public function actionCreatebatch()
    {
        $db = Yii::$app->db;
        $db->open();
        $BatchModel = new Batch();
        //获取wage表数据
        $list = Wage::find()->select('wagBatNo')->distinct()->asArray()->all();
        foreach ($list as $wageData) {
            $wagBatNo = $wageData['wagBatNo'];
            $betchData = Batch::find()->where(['wagBatNo' => $wagBatNo])->one();
            if (empty($betchData)) {
                //获取wagBatNo的统计数据
                $getListBywagBatNo = Wage::find()->where(['wagBatNo' => $wagBatNo])->asArray()->all();
                $cusAmount = 0; //发工资的总金额
                $status = 'A'; //批次受理的状态,S 是成功，D是删除，A是已经鉴权ok C 是已经确认过
                $number = 0; //发工资人的数量
                $compId = '';
                foreach ($getListBywagBatNo as $wagBatNoInfo) {
                    $createTime = $wagBatNoInfo['createtime'];
                    $cusAmount += $wagBatNoInfo['cusAmount'];
                    $number += 1;
                    $compId = $wagBatNoInfo['compUsrNo'];
                }
                //入库数据到batch表
                /*
                $BatchModel->allBatch = $wagBatNo;//总的批次
                $BatchModel->wagBatNo = $wagBatNo;//批次id
                $BatchModel->createtime = $createTime;//创建时间
                $BatchModel->number = $number;//发工资的人的数量
                $BatchModel->amount = $cusAmount;//发工资的总金额
                $BatchModel->status = $status;//批次受理的状态,S 是成功，D是删除，A是已经鉴权ok C 是已经确认过
                $BatchModel->compId = $compId;//公司的id
                $BatchModel->succCount = 0;//成功笔数
                $BatchModel->failCount = 0;//失败笔数
                $BatchModel->save();
                 */
                $update = [
                    'allBatch' => $wagBatNo,
                    'wagBatNo' => $wagBatNo,
                    'createtime' => $createTime,
                    'number' => $number,
                    'amount' => $cusAmount,
                    'status' => 'A',
                    'compId' => $compId,
                ];
                $rs = $db->createCommand()->insert('batch', $update)->execute();
                var_dump($rs);
                die;
            }
        }
    }

    /**
     * 修复批次中的统计信息.
     *
     * @return array
     **/
    public function actionFixbatch()
    {
        $conn = Yii::$app->db;
        $conn->open();
        $sql = 'select id, wagBatNo, compId from batch';
        $rowset = $conn->createCommand($sql);
        foreach ($rowset as $row) {
            $rs = Wage::updateBatchCount($row['wagBatNo'], $row['compId']);
            var_dump($rs);
            die;
        }
    }

    /**
     * 获取所有进行中的工资.
     *
     * @author doujm
     **/
    public function actionPending()
    {
        $maxTable = Yii::$app->params['maxTable'];
        $pendStatus = Yii::$app->params['wageStatusKey']['pending'];
        $str = "'".implode("','", $pendStatus)."'";
        $conn = Yii::$app->db;
        $conn->open();
        for ($i = 0; $i < $maxTable; ++$i) {
            //遍历所有的表
            $tabName = Wage::choseTable($i);
            $sql = "select id,createtime,wagBatNo, cusName, cusPhone, compUsrNo, status from {$tabName} where status in({$str})";
            $rowset = $conn->createCommand($sql);
            foreach ($rowset as $row) {
                Trace::addLog('wage_pending_data', 'error', array_merge($row, ['table' => $tabName]));
            }
        }
    }
    /**
     * 解冻接口
     * 1.查询所有的企业id分批循环
     * 2.查询当前批次下的总额数据
     * 3.调用解冻接口处理解冻.
     */
    public function actionDefreeze()
    {
        //查询最后修改解冻的记录时间
        $startTime = time();
        Trace::addLog('wage_defreeze_execute', 'info', [
            'starttime' => $startTime,
        ]);
        $failStatus = Yii::$app->params['wageStatusKey']['fail'];
        $maxTable = Yii::$app->params['maxTable'];
        $timeStamp = time() - Yii::$app->params['payment']['defreezeRedisExpire'];
        $db = Yii::$app->db;
        $db->open();
        for ($i = 0; $i < $maxTable; ++$i) {
            $tabName = Wage::choseTable($i);
            $wagBatNos = Wage::getCompUsrNoListByDstinct($tabName, $failStatus, $timeStamp);
            if (count($wagBatNos) === 0) {
                continue;
            }
            foreach ($wagBatNos as $wagBat) {
                $wagBatNoArr = Wage::getWageListInStatus($wagBat['compUsrNo'], $wagBat['wagBatNo'], $failStatus);
                $wagCountAmount = Wage::getWageCountAmountInStatus($wagBat['compUsrNo'], $wagBat['wagBatNo'], $failStatus);
                $payment = payment::getQuerydetail($wagBat['compUsrNo']);
                if (!isset($payment['errno']) || $payment['errno'] != 200) {
                    Trace::addLog('wage_defreeze_payDetail_res', 'error', [
                        'res' => $payment,
                        'wagBat' => $wagBat,
                    ]);
                    continue;
                }
                if ($wagCountAmount > (int) (100 * $payment['data']['frozenTrue'])) {
                    //解冻的金额，不能大于用户自身的冻结金额
                    Trace::addLog('wage_defreeze_exceed_frozed', 'error', [
                        'res' => $payment,
                        'wagBat' => $wagBat,
                        'amount' => $wagCountAmount,
                    ]);
                    continue;
                }
                if ($wagCountAmount > 0 && count($wagBatNoArr)) {
                    $trans = $db->beginTransaction();
                    try {
                        $data = Payment::Defreeze($wagBat['compUsrNo'], $wagBat['wagBatNo'], $wagCountAmount);
                        if (isset($data['errno']) && $data['errno'] == 200) {
                            foreach ($wagBatNoArr as $wagBatNoData) {
                                //更改批次的状态
                                $updateRet = Wage::updateStatusById(
                                    $wagBatNoData['id'],
                                    $wagBatNoData['compUsrNo'],
                                    Yii::$app->params['wageStatusKey']['defreeze'][0]
                                );
                                if ($updateRet['errno'] != 200) {
                                    Trace::addLog('wage_defreeze_update_error', 'error', [
                                        'res' => $updateRet,
                                        'row' => $wagBatNoData,
                                    ]);
                                    throw new Exception($updateRet['msg'], '400');
                                }
                            }
                        } else {
                            //解冻失败,也可能是超时，也可能是因为没钱了
                            Trace::addLog('wage_defreeze_payment_error', 'error', [
                                'res' => $data,
                                'wagBat' => $wagBat,
                            ]);
                        }
                        $trans->commit();
                    } catch (\ErrorException $e) {
                        $trans->rollback();
                        Trace::addLog('wage_defreeze_exception', 'warning', [
                            'wagBat' => $wagBat,
                            'msg' => $e->getMessage(),
                            'code' => $e->getCode(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * 实时发工资.
     *
     * @author doujm
     **/
    public function actionOntimeResend()
    {
        $conn = Yii::$app->db;
        $length = 1;
        //获取所有发送失败的，再次重发
        $maxTable = Yii::$app->params['maxTable'];
        for ($i = 0; $i < $maxTable; ++$i) {
			//$i = 99;
            //遍历所有的表
            $tabName = Wage::choseTable($i);
            $offset = 0;
            $cnt = 1000;
            while ($cnt--) {
                $failStatus = Yii::$app->params['wageStatusKey']['fail'];
                $statusStr = "'".implode("','", $failStatus)."'";
                $sql = "select id, cusName, cusPhone, status,compUsrNo  from {$tabName} where status in({$statusStr}) limit {$offset}, {$length}";
                $offset += $length;
                //$sql = "select id, cusPhone, status from wage where status != 'S' && createtime >= '{$time}'";
                $command = $conn->createCommand($sql);
                $rowset = $command->queryAll();
                $phones = [];
                foreach ($rowset as $order) {
                    $phones[] = $order['cusPhone'];
                }
                $userStatus = User::batchCheckStatus($phones);
                if ($userStatus['errno'] != 200) {
                    continue;
                }
                foreach ($rowset as $row) {
                    if (!isset($userStatus['data'][$row['cusPhone']])) {
                        Trace::addLog('wage_resend_no_batch_status', 'error', $row);
                        continue;
                    }
                    $status = $userStatus['data'][$row['cusPhone']];
					
					if (isset($status['name']) && $status['name'] != $row['cusName']) {
						$status['status'] = 'F';
						$update['remk1']  = '姓名手机号不匹配';
					}
                    //更新状态
                    try {
                        if ($status['status'] != $row['status']) {
                            $conn->createCommand()->update($tabName,
                                ['remk1' => $status['remk1'], 'status' => $status['status']],
                                'id = '.$row['id'])->execute();
                        }
                        if ($status['status'] == 'R') {
                            //满足发放条件
                            $rs = Payment::sendSingle($order['id'], $order['compUsrNo']);
                        }
                    } catch (\ErrorException $e) {
                        $log = [
                            'msg' => $e->getMessage(),
                            'data' => $rs['data'],
                            'old' => $order,
                        ];
                        Trace::addLog('update_wageStatus_exception', 'warning', $log);
                    }
                }
                if (count($rowset) < $length) {
                    //不足200个就break；处理下一个表
                    break;
                }
            }
        }
    }

    /**
     * 获取企业的好友.
     *
     * @author doujm
     **/
    public function actionUser()
    {
        $data = Wage::getCompUser('548901350163882167', '19100000099');
        var_dump($data);
    }
}
