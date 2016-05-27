<?php
/**
 * Created by PhpStorm.
 * User: 高志伟
 * Date: 2016/2/17
 * Time: 19:36
 */
namespace console\controllers;

use apps\models\Cmmtbkin;
use console\controllers\BaseController;

class QueryController extends BaseController
{
    public function actionAddcmmtbkininfo()
    {
//        $conn = Yii::$app->db;
        //        $conn->open();
        //        $sql = "select ID from jdb_cmmtbkin where ID=98987";
        //        //$sql = "select id, cusPhone, status from wage where status != 'S' && createtime >= '{$time}'";
        //        $command = $conn->createCommand($sql);
        //        $rest = $command->queryAll();
        //var_dump($rest);die;
        $CmmtbkinModel           = new Cmmtbkin();
        $CmmtbkinModel->ID       = 98995;
        $CmmtbkinModel->LBNK_NO  = '301100001032';
        $CmmtbkinModel->LBNK_NM  = '广东发展银行股份有限公司北京西直门支行';
        $CmmtbkinModel->LBNK_CD  = 306;
        $CmmtbkinModel->CORP_ORG = 'GDB';
        $CmmtbkinModel->ADM_CITY = 1000;
        $CmmtbkinModel->ADM_PROV = 110;
        $CmmtbkinModel->ADM_RGN  = 1000;
        $CmmtbkinModel->PROV_CD  = '01';
        $CmmtbkinModel->CITY_CD  = 100;
        $CmmtbkinModel->TM_SMP   = '20160217191534';
        $isSave                  = $CmmtbkinModel->save();
        var_dump($isSave);
    }
}
