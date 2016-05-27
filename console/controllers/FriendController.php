<?php
/*************************************************************************
> File Name :     ./commands/DrawController.php
> Author :        unasm
> Mail :          douunasm@gmail.com
> Last_Modified : 2016-01-19 17:35:08
 ************************************************************************/
namespace console\controllers;

//use yii\console\Controller;
use apps\lib\Friendsapi;
use apps\lib\Trace;
use apps\models\Wage;
use console\controllers\BaseController;
use Yii;
use yii\base\ErrorException;

/**
 * 工资查询
 **/
class FriendController extends BaseController
{
    /**
     * 查询提现的状态
     *
     * 每个月的月初一号触发的脚本，删除没发工资的好友
     *
     **/
    public function actionDelete()
    {
        //减去一天，保证获取的是上一个月的时间
        $lastTime = time() - 24 * 3600;
        //获取上个月月初的时间戳
        $timeStamp = strtotime(date('Y-m', $lastTime) . '-01 00:00:00');
        $maxTable  = Yii::$app->params['maxTable'];
        $db        = Yii::$app->db;
        $db->open();
        //for ($i = 40; $i < $maxTable; $i++) {
        for ($i = 0; $i < $maxTable; $i++) {
            $tabName = Wage::choseTable($i);
            //$sql = "select distinct compUsrNo from {$tabName} where compUsrNo = '551142872456372250'";
            try {
                $sql  = "select distinct compUsrNo from {$tabName}";
                $list = $db->createCommand($sql)->queryAll();
                foreach ($list as $company) {
                    $list = Friendsapi::getAllList($company['compUsrNo'], 1);
                    if ($list['errno'] != 200) {
                        Trace::addLog('get_friendsAll_error', 'warning', ['company' => $company, 'list' => $list]);
                        continue;
                    }
                    if (!isset($list['data']['idlist']) || count($list['data']['idlist']) == 0) {
                        continue;
                    }
                    $DeleteStatus = "'" . implode("','", Yii::$app->params['wageStatusKey']['delete']) . "'";
                    //获取一个月内发了工资的,并不计较成功，或者失败
                    $sql = "select distinct cusIdNo, uuid, cusPhone, status, cusName from {$tabName} where createtime > '{$timeStamp}' && compUsrNo = '{$company['compUsrNo']}' && status not in ({$DeleteStatus})";
                    //$sql = "select cusPhone from wage where createtime > '{$timeStamp}' && compUsrNo = '{$company['compUsrNo']}'";
                    $sendedList = $db->createCommand($sql)->queryAll();
                    foreach ($list['data']['idlist'] as $user) {
                        $found = 0;
                        //并不计较未成为好友的人，只从已经成为好友的人里面删除未发工资的
                        foreach ($sendedList as $li) {
                            if (($li['cusPhone'] == $user['mobile'])) {
                                $found = 1;
                                break;
                            }
                        }
                        if ($found == 0) {
                            //证明没有发工资
                            Friendsapi::deleteFriend(['companyid' => $company['compUsrNo'], 'userid' => $user['uuid']]);
                        }
                    }
                }
            } catch (\ErrorException $e) {
                $log = [
                    'msg'  => $e->getMessage(),
                    'line' => $e->getLine(),
                ];
                Trace::addLog('friend_delete_exception', 'error', $log);
            }
        }
    }
}
