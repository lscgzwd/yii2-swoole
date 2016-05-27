<?php
/*************************************************************************
> File Name :     ./commands/DrawController.php
> Author :        unasm
> Mail :          douunasm@gmail.com
> Last_Modified : 2016-01-19 17:35:08
 ************************************************************************/
namespace console\controllers;

use apps\lib\Payment;
use apps\models\CompanyInfo;
use apps\models\QueryCharge;
use Yii;

/**
 * 充值查询入库操作脚本
 **/
class ChargequeryController extends BaseController
{
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
        for ($i = 0; $i <= $times; $i++) {
            $offset      = ($i - 1) * $handle_num;
            $companyList = CompanyInfo::getPageList($handle_num, $offset);
            foreach ($companyList as $companyData) {
                //获取当前企业的最后一条数据
                /*
                $queryChargeInfo = QueryCharge::find()
                ->OrderBy(['inserttime'=>SORT_DESC])
                ->where(['company_id'=>$companyData['company_id']])
                ->andWhere(['<>', 'status', 'D'])
                ->one();
                if(!empty($queryChargeInfo)) {
                $queryChargeInfo = $queryChargeInfo->attributes;
                $begin_time = $queryChargeInfo['inserttime'];
                } else {
                $begin_time = time() - $time_interval;//开始时间戳
                }
                 */
                //获取过去一天内的充值记录
                $begin_time              = time() - 86400;
                $paramData['comp_jdbid'] = $companyData['company_id'];
                $paramData['begin_time'] = $begin_time; //开始时间戳
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
}
