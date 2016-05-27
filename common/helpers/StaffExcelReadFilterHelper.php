<?php
/**
 * Created by PhpStorm.
 * User: lusc
 * Date: 2016/5/7
 * Time: 1:38
 */

namespace common\helpers;

class StaffExcelReadFilterHelper implements \PHPExcel\Reader\IReadFilter
{
    public function readCell($column, $row, $worksheetName = '')
    {
        // 员工excel 只读取第二行到第5002行的A-D列
        if (($row >= 2 && $row <= (\Yii::$app->params['staff_upload_excel_max_row'] + 2)) && (in_array($column, range('A', 'D')))) {
            return true;
        }

        return false;
    }
}
