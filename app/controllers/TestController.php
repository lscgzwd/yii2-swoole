<?php
/*************************************************************************
获取地推区域数据
 ************************************************************************/
namespace apps\controllers;

use apps\lib\Common;
use apps\lib\ExtensionApi;
use apps\lib\Image;
use apps\lib\Payment;
use apps\lib\Trace;
use apps\lib\User;
use apps\models\Batch;
use apps\models\CompanyInfo;
use apps\models\QueryCharge;
use apps\models\Region;
use apps\models\Wage;
use common\models\CompanyStaffModel;
use Yii;

/**
 * 测试类
 **/
class TestController extends BaseController
{
    public function actionIndex()
    {
        $request    = Yii::$app->request;
        $parent_id  = $request->get('parent_id', null);
        $regionList = Region::getRegionList($parent_id);
        return ['code' => !empty($regionList) ? 200 : 500, 'msg' => !empty($regionList) ? 'ok' : '当前数据信息不存在', 'info' => !empty($regionList) ? '成功获取信息' : '当前数据信息不存在', 'data' => $regionList];
    }

    /**
     * @return array
     * 验证码生成
     */
    public function actionVerifynum()
    {
//        $VerifyNum = new VerifyNum();
        //        $output = $VerifyNum->doimg();
        $output = Image::buildImageVerify(4);
        var_dump($output);die;
        //$output = Image::GBVerify(4);var_dump($imagesObj);die;
    }

    /**
     * 充值记录查询定时任务例子
     */
    public function actionGetcompanylist()
    {
        $count      = CompanyInfo::getCount();
        $begin_time = strtotime('20150101');
        $end_time   = strtotime('20160202');
        for ($i = 0; $i <= $count; $i++) {
            $dataList = CompanyInfo::getPageList(1, $i);
            foreach ($dataList as $dataInfo) {
                $company_id = $dataInfo['company_id'];
                //累计充值
                $seachParam = array(
                    'comp_jdbid' => $company_id,
                    'begin_time' => $begin_time,
                    'end_time'   => $end_time, //date('Ymd'),
                    'order_id'   => '',
                );
                $rest               = Payment::queryChargeList($seachParam);
                $addQueryChargeData = array();
                if ($rest['errno'] == 200) {
                    $addQueryChargeData['transId'] = $rest['data']['transId'];
                    $detail                        = json_decode($rest['data']['detail'], true);
                    foreach ($detail as $detailInfo) {
                        $addQueryChargeData['compJdbId']  = $company_id; //$rest['data']['compJdbId'];
                        $addQueryChargeData['amount']     = $detailInfo['amount'];
                        $addQueryChargeData['bankNo']     = $detailInfo['bankNo'];
                        $addQueryChargeData['chargeDate'] = $detailInfo['chargeDt'];
                        $addQueryChargeData['chargeTime'] = $detailInfo['chargsTm'];
                        $addQueryChargeData['orderId']    = $detailInfo['orderId'];
                        $addQueryChargeData['updatetime'] = time();
                        $addQueryChargeData['payNo']      = $detailInfo['payNo'];
                        $addQueryChargeData['status']     = $detailInfo['status'];
                        $ret                              = QueryCharge::addQueryCharge($addQueryChargeData);return $ret;
                        /*if(!$ret) {
                    $errorQueryCharge = array(
                    'compJdbId' => $company_id,//$rest['data']['compJdbId'];
                    'transId' => $rest['data']['transId'],
                    'begin_time' => $begin_time,
                    'end_time' => $end_time,
                    'orderId' => $detailInfo['orderId'],
                    'erron' => $rest['errno'],
                    'msg' => $rest['msg'],
                    );
                    QueryChargeError::addInfo($errorQueryCharge);
                    }*/
                    }
                } /* else{
            $errorQueryCharge = array(
            'compJdbId' => $company_id,
            'begin_time' => $begin_time,
            'end_time' => $end_time,
            'erron' => $rest['errno'],
            'msg' => $rest['msg'],
            );
            QueryChargeError::addInfo($errorQueryCharge);
            }*/
            }
        }
    }

    /**
     * 借入记录查询定时任务例子
     */
    public function actionQueryborrowrecord()
    {

    }

    public function actionGet()
    {
        setcookie("user_id1e", 11211, time() + 3600, '/', null, null, true);
    }

    public function actionGetcompanyinfo()
    {
        $companyobj = CompanyInfo::findByCompanyId("551763471197282553");
        if (!empty($companyobj)) {
            $companyinfo = $companyobj->attributes;
        }
        $companyinfo = json_encode($companyinfo);
        print_r($companyinfo);die;
    }

    public function actionQuerycharge()
    {
        var_dump($_COOKIE['user_id1e']);die;
        $dataInfo = User::GetUinfoByPhoneNum("15801330601", 'base', 1);
        echo "<pre>";
        print_r($dataInfo);die;

        //累计充值
        $seachParam = array(
            'comp_jdbid' => "559059186336409243",
            'begin_time' => "1456142961",
            'end_time'   => "1456315761", //date('Ymd'),
        );
        $rest = Payment::queryChargeList($seachParam); //queryCharge($seachParam);//
        echo "<pre>";
        print_r($rest);die;
    }
    public function actionTest()
    {
        $size       = 2;
        $company_id = "555343341357637869";
        $page       = Yii::$app->request->get('page', 1);
        $totalNum   = QueryCharge::getTotalNum();
        $totalPage  = ceil($totalNum / $size); //数据总数
        $result     = QueryCharge::getListByPage($company_id, $page, $size);
        if (!empty($result)) {
            $result                            = QueryCharge::getListFormatData($result, true);
            $rest                              = Payment::queryChargeTotal($company_id);
            $totalCashBalance                  = Payment::queryChargeTotalFormate($rest, $company_id);
            $result['data']['stats']['total']  = $totalCashBalance;
            $result['data']['stats']['charge'] = sprintf("%.2f", $totalCashBalance * 0.01);
        }

        return $result;die;
        $str = "好好";
        $ret = Common::pregChinese($str);
        var_dump($ret);die;
//        $pwd = "20DWTB4a@";
        //        $ret = User::VerifyPassword($pwd);var_dump($ret);die;
        $extensioncode = "20DWTB4";
        return ExtensionApi::VerifyExtensionCode($extensioncode);
    }
    public function actionTest1()
    {
        //echo Common::getRandChar(11);die;
        $user_jdbid = "555343341357637869";
        $rest       = Payment::queryChargeTotal($user_jdbid); //累计充值总额的查询接口
        echo "<pre>";
        print_r($rest);die;
    }

    /**
     * 充值查询
     * 1.查询所有的企业id分批循环
     * 2.查询一个时间段
     * 3.如果orderid在表里就不入库反之就入库
     */
    public function actionExecute()
    {
        $companyInfoCount = CompanyInfo::getCount();
        $handle_num       = Yii::$app->params['chargequery']['handle_num'];
        $time_interval    = Yii::$app->params['chargequery']['time_interval']; //时间段 单位秒
        $times            = ceil($companyInfoCount / $handle_num); //需要处理多少次
        for ($i = 1; $i <= $times; $i++) {
            $offset      = ($i - 1) * $handle_num;
            $companyList = CompanyInfo::getPageList($handle_num, $offset);
            foreach ($companyList as $companyData) {
                $paramData['comp_jdbid'] = $companyData['company_id'];
                $paramData['begin_time'] = time() - $time_interval; //开始时间戳
                $paramData['end_time']   = time(); //结束时间戳

                $queryChargeList = Payment::queryChargeList($paramData);
                if (!empty($queryChargeList['data']['detail'])) {
                    $detailList = $queryChargeList['data']['detail'];
                    foreach ($detailList as $detailInfo) {
                        $QueryChargeModel = QueryCharge::find();
                        if (!empty($detailInfo)) {
                            $QueryChargeModel->where(['order_id' => $detailInfo['orderId'], 'company_id' => $companyData['company_id'], 'status' => $detailInfo['status']]); //->attributes;//
                        }
                        $QueryChargeModel = $QueryChargeModel->one();
                        if (empty($QueryChargeModel)) {
                            $queryChargeInfo = array(
                                'compJdbId'  => $companyData['company_id'],
                                'transId'    => $queryChargeList['data']['transId'], //支付网关流水ID
                                'amount'     => $detailInfo['amount'], //交易金额（单位分）
                                'bankNo'     => $detailInfo['bankNo'], //充值的银行
                                'chargeDate' => $detailInfo['chargeDt'], //充值日期 YYYYMMDD
                                'chargeTime' => $detailInfo['chargsTm'], //充值时间
                                'orderId'    => $detailInfo['orderId'], //企业侧处理ID
                                'payNo'      => $detailInfo['payNo'], //支付平台的充值流水号
                                'bankName'   => $detailInfo['bankName'], //银行名称
                                'status'     => $detailInfo['status'], //充值的状态，状态码含义以支付平台的为标准
                            );
                            QueryCharge::addQueryCharge($queryChargeInfo);
                        }
                    }
                }
            }
        }
    }

    public function actionChongzhi()
    {
        $size       = 10;
        $company_id = User::getUserId();
        $page       = 1; //$params['pageNo'];//Yii::$app->request->get('page',1);
        $totalNum   = QueryCharge::getTotalNum();
        $totalPage  = ceil($totalNum / $size); //数据总数
        $result     = QueryCharge::getListByPage($company_id, $page, $size);
        $rs         = QueryCharge::getListFormatData($result);
//            if(!empty($result)) {
        //
        //            }
        //            $rs = Payment::queryCharge($params);
        if (isset($rs['errno']) && $rs['errno'] == 200 && isset($rs['data']['list'])) {
            //充值查询
            foreach ($rs['data']['list'] as $key => $value) {
                $rs['data']['list'][$key]['amount'] = '+' . sprintf("%.2f", $value['amount'] * 0.01);
                $rs['data']['list'][$key]['type']   = '充值';
            }
        }
        if (isset($rs['data']['stats']['total'])) {
            $rs['data']['stats']['charge'] = sprintf("%.2f", $rs['data']['stats']['total'] * 0.01);
        } else {
            $rs['data']['stats']['charge'] = '0.00';
        }
    }

    public function actionCreatebatch()
    {
        $BatchModel = new Batch();
        //获取wage表数据
        $list = Wage::find()->select('wagBatNo')->distinct()->asArray()->all();
        foreach ($list as $wageData) {
            $wagBatNo  = $wageData['wagBatNo'];
            $betchData = Batch::find()->where(['wagBatNo' => $wagBatNo])->one();
            if (empty($betchData)) {
                //获取wagBatNo的统计数据
                $getListBywagBatNo = Wage::find()->where(['wagBatNo' => $wagBatNo])->asArray()->all();
                $cusAmount         = 0; //发工资的总金额
                $status            = "S"; //批次受理的状态,S 是成功，D是删除，A是已经鉴权ok C 是已经确认过
                $number            = 0; //发工资人的数量
                $compId            = '';
                foreach ($getListBywagBatNo as $wagBatNoInfo) {
                    $createTime = intval($wagBatNoInfo['createtime']);
                    $cusAmount += intval($wagBatNoInfo['cusAmount']);
                    $number += 1;
                    $compId = $wagBatNoInfo['compUsrNo'];
                }
                //入库数据到batch表
                $BatchModel->allBatch   = $wagBatNo; //总的批次
                $BatchModel->wagBatNo   = $wagBatNo; //批次id
                $BatchModel->createtime = $createTime; //创建时间
                $BatchModel->number     = $number; //发工资的人的数量
                $BatchModel->amount     = $cusAmount; //发工资的总金额
                $BatchModel->status     = $status; //批次受理的状态,S 是成功，D是删除，A是已经鉴权ok C 是已经确认过
                $BatchModel->compId     = $compId; //公司的id
                $BatchModel->succCount  = 0; //成功笔数
                $BatchModel->failCount  = 0; //失败笔数
                $ret                    = $BatchModel->save();
                if ($wagBatNo == "01101c077e7fcdb375472960abbf2fc7") {
                    echo "<pre>";
                    var_dump($ret);
                }
            }
        }
    }

    /**
     * @return array
     * 解冻接口
     */
    public function actionDefreeze1()
    {
        $userId = "588805414850669994"; //企业id
        //        $wagBatNo = "53dd68008f8d544abc90e2e344472302";//代发工资批次号
        //        $amount = 50000;//解冻金额（单位分）
        $unsuccStr = "'S','D','Z'";
        $tabName   = Wage::choseTable($userId);
        $db        = Yii::$app->db;
        $db->open();
        $sql = "select distinct wagBatNo from {$tabName} WHERE status not in ({$unsuccStr})";
//        $sql = "select count(wagBatNo) from {$tabName}  where wagBatNo = '{$wagBatNo}' && cusAmount = '{$amount}' && status not in ({$unsuccStr})";
        $wagBatNoArr = $db->createCommand($sql)->queryAll();
        foreach ($wagBatNoArr as $wagBatNoData) {
            $amountSql = "select SUM(cusAmount) as amount,wagBatNo from {$tabName} WHERE status not in ($unsuccStr) AND wagBatNo='{$wagBatNoData['wagBatNo']}'";
            $amountArr = $db->createCommand($amountSql)->queryOne();
            if (!empty($amountArr)) {
                $data = Payment::Defreeze($userId, $amountArr['wagBatNo'], $amountArr['amount']);
                if (isset($data['data']) && !empty($data['data'])) {

                }
            }
        }
        die;
        echo "<pre>";
        print_r($wagBatNoArr);
    }
    /**
     * 解冻接口
     * 1.查询所有的企业id分批循环
     * 2.查询当前批次下的总额数据
     * 3.调用解冻接口处理解冻
     */
    public function actionDefreeze()
    {
        $db = Yii::$app->db;
        $db->open();
        $companyInfoCount = CompanyInfo::getCount();
        $handle_num       = Yii::$app->params['payment']['defreezeNum'];
        //$time_interval = Yii::$app->params['chargequery']['time_interval'];//时间段 单位秒
        $times = ceil($companyInfoCount / $handle_num); //需要处理多少次
        for ($i = 0; $i <= $times; $i++) {
            $offset      = ($i - 1) * $handle_num;
            $companyList = CompanyInfo::getPageList($handle_num, $offset);
            foreach ($companyList as $companyData) {
                $userId        = $companyData['company_id']; //企业id
                $tableName     = Wage::choseTable($userId);
                $pendingStatus = Yii::$app->params['wageStatusKey']['pending'];
                $successStatus = ['S', 'D', 'Z'];
                $arr           = array_merge($pendingStatus, $successStatus);
                $unsuccStr     = "('" . implode("','", $arr) . "')";
                $sql           = "select distinct wagBatNo from {$tableName} WHERE status not in {$unsuccStr}";
                $wagBatNoArr   = $db->createCommand($sql)->queryAll();
                foreach ($wagBatNoArr as $wagBatNoData) {
                    $amountSql = "select SUM(cusAmount) as amount,wagBatNo from {$tableName} WHERE status not in $unsuccStr AND wagBatNo='{$wagBatNoData['wagBatNo']}'";
                    $amountArr = $db->createCommand($amountSql)->queryOne();
                    if (!empty($amountArr)) {
                        $data = Payment::Defreeze($userId, $amountArr['wagBatNo'], $amountArr['amount']);
                        if (isset($data['data']) && !empty($data['data'])) {
//                            $updateSql = " UPDATE ".$tableName. " SET status =:status WHERE wagBatNo=:wagBatNo AND compUsrNo=:compUsrNo AND cusAmount:=cusAmount";
                            //                            $rs = $db->createCommand($updateSql)->execute(array(':wagBatNo'=>$wagBatNoData['wagBatNo'],':compUsrNo'=>$userId,':cusAmount'=>$amountArr['amount'], ':status'=>"Z"));
                            $updateSql = " UPDATE " . $tableName . " SET status ='Z' WHERE wagBatNo='{$wagBatNoData['wagBatNo']}' AND compUsrNo='{$userId}' AND status not in {$unsuccStr}";
                            $rs        = $db->createCommand($updateSql)->execute();
                            if (!$rs) {
                                Trace::addLog('wage_updatestatus_sql_exception', 'error', $updateSql);
                            }
                        } else {
                            Trace::addLog('payment_defreeze_exception', 'error', [
                                'comp_jdbid'  => $userId,
                                'compBatchId' => $wagBatNoData['wagBatNo'],
                                'amount'      => $amountArr['amount'],
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function actionTest2()
    {
        $db = Yii::$app->db;
        $db->open();
        $batchInStatus = Yii::$app->params['payment']['batchInStatus'];
        $batchCount    = Batch::getCount($batchInStatus);
        $handle_num    = Yii::$app->params['payment']['defreezeBatchNum'];
        $times         = ceil($batchCount / $handle_num); //需要处理多少次
        for ($i = 0; $i <= $times; $i++) {
            $offset    = ($i - 1) * $handle_num;
            $batchList = Batch::getPageList($handle_num, $offset, $batchInStatus);
            foreach ($batchList as $batchData) {
                $userId              = $batchData['compId']; //企业id
                $wagBatNo            = $batchData['wagBatNo']; //企业id
                $failStatus          = Yii::$app->params['wageStatusKey']['fail'];
                $deleteStatus        = Yii::$app->params['wageStatusKey']['delete'];
                $defreezeStatus      = Yii::$app->params['wageStatusKey']['defreeze'];
                $defreezeNotInStatus = array_merge($failStatus, $deleteStatus, $defreezeStatus);
                $wagBatNoArr         = Wage::getWageListByNotInStatus($userId, $wagBatNo, $defreezeNotInStatus);
                $wagCountAmount      = Wage::getWageCountAmountByNotInStatus($userId, $wagBatNo, $defreezeNotInStatus);
                if ($wagCountAmount > 0) {
                    $data = Payment::Defreeze($userId, $wagBatNo, $wagCountAmount);
                    if (isset($data['data']) && !empty($data['data'])) {
                        foreach ($wagBatNoArr as $wagBatNoData) {
                            Wage::updateStatusById($wagBatNoData['id'], $wagBatNoData['compUsrNo'], "Z");
                        }
                    }
                }
            }
        }
    }

    public function actionTest3()
    {
        $startTime = time();
        Trace::addLog('wage_defreeze_exception', 'info', [
            'starttime' => $startTime,
        ]);
        $data1         = [];
        $batchInStatus = Yii::$app->params['payment']['batchInStatus'];
        $batchCount    = Batch::getCount($batchInStatus);
        $handle_num    = Yii::$app->params['payment']['defreezeBatchNum'];

        $pendingStatus  = Yii::$app->params['wageStatusKey']['pending'];
        $successStatus  = Yii::$app->params['wageStatusKey']['success'];
        $deleteStatus   = Yii::$app->params['wageStatusKey']['delete'];
        $defreezeStatus = Yii::$app->params['wageStatusKey']['defreeze'];
        $failStatus     = Yii::$app->params['wageStatusKey']['fail'];
        $statusInArr    = array_merge($pendingStatus, $successStatus, $deleteStatus, $defreezeStatus); //解冻操作脚步每次处理发工资对应的状态

        $times = ceil($batchCount / $handle_num); //需要处理多少次
        for ($i = 0; $i <= $times; $i++) {
            $offset    = ($i - 1) * $handle_num;
            $batchList = Wage::getCompUsrNoListByDstinct("wage_" . $i, $failStatus, $offset);
            foreach ($batchList as $batchData) {
                $userId         = $batchData['compUsrNo']; //企业id
                $wagBatNo       = $batchData['wagBatNo']; //批次id
                $wagBatNoArr    = Wage::getWageListByNotInStatus($userId, $wagBatNo, $statusInArr);
                $wagCountAmount = Wage::getWageCountAmountByNotInStatus($userId, $wagBatNo, $statusInArr);
                if ($wagCountAmount > 0) {
                    $data = ['data' => 111]; //Payment::Defreeze($userId,$wagBatNo,$wagCountAmount);
                    if (isset($data['data']) && !empty($data['data']) && !empty($wagBatNoArr)) {
                        foreach ($wagBatNoArr as $wagBatNoData) {
                            $updateRet = Wage::updateStatusById($wagBatNoData['id'], $wagBatNoData['compUsrNo'], "Z", $statusInArr);
                            if ($updateRet) {
                                $data1[$wagBatNoData['id']][] = [
                                    'id'        => $wagBatNoData['id'],
                                    'compUsrNo' => $wagBatNoData['compUsrNo'],
                                    'status'    => "Z",
                                    "ret"       => $updateRet,
                                ];
                            }
                        }
                    }
                }
            }
        }
        Trace::addLog('wage_defreeze_exception', 'info', [
            'enttime'         => time(),
            'useTime'         => (time() - $startTime) . "s",
            "updateStatusArr" => $data1,
        ]);
    }

    public function actionHset()
    {
        $redis  = Yii::$app->redis;
        $myhash = "159109877061";
        $field  = "myhash";
        $value  = intval($redis->HGET($myhash, $field));
        $ret    = Common::HsetRedis($myhash, $field, $value + 1, 1, 2, 120); //$myhash,$field, $value,$isLimit=0,$maxErrorToTimes=0,$ErrRedisExpire=0
        var_dump($ret);
    }

    public function actionStaff()
    {
        $list = CompanyStaffModel::findStaffByCompany('596874199211929855', CompanyStaffModel::STATUS_UPLOAD_INFO_RIGHT);
    }

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
    public function actionLusctest()
    {
        return User::getUserinfoByUserId($_GET['id']);
    }
}
