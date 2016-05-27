<?php
/*************************************************************************
 * File Name :    ../../app/controllers/StaffController.php
 * Author    :    gaozhiwei
 * Mail      :    gaozw@jiedaibao.com
 ************************************************************************/
namespace apps\controllers;

use apps\lib\Trace;
use apps\models\Wage;
use common\controllers\ApiBaseController;
use common\helpers\StringHelper;
use common\models\CompanyStaffModel;
use common\models\StaffUploadLogModel;
use Yii;

/**
 * 花名册相关操作
 **/
class StaffController extends ApiBaseController
{

    /**
     * 上传员工excel
     * @return array
     */
    public function actionUploadStaffExcel()
    {
//        $companyId = User::getUserId();
        $companyId = '569620096733945907';

        if (empty($companyId)) {
            return ['code' => 5001, 'errno' => 5001, 'msg' => '当前用户没有登录'];
        }
        if (empty($_FILES) || !isset($_FILES['excel'])) {
            return ['errno' => 400, 'msg' => 'excel文件没有选择', 'data' => null];
        }

        $uploadPath = Yii::$app->params['upload']['path'];
        $tmpName    = $_FILES['excel']['tmp_name'];
        $clientName = $_FILES['excel']['name'];
        $uuid       = StringHelper::uuid();
        $fileExt    = substr($clientName, strrpos($clientName, '.'));
        if ($fileExt != '.xls' && $fileExt != '.xlsx') {
            return ['errno' => 400, 'msg' => '文件格式错误，只能上传excel文件', 'data' => null];
        }
        $newFileName = $uploadPath . $uuid . $fileExt;
        move_uploaded_file($tmpName, $newFileName);
        $uploadLogModel = new StaffUploadLogModel();
        $result         = $uploadLogModel->addExcel($companyId, $uuid, $newFileName);
        if ($result) {
            Yii::$app->swoole->task(json_encode(['type' => 'StaffExcelParse', 'data' => [
                'uuid'      => $uuid,
                'companyId' => $companyId,
                'fileName'  => $newFileName,
            ]]));
            return ['errno' => 200, 'msg' => '上传成功', 'data' => $uuid];
        } else {
            return ['errno' => 500, 'msg' => '上传失败', 'data' => null];
        }
    }

    /**
     * 获取状态映射表
     * @return array
     */
    public function actionGetStatusType()
    {
        $statusMap = CompanyStaffModel::$statusMap;
        foreach ($statusMap as $id => &$row) {
            unset($row['types']);
        }
        return ['errno' => 200, 'msg' => '请求成功', 'data' => $statusMap];
    }

    /**
     * 轮循获取上传结果
     * @return array
     */
    public function actionGetUploadResult()
    {
//        $companyId = User::getUserId();
        $companyId = '569620096733945907';
        $pageNo    = Yii::$app->getRequest()->post('pageNo', 1);
        $pageSize  = Yii::$app->getRequest()->post('pageSize', 20);
        if (empty($companyId)) {
            return ['code' => 5001, 'errno' => 5001, 'msg' => '当前用户没有登录'];
        }
        $uploadNo = Yii::$app->getRequest()->post('uploadNo');

        if (empty($uploadNo)) {
            return ['code' => 400, 'errno' => 400, 'msg' => '参数错误'];
        }
        $uploadLog = (new StaffUploadLogModel())->findByUUID($companyId, $uploadNo);
        if ($uploadLog['status'] == 0) {
            return ['code' => 201, 'errno' => 201, 'msg' => 'excel还没有解析'];
        } elseif ($uploadLog['status'] == StaffUploadLogModel::STATUS_PROCESSED_FAIL) {
            return ['code' => 503, 'errno' => 503, 'msg' => 'excel解析失败，请确认你上传的文件格式。'];
        } elseif ($uploadLog['status'] == StaffUploadLogModel::STATUS_ROW_LIMIT) {
            return ['code' => 400, 'errno' => 400, 'msg' => 'excel解析失败，超过最大行数限制。'];
        }
        $data = (new CompanyStaffModel())->findByUploadNo($companyId, $uploadNo, $pageNo, $pageSize);
        return ['code' => 200, 'errno' => 200, 'msg' => '请求成功', 'data' => $data];
    }

    /**
     * 获取企业员工列表
     * @return array
     */
    public function actionList()
    {
        Trace::addLog('trace_action_begin', 'info', ['haha' => 'heie', 'line' => __LINE__, 'file' => __FILE__]);
//        $companyId = User::getUserId();
        $companyId = '569620096733945907';
        if (empty($companyId)) {
//            return ['code' => 5001, 'errno' => 5001, 'msg' => '当前用户没有登录'];
        }
        //TODO  员工类型前端和后端新类型匹配
        $staffType = Yii::$app->getRequest()->post('staffType', 0);
        $staffType++;

        $status   = Yii::$app->getRequest()->post('status', 0);
        $pageNo   = Yii::$app->getRequest()->post('pageNo', 1);
        $pageSize = Yii::$app->getRequest()->post('pageSize', 20);

        $data = (new CompanyStaffModel())->findByTypeAndStatus($companyId, $staffType, $status, -1, $pageNo, $pageSize);
        //TODO  在工资发放的时候直接更新累积发放工资字段，优化性能
        foreach ($data['list'] as $key => &$row) {
            $row['salary'] = Wage::getCountByUid($companyId, $row['jdb_id']);
            $row['salary'] = sprintf("%.2f", $row['salary'] * 0.01);
            foreach (CompanyStaffModel::$statusMap as $map) {
                if (in_array($row['status'], $map['types']) && $map['id'] != 0) {
                    $row['status'] = $map['id'];
                }
            }
            //TODO  员工类型前端和后端新类型匹配
            $row['staff_type'] = intval($row['staff_type'] - 1);
        }
        return ['code' => 200, 'errno' => 200, 'msg' => '请求成功', 'data' => $data];
    }
}
