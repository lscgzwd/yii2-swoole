<?php
/**
 * 员工excel解析
 * User: lusc
 * Date: 2016/5/19
 * Time: 13:34
 */

namespace common\service\swooletask;

use apps\lib\Trace;
use common\helpers\IDCardCheckHelper;
use common\helpers\StaffExcelReadFilterHelper;
use common\helpers\StringHelper;
use common\models\CompanyStaffModel;
use common\models\StaffUploadLogModel;
use PHPExcel\IOFactory;
use yii\base\Exception;

/**
 * 员工管理花名册解析
 * Class StaffExcelParse
 * @package common\service\swooletask
 */
class StaffExcelParse
{
    protected $excelReader = null;

    /**
     * StaffExcelParse constructor.
     * @param $task
     */
    public function __construct($task)
    {
        $uuid                = $task['uuid'];
        $companyId           = $task['companyId'];
        $fileName            = $task['fileName'];
        $staffUploadLogModel = new StaffUploadLogModel();
        // 判断Excel类型
        if (false === $excelType = $this->getExcelType($fileName)) {
            $staffUploadLogModel->updateStatus($uuid, StaffUploadLogModel::STATUS_PROCESSED_FAIL);
            Trace::addLog('handle_staff_excel_error', 'error', ['data' => $task, 'msg' => '文件类型错误'], __CLASS__);
        }
        // 读取excel,得到excel实例
        try {
            $this->getExcelReader($excelType, $fileName);
        } catch (Exception $e) {
            $staffUploadLogModel->updateStatus($uuid, StaffUploadLogModel::STATUS_PROCESSED_FAIL);
            Trace::addLog('handle_staff_excel_exception', 'error', ['data' => $task, 'exception' => $e->__toString(), 'msg' => '解析excel，excel文件读取异常'], __CLASS__);
        }
        // 限制最大读取行数，5000行
        // 标题占一行，所以需要加2 获取最大限制行数5000行加1行，5001行的手机号，如果还有值，说明上传的excel超过了限制
        $maxCell = trim($this->excelReader->getCell('B' . (\Yii::$app->params['staff_upload_excel_max_row'] + 2))->getValue());
        if (!empty($maxCell)) {
            $staffUploadLogModel->updateStatus($uuid, StaffUploadLogModel::STATUS_ROW_LIMIT);
            return false;
        }
        // 身份证验证类
        $idChecker = new IDCardCheckHelper();
        $datas     = [];
        // 公司员工类，先实例化，避免在遍历中多次实例化对象
        $companyStaffModel = new CompanyStaffModel();
        // 读取第二行到最后一行数据，第一行为中文标题，过滤
        $phones = [];
        for ($rowIndex = 2; $rowIndex < (\Yii::$app->params['staff_upload_excel_max_row'] + 1); $rowIndex++) {
            if (false == $row = $this->readRow($rowIndex)) {
                continue;
            }
            // 过滤手机号重复记录
            if (in_array($row['mobilephone'], $phones)) {
                continue;
            }
            $phones[] = $row['mobilephone'];

            $row['company_id'] = $companyId;
            $row['upload_no']  = $uuid;
            $row['uuid']       = StringHelper::uuid();
            // 员工类型映射转换
            $row['staff_type'] = $companyStaffModel->getStaffTypeByText($row['staff_type']);
            $this->verifyInfo($row, $idChecker);
            //TODO 统计错误行数，触发风控规则, 统一已经被三个已经的公司导入

            // 根据手机号判断同一个公司下重复，没有发送好友请求前，可以修改
            if ($this->checkExist($row)) {
                continue;
            }
            $datas[] = $row;
        }
        $this->saveStaffs($companyId, $uuid, $datas);
        return true;
    }

    public function saveStaffs($companyId, $uuid, $rows)
    {
        // 需要特别注意此处，字段名和values数组必须对应，所以采用数组升序排列和后面对应的字段顺序必须一致
        $values = [];
        foreach ($rows as $row) {
            ksort($row);
            $values[] = array_values($row);
        }
        // 设置分表hash
        CompanyStaffModel::setHashId($companyId);
        try {
            if (!empty($values)) {
                // 批量写入数据库
                CompanyStaffModel::getDb()->createCommand()->batchInsert(CompanyStaffModel::tableName(), [
                    'company_id',
                    'create_time',
                    'dimission',
                    'id_no',
                    'mobilephone',
                    'name',
                    'staff_type',
                    'status',
                    'update_time',
                    'upload_no',
                    'uuid',
                ], $values)->execute();
            }

            // 更新上传日志，告诉前台已经解析完毕
            (new StaffUploadLogModel())->updateStatus($uuid, StaffUploadLogModel::STATUS_PROCESSED);
        } catch (\Exception $e) {
            (new StaffUploadLogModel())->updateStatus($uuid, StaffUploadLogModel::STATUS_PROCESSED_FAIL);
            Trace::addLog('handle_staff_excel_exception', 'error', ['values' => $values, 'exception' => $e->__toString(), 'msg' => '解析excel，插入数据异常'], __CLASS__);
        }
    }

    /**
     * @param $row
     */
    public function checkExist($row)
    {
        $existStaff = CompanyStaffModel::findCompanyStaffByMobilePhone($row['company_id'], $row['mobilephone']);
        if ($existStaff) {
            // 信息有误的从新更新,或者信息基础验证正确，但是还在处理中的 或者已经删除的
            if (in_array($existStaff['status'], CompanyStaffModel::$statusMap[CompanyStaffModel::STATUS_GROUP_WRONG_INFO]['types']) || $existStaff['status'] == CompanyStaffModel::STATUS_UPLOAD_INFO_RIGHT || $existStaff['status'] == CompanyStaffModel::STATUS_FRIEND_DELETE) {
                $newData = array_merge($existStaff, $row);
                try {
                    (new CompanyStaffModel())->updateStaff($newData);
                } catch (\Exception $e) {
                    Trace::addLog('handle_staff_excel_exception', 'error', ['row' => $row, 'values' => $newData, 'exception' => $e->__toString(), 'msg' => '解析excel，更新数据异常'], __CLASS__);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 基础验证员工基本信息
     * @param $row
     * @param $idChecker
     */
    public function verifyInfo(&$row, $idChecker)
    {
        if (!StringHelper::checkMobile($row['mobilephone'])) {
            $row['status'] = CompanyStaffModel::STATUS_UPLOAD_WRONG_PHONE; // 不是合法的手机号
        } elseif (!$idChecker->checkIdentity($row['id_no'])) {
            $row['status'] = CompanyStaffModel::STATUS_UPLOAD_WRONG_IDCARD; // 错误的身份证号
        } elseif (empty($row['name']) || mb_strlen($row['name']) < 2) {
            $row['status'] = CompanyStaffModel::STATUS_UPLOAD_WRONG_NAME; // 姓名出错
        } else {
            $row['status'] = CompanyStaffModel::STATUS_UPLOAD_INFO_RIGHT; // 上传基本信息正常
        }
    }

    /**
     * 从sheet中读取一行
     * @param $rowIndex
     * @return array|bool
     */
    public function readRow($rowIndex)
    {
        $row = [
            'name'        => trim($this->excelReader->getCell('A' . $rowIndex)->getValue()),
            'id_no'       => trim($this->excelReader->getCell('C' . $rowIndex)->getValue()),
            'mobilephone' => trim($this->excelReader->getCell('B' . $rowIndex)->getValue()),
            'staff_type'  => trim($this->excelReader->getCell('D' . $rowIndex)->getValue()),
            'create_time' => time(),
            'update_time' => time(),
            'dimission'   => 0,
        ];
        // 过滤不合法数据，有的数据会造成数据库错误，无法插入数据 手机号包括非数字
        if (preg_match('/[^\d]+/', $row['mobilephone']) || empty($row['mobilephone'])) {
            return false;
        }
        // 过滤不合法数据，有的数据会造成数据库错误，无法插入数据 身份证号包括除0-9X外的数字
        if (preg_match('/[^\dxX]+/', $row['id_no']) || empty($row['id_no'])) {
            return false;
        }
        // 过滤不合法数据，有的数据会造成数据库错误，无法插入数据 姓名长度超过20个汉字
        if (mb_strlen($row['name']) > 20 || empty($row['name'])) {
            return false;
        }
        return $row;
    }

    /**
     * 读取excel文件，返回第一个sheet的读取指针
     * @param $extensionType
     * @param $fileName
     * @throws \Exception
     * @throws \PHPExcel\Reader\Exception
     */
    public function getExcelReader($extensionType, $fileName)
    {
        $objReader = IOFactory::createReader($extensionType);
        $objReader->setReadDataOnly(true); // 只读取excel数据，不解析表格样式，性能优化
        $objReader->setReadFilter(new StaffExcelReadFilterHelper()); // 限制读取的行和列
        $objExcel  = null;
        $worksheet = null;
        // 捕获异常，有可能不是excel文件
        try {
            $objExcel  = $objReader->load($fileName); // 加载excel文件
            $worksheet = $objExcel->getSheet(0); // 固定读取第一个sheet
        } catch (\Exception $e) {
            throw $e;
        }
        $this->excelReader = $worksheet;
        unset($objExcel, $objReader);
    }

    /**
     * Excel5和Excel2007以上内容格式不一样，根据扩展名，返回不同的类型
     * @param $fileName
     * @return bool|null|string
     */
    public function getExcelType($fileName)
    {
        $pathinfo = pathinfo($fileName);

        $extensionType = null;
        if (isset($pathinfo['extension'])) {
            switch (strtolower($pathinfo['extension'])) {
                case 'xlsx': //    Excel (OfficeOpenXML) Spreadsheet
                case 'xlsm': //    Excel (OfficeOpenXML) Macro Spreadsheet (macros will be discarded)
                case 'xltx': //    Excel (OfficeOpenXML) Template
                case 'xltm': //    Excel (OfficeOpenXML) Macro Template (macros will be discarded)
                    $extensionType = 'Excel2007';
                    break;
                case 'xls': //    Excel (BIFF) Spreadsheet
                case 'xlt': //    Excel (BIFF) Template
                    $extensionType = 'Excel5';
                    break;
                default:
                    break;
            }
        }
        return $extensionType === null ? false : $extensionType;
    }
}
