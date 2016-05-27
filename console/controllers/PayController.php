<?php
/*************************************************************************
> File Name :     ./commands/DrawController.php
> Author :        unasm
> Mail :          douunasm@gmail.com
> Last_Modified : 2016-01-19 17:35:08
 ************************************************************************/
namespace console\controllers;

use apps\lib\Qiyeapp;
//use apps\commands\BaseController as Controller;
use apps\lib\Trace;
use apps\lib\User;
use apps\models\Wage;
use console\controllers\BaseController;
use Yii;

/**
 * 工资查询
 **/
class PayController extends BaseController
{

    public function actionBatch()
    {
        $wagBatNo = '6d2ebad5596887c6faa61d811d044e22';
        $compId   = '581048545218116699';
        $rs       = Wagelib::queryBatch($wagBatNo, $compId);
        var_dump($rs);
        if ($rs['errno'] == 200) {
            $rs = Wagelib::updateStatus($rs['data'], $wagBatNo, $compId);
            var_dump($rs);
            if ($rs['errno'] == 200) {
                return true;
            }
        }
    }

    /**
     * 第一次发工资，获取用户的状态
     *
     * @return array
     **/
    public function actionPop()
    {
        $repeatTimes = 5;
        $key         = Yii::$app->params['redisKey']['toSendWage'];
        $redis       = Yii::$app->redis;
        echo date("Y-m-d H:i:s") . " start to process" . PHP_EOL;
        $len = $redis->LLEN($key);
        var_dump($len);
        $doubleKey = Yii::$app->params['redisKey']['doubleWrite'];
        while ($len--) {
            $json = $redis->RPOP($key);
            Trace::addLog("wage_pop_dispatch", 'info', ['rs' => $json]);

        }
    }
    /**
     * 查询提现的状态
     *
     **/
    public function actionQuery()
    {
        $rs = Qiyeapp::getWageData('548901350163882167');
        //$rs = User::getUserInfo('555343341357637869');
        //$rs = User::GetUlistByPhoneNumArr([18520701753]);
        //$rs = Payment::getQueryDetail('581048545218116699');
        var_dump($rs);
        die;
        //$rs = Wagelib::queryBatch('c4e4f5e87dcc6416d6cf5d96e2945486', '587318040062845375');
        //$rs = User::AvaileCompany('588360958337192709');
        $db = Yii::$app->db;
        $db->open();
        $sql    = "select * from auth where id = 2395";
        $rowset = $db->createCommand($sql)->queryOne();
        $tmp    = json_decode($rowset['auc_ext'], true);
        if (!is_array($tmp)) {
            var_dump($rowset);
            die;
        }
        $tmp           = json_decode($tmp['ext'], true);
        $auc['key']    = $tmp['secretKey'];
        $auc['cipher'] = $tmp['cipher'];
        $auc['from']   = "app";

        $rs = Wagelib::addWage('80a4e004e7ac8210734aedb41cb64588', $auc);
        //$rs = Wagelib::addWage($wage['wagBatNo'], $auc);
        var_dump($rs);
    }
    /**
     * 维护邀请码的更新
     *
     **/
    public function actionFixchannel()
    {
        echo "start : " . PHP_EOL;
        $filename = '/data/apps/enterprise/data.txt';
        $fp       = fopen($filename, 'r');
        $j        = 0;
        $db       = Yii::$app->db;
        $db->open();
        $sql     = "select company_id from jdb_company_info where is_valid = 1";
        $rowsets = $db->createCommand($sql)->queryAll();
        try {
            while ($line = fgets($fp)) {
                $line   = trim($line);
                $arr    = preg_split('/[\s,]+/', $line);
                $sql    = "select * from jdb_company_info where is_valid = 1 && extension_code = '{$arr[0]}'";
                $rowset = $db->createCommand($sql)->queryAll();
                $error  = 0;
                foreach ($rowset as $row) {
                    $subSql = "select * from jdb_extension_company where company_id = '{$row['company_id']}' && extension_code = '{$arr[0]}'";
                    $subRow = $db->createCommand($subSql)->queryAll();
                    $cnt    = count($subRow);
                    if ($cnt != 1) {
                        var_dump($row);
                        var_dump("数据异常，jdb_extend_company 有{$cnt} 条数据");
                        continue;
                    }
                    if ($subRow[0]['extension_code'] != $arr[0]) {
                        var_dump($row);
                        var_dump("邀请码不一致");
                        //    continue;
                    }
                    Trace::addLog('pay_company_info_old_data', 'info', $row);
                    Trace::addLog('pay_extendsion_info_old_data', 'info', $subRow[0]);
                    $two = $db->createCommand()->update(
                        'jdb_company_info', ['extension_code' => $arr['1']],
                        'id = ' . $row['id']
                    )->execute();
                    $one = $db->createCommand()->update('jdb_extension_company',
                        ['extension_code' => $arr['1']],
                        'id = ' . $subRow[0]['id']
                    )->execute();
                }
                echo "{$row['id']} 和 $subRow[0]['id'] 对应更新" . PHP_EOL;
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getLine());
        }
    }

    public function actionCreate()
    {
        $tabNum = Yii::$app->params['maxTable'];
        $db     = Yii::$app->db;
        $db->open();
        $file = "/data/apps/enterprise/index.sql";

        /*
        $sql = "alter table `auth` add index uid (uid);";
        $rs = $db->createCommand($sql)->execute();
        file_put_contents($file, $sql . PHP_EOL, FILE_APPEND | LOCK_EX);

        $sql = "alter table `jdb_company_info` add  column `bankExt`  text COMMENT '企业的银行其他字段信息'";
        $rs = $db->createCommand($sql)->execute();
        file_put_contents($file, $sql . PHP_EOL, FILE_APPEND | LOCK_EX);

        $sql = "alter table batch add index allBatch(allBatch);";
        $rs = $db->createCommand($sql)->execute();
        file_put_contents($file, $sql . PHP_EOL, FILE_APPEND | LOCK_EX);
        $sql = "alter table `jdb_verify_code` add index phone_num(phone_num);";
        $rs = $db->createCommand($sql)->execute();
        file_put_contents($file, $sql . PHP_EOL, FILE_APPEND | LOCK_EX);

        $sql = "alter table `jdb_querycharge` add index company_id(company_id);";
        file_put_contents($file, $sql . PHP_EOL, FILE_APPEND | LOCK_EX);
        $rs = $db->createCommand($sql)->execute();
         */
        for ($i = 11; $i < $tabNum; $i++) {
            $tabName = Wage::choseTable($i);
            //$sql = "alter table "
            $sql1 = "alter table `{$tabName}` add index  cusPhone(cusPhone);";
            $sql2 = "alter table `{$tabName}` add index compUsrNo(compUsrNo);";
            /*
            $rs = $db->createCommand($sql1)->execute();
            $rs = $db->createCommand($sql2)->execute();
             */
            file_put_contents($file, $sql1 . PHP_EOL, FILE_APPEND | LOCK_EX);
            file_put_contents($file, $sql2 . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }
    public function actionExcel()
    {
        $uid      = '568835951258771475';
        $filename = '/Users/liuyinkuo/wageImport';
        $patterns = "/\d+/";
        try {
            echo "start : " . PHP_EOL;
            $fp      = fopen($filename, 'r');
            $compArr = User::getUserInfo($uid);
            if (!$compArr['data']['bankinfo']['company_bank_name']) {
                throw new Exception('no company name', 1);
            }

            $j = 0;
            while ($line = fgets($fp)) {
                echo $j . PHP_EOL;
                $j++;
                echo date('Y-m-d H:i:s') . PHP_EOL;
                preg_match_all($patterns, $line, $explodeArr);
                if (strlen($explodeArr[0][0]) != 11) {
                    echo "phone length wrong: " . $explodeArr[0][0] . PHP_EOL;
                    continue;
                }

                echo "this uuid: " . $explodeArr[0][0] . PHP_EOL;

                $user = User::GetUlistByPhoneNumArr([$explodeArr[0][0]]);

                if (!isset($user['data'])) {
                    var_dump($user, $explodeArr);
                    echo "no index data" . PHP_EOL;
                    continue;
                }
                $userData   = $user['data'];
                $uuidResult = array_keys($userData);
                if (count($uuidResult) == 0) {
                    echo "count uuid result is 0: " . PHP_EOL;
                    continue;
                }
                $uuid = $uuidResult[0];
                echo "uuid: " . $uuid . PHP_EOL;

                if (!$userData[$uuid]['vali_status']['real_name']) {
                    echo "未实名的人: " . $uuid . PHP_EOL;
                    echo "尚未实名" . PHP_EOL;
                    continue;
                }

                $userData = $userData[$uuid];

                echo "user data: " . date('Y-m-d H:i:s') . PHP_EOL;
                print_r($userData);
                echo PHP_EOL;
                $firends = [
                    'companyname' => $compArr['data']['bankinfo']['company_bank_name'],
                    'companyid'   => $uid,
                    'userid'      => $userData['user_id'],
                    'username'    => $userData['user_name'],
                ];

                echo "user: " . date('Y-m-d H:i:s') . PHP_EOL;
                print_r($user);
                echo PHP_EOL;

                echo "Friends: " . date('Y-m-d H:i:s') . PHP_EOL;
                print_r($firends);
                echo PHP_EOL;

                $rs = Friendsapi::addFriend($firends);

                echo "result: " . date('Y-m-d H:i:s') . PHP_EOL;
                print_r($rs);
                echo PHP_EOL;
                echo "success" . PHP_EOL;
            }
            echo date('Y-m-d H:i:s') . PHP_EOL;
            echo "Finish" . PHP_EOL;
        } catch (Exception $e) {
            echo date('Y-m-d H:i:s') . PHP_EOL;
            echo $e->getMessage() . PHP_EOL;
            echo $e->getCode() . PHP_EOL;
        }
    }

    public function actionChange()
    {
        $wagBatNo = '8ad66ca5d54958d6bbb6227cabbac63d';
        $uid      = '581048545218116699';

        $tabName = Wage::choseTable($uid);
        $db      = Yii::$app->db;
        $db->open();
        $trans  = $db->beginTransaction();
        $sql    = "select * from batch where `wagBatNo` = '{$wagBatNo}' && compId = '{$uid}'";
        $row    = $db->createCommand($sql)->queryOne();
        $newBat = Wage::buildWagBatNo($uid); //当前批次号
        var_dump($row);
        var_dump($newBat);
        if (empty($row)) {
            var_dump("批次为空");
            die;
        }
        try {
            $db->createCommand()->update('batch', [
                'wagBatNo' => $newBat,
            ], 'id = ' . $row['id'])->execute();
            $rowset = $db->createCommand("select * from {$tabName} where wagBatNo = '{$wagBatNo}'")->queryAll();
            foreach ($rowset as $row) {
                $rs = $db->createCommand()->update($tabName,
                    ['wagBatNo' => $newBat, 'orderId' => Wage::buildOrderNo($uid)],
                    'id = ' . $row['id']
                )->execute();
                if (!$rs) {
                    $trans->rollback();
                    die("error");
                }

            }
            $rs = $trans->commit();
            //$newBat = $wagBatNo;
            //$sql = "select * from auth where id = (select max(id) from auth where uid = '{$uid}' && last_event = 0  && type = 'wag
            //$sql = "select * from auth where id = 2395";
            $sql    = "select * from auth where id = (select max(id) from auth where uid = '{$uid}' && last_event = 0  && type = 'wage')";
            $rowset = $db->createCommand($sql)->queryOne();
            $tmp    = json_decode($rowset['auc_ext'], true);
            if (!is_array($tmp)) {
                var_dump($rowset);
                die;
            }
            $tmp           = json_decode($tmp['ext'], true);
            $auc['key']    = $tmp['secretKey'];
            $auc['cipher'] = $tmp['cipher'];
            $auc['from']   = "app";
            var_dump($sql);
            var_dump($rowset);
            var_dump($auc);
            var_dump($newBat);
            $rs = Wagelib::addWage($newBat, $auc);
            var_dump($rs);
            die;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump("asdfa");
            $trans->rollback();
        }
    }

    /**
     * path
     *
     * @return void
     * @author Me
     **/
    public function actionData()
    {
        $filename = '/data/apps/enterprise/wage.txt';
        $fp       = fopen($filename, 'r');
        $uid      = '587954888749985149';
        $wrongBat = 'eb2a006dac33466da686f5d66992d396';
        $db       = Yii::$app->db;
        $db->open();
        $trans = $db->beginTransaction();
        try {
            while ($line = fgets($fp)) {
                $arr     = json_decode($line, true);
                $data    = $arr['context']['origin'];
                $tabName = Wage::choseTable($i);
                $sql     = "select * from {$tabName} where id = {$data['id']}";
                $rowset  = $db->createCommand($sql)->queryOne();
                if ($rowset['wagBatNo'] != $wrongBat) {
                    echo "批次号不符" . PHP_EOL;
                }
                if ($rowset['compUsrNo'] != $uid) {
                    echo "非该企业" . PHP_EOL;
                }

                Trace::addLog("wage_batch_fix", 'info', ['old' => $data, 'new' => $rowset]);
                $rs = $db->createCommand()->update($tabName,
                    ['wagBatNo' => $data['wagBatNo'], 'orderId' => $data['orderId']],
                    'id = ' . $data['id']
                )->execute();
                if (!$rs) {
                    var_dump("asdfa");
                    $trans->rollback();
                    die("update error");
                }
            }
            $trans->commit();
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getLine());
            $trans->rollback();
        }

    }

}
