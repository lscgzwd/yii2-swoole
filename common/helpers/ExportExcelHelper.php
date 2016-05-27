<?php

namespace common\helpers;

use apps\lib\Trace;

class ExportExcelHelper
{

    /**
     * 导出excel到浏览器
     * @param string $subject
     * @param array $list
     * @param array $header
     * @param string $title
     * @param string $total
     * @return boolean
     */
    public static function export($subject, $list, $header, $title = '', $total = '')
    {

        if (empty($title)) {
            $title = '借贷宝企业平台';
        }

        if (empty($header)) {
            Trace::addLog('export_excel_exception', 'error', []);
            \yii::$app->end(404);
        }

        if (empty($list)) {
            Trace::addLog('export_excel_exception', 'error', []);
            \yii::$app->end(404);
        }

        $len     = count($header);
        $columns = self::makeColums($len);
        if (empty($columns)) {
            Trace::addLog('export_excel_exception', 'error', []);
            \yii::$app->end(404);
        }
        set_time_limit(0);
        $excel = new \PHPExcel\Spreadsheet();
        $excel->getProperties()->setCreator($title)->setLastModifiedBy($title)->setSubject($subject);
        $line = 1;
        $idx  = 0;
        foreach ($header as $value) {
            $excel->setActiveSheetIndex(0)->setCellValue($columns[$idx] . $line, $value);
            $excel->getActiveSheet()->getColumnDimension($columns[$idx])->setAutoSize(true);
            $idx++;
        }
        foreach ($list as $item) {
            $column = 0;
            $line++;
            foreach ($header as $key => $val) {
                $excel->setActiveSheetIndex(0)->setCellValue($columns[$column++] . $line, strval(" " . $item[$key] . " "));
            }
        }
        if (!empty($total)) {
            $column = 0;
            $line++;
            $excel->setActiveSheetIndex(0)->setCellValue($columns[$column] . $line, strval(" " . $total . " "));
            $excel->getActiveSheet()->mergeCells($columns[$column] . $line . ':' . $columns[count($header) - 1] . $line);
        }
        $excel->getActiveSheet()->setTitle($title);
        try {
        	ob_end_clean();
            header("Content-type:application/vnd.ms-excel");
            header('Content-Disposition: attachment;filename="' . urlencode($subject) . '.xls"');
            $objWriter = \PHPExcel\IOFactory::createWriter($excel, 'Excel5');
            $objWriter->save('php://output');
            Trace::addLog('export_success', 'info', ['subject' => $subject, 'count' => count($list)]);
        } catch (\ErrorException $e) {
            Trace::addLog('export_fail', 'info', ['subject' => $subject, 'count' => count($list), 'msg' => $e->getMessage()]);
        }
    }
    private static function makeColums($len)
    {

        $loop    = 0;
        $charnum = 65;
        $colums  = [];
        for (; $loop < $len; $loop++) {
            $quotient  = intval($loop / 26);
            $remainder = $loop % 26;

            $f = $quotient > 0 ? chr($charnum + $quotient - 1) : '';
            $s = $remainder >= 0 ? chr($charnum + $remainder) : '';
            array_push($colums, $f . $s);
        }
        return $colums;
    }
}
