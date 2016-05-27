<?php
/*************************************************************************
 * File Name :    DtlxController.php
 * Author    :    unasm
 * Mail      :    unasm@sina.cn
 ************************************************************************/

namespace console\controllers;

//use yii\console\Controller;
use apps\lib\Wind;
use apps\lib\Trace;
use apps\lib\Groundpush;
use apps\models\Wage;
use Yii;

/**
 * 工资查询
 **/
class DtlxController extends BaseController
{

    /**
     * 从队列里查询出发放工资不成功的记录,并到通过地推拉新接口进行相应逻辑处理
     *
     **/
    public function actionQuery()
    {
        $redis = Yii::$app->redis;
        //N 未注册借贷宝
        $key = Yii::$app->params['redisKey']['laxinWage'];
        $len = $redis->LLEN($key);
		$len = 10;
        while ($len--) {
            $pop      = $redis->LPOP($key);
            if (YII_ENV == 'dev') {
                Trace::addLog('dtlx_pop_redis', 'info', ['pop' => $pop, 'key' => $key]);
            }

            $tmp_info = json_decode($pop, true);
			/*
            $tmp_info = [
            'mobile' => '13911441064',
            'status' => 'S',
            'companyId' => '576172372306960399',
            'name' => '高蕊',
            'time' => '1458805700',
            'createtime' => '1458737701',
            'id' => '58',
            ];
			 */
			if (!isset($tmp_info['companyId']))  {
				continue;
			}
			$companyId = $tmp_info['companyId'];
            $extensionCode = '';
            if ($companyId) {
                $extensionCode_tmp = Groundpush::extensionCode($companyId);
                if ($extensionCode_tmp['errno'] == 200) {
                    $extensionCode = $extensionCode_tmp['data']['extensionCode'];
                }
            }
			if (!isset($tmp_info['id'])) {
				continue;	
			}
            $table     = Wage::choseTable($companyId);
            $db        = Yii::$app->db;
            $db->open();
            $wageRow = $db->createCommand("select * from {$table}  where id = '{$tmp_info['id']}'")->queryOne();
            if (empty($wageRow)) {
                throw new \Exception('未查询到相关记录', 500);
            }
            try {
                if ($tmp_info['status'] == 'N') {
                    Groundpush::wageNoReg($tmp_info, $extensionCode, $wageRow);
                }
                if ($tmp_info['status'] == 'S') {
                    $rs = Groundpush::wageSuccess($tmp_info, $extensionCode, $wageRow);
                    Wind::wageSuccess($wageRow, $tmp_info);
                }
            } catch (\Exception $e) {
                $redis->RPUSH($key, $pop) ;
                Trace::addLog('wage_callback_excepiton', 'warning', ['msg' => $e->getMessage(),'pop' => $pop, 'key' => $key]);
            }
        }
    }

}
