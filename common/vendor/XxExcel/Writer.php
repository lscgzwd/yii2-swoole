<?php
/**
 * excel快速生成类
 * User: lusc
 * Date: 2016/5/26
 * Time: 19:48
 */

namespace common\vendor\XxExcel;

use yii\base\Exception;

/**
 * Class Writer 快速生成excel2007
 * @package common\vendor\XxExcel
 */
class Writer
{
    const EXCEL_2007_MAX_ROW = 1048576;
    const EXCEL_2007_MAX_COL = 16384;
    protected $author        = '借贷宝企业版';
    protected $sheets        = [];
    protected $tempFiles     = [];
    protected $fileName      = '';
    protected $sheetTemplate = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac"><dimension ref="A1:{$maxCell}"/><sheetViews><sheetView tabSelected="{$tabSelected}" zoomScaleNormal="100" workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="13.8" x14ac:dyDescent="0.25"/><cols><col min="1" max="1025" width="15"/></cols><sheetData>{$rows}</sheetData><phoneticPr fontId="1" type="noConversion"/><pageMargins left="0.5" right="0.5" top="1" bottom="1" header="0.5" footer="0.5"/><pageSetup paperSize="9" orientation="portrait" useFirstPageNumber="1" r:id="rId1"/><headerFooter><oddHeader>&amp;C&amp;"Times New Roman,Regular"&amp;12&amp;A</oddHeader><oddFooter>&amp;C&amp;"Times New Roman,Regular"&amp;12Page &amp;P</oddFooter></headerFooter></worksheet>';
    protected $workbookTemplate = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><fileVersion appName="Calc"/><workbookPr backupFile="false" showObjects="all" date1904="false"/><workbookProtection/><bookViews><workbookView activeTab="0" firstSheet="0" showHorizontalScroll="true" showSheetTabs="true" showVerticalScroll="true" tabRatio="212" windowHeight="8192" windowWidth="16384" xWindow="0" yWindow="0"/></bookViews><sheets>{$sheets}</sheets><calcPr iterateCount="100" refMode="A1" iterate="false" iterateDelta="0.001"/></workbook>';

    public function __construct()
    {
    }

    public function setAuthor($author = '')
    {
        $this->author = $author;
    }

    public function build($fileName = '')
    {
        if (empty($this->sheets)) {
            throw new Exception('Empty sheets, can not build.');
        }
        if (empty($fileName)) {
            $fileName = $this->tempFilename();
        }
        if (is_writeable($fileName) === false) {
            throw new Exception("{$fileName} can not been write.");
        }
        $zip    = new \ZipArchive();
        $status = $zip->open($fileName, \ZipArchive::CREATE);
        if ($status == false) {
            throw new Exception('Can not create zip file with filename:' . $fileName);
        }
        $this->fileName = $fileName;
        $zip->addEmptyDir('docProps/');
        $zip->addFromString('docProps/app.xml', $this->buildAppXML());
        $zip->addFromString('docProps/core.xml', $this->buildCoreXML());

        $zip->addEmptyDir('_rels/');
        $zip->addFromString('_rels/.rels', $this->buildRelationshipsXML());

        $zip->addEmptyDir('xl/worksheets/');
        foreach ($this->sheets as $_key => $sheet) {
            $zip->addFromString("xl/worksheets/" . $sheet['xmlname'], $sheet['content']);
        }
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst count="0" uniqueCount="0" xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"></sst>');
        $zip->addFromString('xl/workbook.xml', $this->buildWorkbookXML());
        $zip->addFromString('xl/styles.xml', $this->buildStylesXML());
        $zip->addFromString('[Content_Types].xml', $this->buildContentTypesXML());

        $zip->addEmptyDir('xl/_rels/');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->buildWorkbookRelsXML());
        $zip->close();
    }

    protected function buildAppXML()
    {
        $appXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><TotalTime>0</TotalTime></Properties>';
        return $appXml;
    }

    protected function buildCoreXML()
    {
        $coreXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>' . $this->xmlspecialchars($this->author) . '</dc:creator><cp:revision>0</cp:revision><dcterms:created xsi:type="dcterms:W3CDTF">' . date("Y-m-d\TH:i:s.00\Z") . '</dcterms:created></cp:coreProperties>';
        return $coreXml;
    }

    protected function buildRelationshipsXML()
    {
        $relXml = '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>';
        return $relXml;
    }

    protected function buildWorkbookXML()
    {
        $xml    = $this->workbookTemplate;
        $sheets = '';
        foreach ($this->sheets as $_key => $sheet) {
            $sheets .= '<sheet name="' . $this->xmlspecialchars($sheet['name']) . '" sheetId="' . ($_key + 1) . '" state="visible" r:id="rId' . ($_key + 2) . '"/>';
        }
        $xml = str_replace('{$sheets}', $sheets, $xml);
        return $xml;
    }

    protected function buildStylesXML()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="1"><numFmt numFmtId="164" formatCode="GENERAL" /></numFmts><fonts count="4"><font><name val="Arial"/><charset val="1"/><family val="2"/><sz val="10"/></font><font><name val="Arial"/><family val="0"/><sz val="10"/></font><font><name val="Arial"/><family val="0"/><sz val="10"/></font><font><name val="Arial"/><family val="0"/><sz val="10"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border diagonalDown="false" diagonalUp="false"><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="20"><xf applyAlignment="true" applyBorder="true" applyFont="true" applyProtection="true" borderId="0" fillId="0" fontId="0" numFmtId="164"><alignment horizontal="general" indent="0" shrinkToFit="false" textRotation="0" vertical="bottom" wrapText="false"/><protection hidden="false" locked="true"/></xf><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="2" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="2" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="43"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="41"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="44"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="42"/><xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="9"/></cellStyleXfs><cellXfs count="1"><xf applyAlignment="false" applyBorder="false" applyFont="false" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="164" xfId="0"/></cellXfs><cellStyles count="6"><cellStyle builtinId="0" customBuiltin="false" name="Normal" xfId="0"/><cellStyle builtinId="3" customBuiltin="false" name="Comma" xfId="15"/><cellStyle builtinId="6" customBuiltin="false" name="Comma [0]" xfId="16"/><cellStyle builtinId="4" customBuiltin="false" name="Currency" xfId="17"/><cellStyle builtinId="7" customBuiltin="false" name="Currency [0]" xfId="18"/><cellStyle builtinId="5" customBuiltin="false" name="Percent" xfId="19"/></cellStyles>
</styleSheet>';
    }

    protected function buildWorkbookRelsXML()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        foreach ($this->sheets as $_key => $sheet) {
            $xml .= '    <Relationship Id="rId' . ($_key + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/' . ($sheet['xmlname']) . '"/>';
        }

        $xml .= '    <Relationship Id="rId' . (count($this->sheets) + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';
        return $xml;
    }

    protected function buildContentTypesXML()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Override PartName="/_rels/.rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Override PartName="/xl/_rels/workbook.xml.rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        foreach ($this->sheets as $_key => $sheet) {
            $xml .= '<Override PartName="/xl/worksheets/' . ($sheet['xmlname']) . '" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $xml .= '    <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
    <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
 </Types>';
        return $xml;
    }

    public function addSheet($data, $sheetName = 'Sheet1')
    {
        $content     = $this->sheetTemplate;
        $maxCell     = $this->getCellMark(count($data) - 1, count($data[0]) - 1);
        $tabSelected = count($this->sheets) == 0 ? 1 : 0;
        $content     = str_replace('{$maxCell}', $maxCell, $content);
        $content     = str_replace('{$tabSelected}', $tabSelected, $content);
        $i           = 1;
        $rowStr      = [];
        foreach ($data as $_k => $row) {
            $_rowStr = '<row collapsed="false" customFormat="false" customHeight="false" hidden="false" ht="12.1" outlineLevel="0" r="' . $i . '">';
            $j       = 1;
            $cellStr = [];
            foreach ($row as $cell) {
                $cellLabel = $this->getCellMark($i - 1, $j - 1);
                $_cellStr  = '<c r="' . $cellLabel . '" s="0" t="inlineStr"><is><t>' . $this->xmlspecialchars($cell) . '</t></is></c>';
                $j++;
                $cellStr[] = $_cellStr;
            }
            $_rowStr .= implode('', $cellStr) . '</row>';
            $rowStr[] = $_rowStr;
            $i++;
        }
        $content = str_replace('{$rows}', implode('', $rowStr), $content);
        $sheet   = [
            'name'    => $sheetName,
            'xmlname' => 'sheet' . (count($this->sheets) + 1) . '.xml',
            'content' => $content,
        ];
        $this->sheets[] = $sheet;
    }

    protected function xmlspecialchars($val)
    {
        return str_replace("'", "&#39;", htmlspecialchars($val));
    }

    public function __destruct()
    {
        if (!empty($this->tempFiles)) {
            foreach ($this->tempFiles as $tempfile) {
                @unlink($tempfile);
            }
        }
    }

    protected function tempFilename()
    {
        $filename          = tempnam(sys_get_temp_dir(), "xlsx_writer_");
        $this->tempFiles[] = $filename;
        return $filename;
    }

    /**
     * 通过列号行号阿拉伯数字转换成A1 A2形式
     * @param $rowNumber
     * @param $columnNumber
     * @return string
     */
    public function getCellMark($rowNumber, $columnNumber)
    {
        $n = $columnNumber;
        for ($r = ''; $n >= 0; $n = intval($n / 26) - 1) {
            $r = chr($n % 26 + 0x41) . $r;
        }
        return $r . ($rowNumber + 1);
    }
}
