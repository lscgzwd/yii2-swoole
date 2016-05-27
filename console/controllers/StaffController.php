<?php
/*************************************************************************
 * File Name :    WagequeryController.php
 * Author    :    unasm
 * Mail      :    unasm@sina.cn
 ************************************************************************/

namespace console\controllers;

use apps\lib;
use apps\lib\Trace;
use apps\models\ExtensionCompany;
use common\models;
use Yii;
use yii\base\Exception;
use yii\console\Controller;

/**
 * 验证上传花名册中的员工信息
 **/
class StaffController extends BaseController
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
    /**
     * 验证花名册中的信息 定时任务 暂定每分钟执行一次
     */
    public function actionVerifyStaffInfo()
    {
        $staffUploadLogModel = new models\StaffUploadLogModel();
        $excels              = $staffUploadLogModel->findByStatus(models\StaffUploadLogModel::STATUS_PROCESSED);
        foreach ($excels as $key => $_row) {
            $companyId = $_row['company_id'];

            //是否充值认证
            $companyInfo = lib\User::isIdent($companyId);

            if (!isset($companyInfo['data']['status']) || $companyInfo['data']['status'] != 1) {
                lib\Trace::addLog('noIdent_company_staff_exception', 'info', ['msg' => 'noIdent', ['data' => ['companyId' => $companyId]]]);
                continue;
            }

            models\CompanyStaffModel::setHashId($companyId); //设置hashId
            try {
                $companyStaffs = models\CompanyStaffModel::findStaffByCompany($companyId);
            } catch (Exception $e) {
                lib\Trace::addLog('CompanyStaff_findCompanyInfo_exception', 'info', ['msg' => 'findCompanyInfo_exception', ['data' => ['companyId' => $companyId, 'exception' => $e->getMessage()]]]);
                continue;
            }

            $companyStaff = new models\CompanyStaffModel();
            $companyInfo  = lib\User::GetCompanyInfo($companyId, false);
            $companyStaff->verifyStaff($companyStaffs, $companyInfo);
            $staffUploadLogModel->updateStatus($_row['uuid'], models\StaffUploadLogModel::STATUS_FRIEND_SENT);

            //拉新数据
            $redis                = Yii::$app->redis;
            $keyLaxin             = Yii::$app->params['redisKey']['laxinFriends'];
            $now                  = time();
            $extensionCompanyInfo = ExtensionCompany::findByCompanyId($companyId);
            if (!empty($extensionCompanyInfo) && !empty($extensionCompanyInfo->extension_code)) {
                $extensionCode = $extensionCompanyInfo->extension_code;
                foreach ($companyStaffs as $companyStaffInfo) {
                    $toRedis = [
                        'CID'      => $companyId,
                        'Channel'  => $extensionCode,
                        'UserName' => $companyStaffInfo['name'],
                        'Mobile'   => $companyStaffInfo['mobilephone'],
                        'Identity' => $companyStaffInfo['id_no'],
                        'Time'     => $now,
                    ];
                    $rs = $redis->LPUSH($keyLaxin, json_encode($toRedis));
                    Trace::addLog('friend_toGround_Redis', 'info', ['data' => $toRedis, 'key' => $keyLaxin, 'rs' => $rs]);
                }
            }
        }
    }

}
