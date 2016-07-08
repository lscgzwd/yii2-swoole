<?php
/**
 * Excel2007快速解析类
 * User: lusc
 * Date: 2016/5/26
 * Time: 15:42
 */

namespace common\vendor\XxExcel;

/**
 * Class Reader excel2007 解析类
 * @package common\vendor\XxExcel
 */
class Reader
{
    /**
     * @var \ZipArchive
     */
    public $zip               = null;
    public $currentSheet      = 1;
    public $sheetInfos        = [];
    public $sharedStrings     = [];
    public $currentSheetDatas = [];
    protected $maxRow         = 5000;

    /**
     * 读取一个excel2007文件
     * @param $filename
     */
    public function open($filename)
    {
        $zip = new \ZipArchive();
        $zip->open($filename);
        $this->zip = $zip;
        $this->readSharedString();
    }

    public function setMaxRow($number)
    {
        $this->maxRow = $number;
    }

    /**
     * 读取sheet信息
     */
    public function readSheetsNames()
    {
        $xml    = simplexml_load_string($this->zip->getFromName('xl/workbook.xml'));
        $sheets = $xml->sheets;
        foreach ($sheets->sheet as $sheet) {
            $_tmp['name']       = $sheet['name']->__toString();
            $_tmp['index']      = $sheet['sheetId']->__toString();
            $this->sheetInfos[] = $_tmp;
        }
    }

    /**
     * excel2007为了节约存储会把单元格信息抽取后存储在sharedString.xml中，如果单元格中有重复数据会节约存储
     * 解析sharedString.xml中的文本数据
     */
    protected function readSharedString()
    {
        $xml = new \XMLReader();
        $xml->XML($this->zip->getFromName('xl/sharedStrings.xml'));

        while ($xml->read()) {
            if ($xml->name == 't' && $xml->nodeType == \XMLReader::ELEMENT) {
                $this->sharedStrings[] = $xml->readString();
            }
        }
    }

    /**
     * 切换sheet表
     * @param int $sheetIndex
     */
    public function changeWorkSheet($sheetIndex = 1)
    {
        $this->currentSheet = $sheetIndex;
    }

    /**
     * 获取当前表的数据
     * @return array|mixed
     */
    public function getCurrentSheetDatas()
    {
        if (isset($this->currentSheetDatas[$this->currentSheet])) {
            return $this->currentSheetDatas[$this->currentSheet];
        }
        $sheetFile = 'xl/worksheets/sheet' . $this->currentSheet . '.xml';

        $xml = new \XMLReader();
        $xml->XML($this->zip->getFromName($sheetFile));
        $rows = [];
        $j    = 0;
        while ($xml->read()) {
            if ($xml->name == 'row' && $xml->nodeType == \XMLReader::ELEMENT) {
                $rowXml = simplexml_load_string($xml->readOuterXml());
                $row    = [];
                foreach ($rowXml->c as $c) {
                    $t         = isset($c['t']) ? $c['t']->__toString() : 's';
                    $value     = '';
                    $emptyCell = false;
                    switch ($t) {
                        case 'inlineStr':
                            $value = $c->is->t->__toString();
                            break;
                        case 'n':
                            $value = $c->v->__toString();
                            break;
                        case 's':
                            if (empty($c->v->__toString())) {
                                $emptyCell = true;
                                break 1;
                            } else {
                                $value = $this->sharedStrings[$c->v->__toString()];
                            }
                            break;
                    }
                    if ($emptyCell === false) {
                        $row[$c['r']->__toString()] = $value;
                    }
                }
                if (!empty($row)) {
                    $rows[] = $row;
                }

                unset($row, $rowXml);
                $j++;
                if ($j > $this->maxRow) {
                    break;
                }
            }
        }
        $this->currentSheetDatas[$this->currentSheet] = $rows;
        unset($xml, $sheetName, $sheetFile);
        return $rows;
    }

    /**
     * @param int    $rowIndex
     * @param string $cell
     * @return mixed
     */
    public function getCellValueByRowAndColumn($rowIndex = 1, $cell = 'A')
    {
        return $this->currentSheetDatas[$this->currentSheet][$rowIndex - 1][$cell . $rowIndex];
    }
}
