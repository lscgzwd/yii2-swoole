<?php
/**
 * Created by PhpStorm.
 * User: xingjianqiang
 * Date: 16-5-20
 * Time: 下午1:15
 */

namespace console\controllers;

use apps\lib\Trace;
use apps\lib\User;
use common\models\CompanyStaffModel;
use common\vendor\XxExcel\Writer;
use yii\base\Exception;

class TestController extends BaseController
{
    public function actionIndex()
    {
        var_dump(memory_get_usage() / 1000);
        var_dump(microtime(true));
        $data = (new CompanyStaffModel())->findByTypeAndStatus('596874199211929855', 0, 0, -1, 1, 5000);
        var_dump(memory_get_usage() / 1000);
        var_dump(microtime(true));
        $excel  = new Writer();
        $rows[] = [
            '员工姓名',
            '手机号',
            '身份证号',
            '类型',
        ];
        foreach ($data['list'] as $key => $row) {
            $rows[] = [
                $row['name'],
                $row['mobilephone'],
                $row['id_no'],
                $row['staff_type'],
            ];
        }
        var_dump(memory_get_usage() / 1000);
        var_dump(microtime(true));
        $excel->addSheet($rows);
        $excel->build(tempnam('/home/nginx', 'excel_'));
        var_dump(memory_get_usage() / 1000);
        var_dump(microtime(true));
    }

    /**
     * 手动添加好友并更新借贷宝id
     */
    public function actionAddFriend()
    {
        $companyIds = ['599948560349652599', '598080604950884237'];
        //$companyIds = ['568835951258771475'];

        $companyStaffModel = new CompanyStaffModel();
        foreach ($companyIds as $companyId) {
            $companyInfo = User::GetCompanyInfo($companyId, false);
            try {
                $companyStaffs = CompanyStaffModel::findAllByParam($companyId, ['jdb_id' => '']);
            } catch (Exception $e) {
                Trace::addLog('CompanyStaff_findCompanyInfo_exception', 'info', ['msg' => 'findCompanyInfo_exception', ['data' => ['companyId' => $companyId, 'exception' => $e->getMessage()]]]);
                continue;
            }

            foreach ($companyStaffs as $companyStaff) {
                error_log("--------------------------------\n", 3, "/tmp/xing.log");
                error_log("before:" . $companyStaff['company_id'] . "\t" . $companyStaff['mobilephone'] . "\t" . print_r($companyStaff, true), 3, "/tmp/xing.log");
                $res = $companyStaffModel->verfiySingleStaff($companyStaff, $companyInfo);
                error_log("after:" . $companyStaff['company_id'] . "\t" . $companyStaff['mobilephone'] . "\t" . print_r($res, true), 3, "/tmp/xing.log");
                error_log("------------------------------\n", 3, "/tmp/xing.log");
            }
        }
    }
}
