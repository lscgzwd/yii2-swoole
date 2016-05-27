<?php
/**
 * swoole异步task执行类
 * 通过$server->task()投递
 * User: lusc
 * Date: 2016/5/7
 * Time: 1:05
 */

namespace common\helpers;

use apps\lib\Trace;
use common\models\CompanyStaffModel;
use common\models\StaffUploadLogModel;
use PHPExcel\IOFactory;

class SwooleTaskHelper
{
    /**
     * swoole\task解析入口，传入JSON数据
     * @author lusc
     * @param $data
     */
    public function run($data)
    {
        $data   = json_decode($data, true);
        $method = $data['type'];
        if (method_exists($this, $method)) {
            try {
                $this->$method($data['data']);
            } catch (\Exception $e) {
                Trace::addLog('execute_task_exception', 'error', ['data' => $data, 'exception' => $e->__toString(), 'msg' => '执行task任务发生异常'], 'swooletask');
            }
        } else {
            Trace::addLog('invalid_task_type', 'error', ['data' => $data], 'swooletask');
        }
    }

    /**
     * 解析员工花名册
     * @author lusc
     * @param $data
     */
    public function handleStaffExcel($data)
    {
        $uuid      = $data['uuid'];
        $companyId = $data['companyId'];
        $fileName  = $data['fileName'];
        $pathinfo  = pathinfo($fileName);

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
            if (!empty($extensionType)) {
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
                    (new StaffUploadLogModel())->updateStatus($uuid, StaffUploadLogModel::STATUS_PROCESSED_FAIL);
                    Trace::addLog('handle_staff_excel_exception', 'error', ['data' => $data, 'exception' => $e->__toString(), 'msg' => '解析excel，excel文件读取异常'], 'swooletask-staff-excel');
                    return false;
                }
                // 标题占一行，所以需要加2 获取最大限制行数5000行加1行，5001行的手机号，如果还有值，说明上传的excel超过了限制
                $maxCell = trim($worksheet->getCell('B' . (\Yii::$app->params['staff_upload_excel_max_row'] + 2))->getValue());
                if (!empty($maxCell)) {
                    (new StaffUploadLogModel())->updateStatus($uuid, StaffUploadLogModel::STATUS_ROW_LIMIT);
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
                    $row = [
                        'company_id'  => $companyId,
                        'name'        => trim($worksheet->getCell('A' . $rowIndex)->getValue()),
                        'id_no'       => trim($worksheet->getCell('C' . $rowIndex)->getValue()),
                        'mobilephone' => trim($worksheet->getCell('B' . $rowIndex)->getValue()),
                        'staff_type'  => trim($worksheet->getCell('D' . $rowIndex)->getValue()),
                        'uuid'        => StringHelper::uuid(),
                        'upload_no'   => $uuid,
                        'create_time' => time(),
                        'update_time' => time(),
                        'dimission'   => 0,
                    ];
                    if (in_array($row['mobilephone'], $phones)) {
                        continue;
                    }
                    // 过滤不合法数据，有的数据会造成数据库错误，无法插入数据 手机号包括非数字
                    if (preg_match('/[^\d]+/', $row['mobilephone']) || empty($row['mobilephone'])) {
                        continue;
                    }
                    // 过滤不合法数据，有的数据会造成数据库错误，无法插入数据 身份证号包括除0-9X外的数字
                    if (preg_match('/[^\dxX]+/', $row['id_no']) || empty($row['id_no'])) {
                        continue;
                    }
                    // 过滤不合法数据，有的数据会造成数据库错误，无法插入数据 姓名长度超过20个汉字
                    if (mb_strlen($row['name']) > 20 || empty($row['name'])) {
                        continue;
                    }
                    // 员工类型映射转换
                    $row['staff_type'] = isset(CompanyStaffModel::$staffTypeTextMap[$row['staff_type']]) ? CompanyStaffModel::$staffTypeTextMap[$row['staff_type']] : CompanyStaffModel::STAFF_TYPE_NORMAL;
                    if (!StringHelper::checkMobile($row['mobilephone'])) {
                        $row['status'] = CompanyStaffModel::STATUS_UPLOAD_WRONG_PHONE; // 不是合法的手机号
                    } elseif (!$idChecker->checkIdentity($row['id_no'])) {
                        $row['status'] = CompanyStaffModel::STATUS_UPLOAD_WRONG_IDCARD; // 错误的身份证号
                    } elseif (empty($row['name']) || mb_strlen($row['name']) < 2) {
                        $row['status'] = CompanyStaffModel::STATUS_UPLOAD_WRONG_NAME; // 姓名出错
                    } else {
                        $row['status'] = CompanyStaffModel::STATUS_UPLOAD_INFO_RIGHT; // 上传基本信息正常
                    }
                    //TODO 统计错误行数，触发风控规则, 统一已经被三个已经的公司导入
                    $phones[] = $row['mobilephone'];
                    // 根据手机号判断同一个公司下重复，没有发送好友请求前，可以修改
                    $existStaff = CompanyStaffModel::findCompanyStaffByMobilePhone($companyId, $row['mobilephone']);
                    if ($existStaff) {
                        // 信息有误的从新更新,或者信息基础验证正确，但是还在处理中的 或者已经删除的
                        if (in_array($existStaff['status'], CompanyStaffModel::$statusMap[3]['types']) || $existStaff['status'] == CompanyStaffModel::STATUS_UPLOAD_INFO_RIGHT || $existStaff['status'] == CompanyStaffModel::STATUS_FRIEND_DELETE) {
                            $newData = array_merge($existStaff, $row);
                            try {
                                $companyStaffModel->updateAll($newData, ['id' => $existStaff['id']]);
                            } catch (\Exception $e) {
                                Trace::addLog('handle_staff_excel_exception', 'error', ['data' => $data, 'values' => $newData, 'exception' => $e->__toString(), 'msg' => '解析excel，更新数据异常'], 'swooletask-staff-excel');
                            }
                        }
                        continue;
                    }

                    $datas[] = $row;
                }
                // 需要特别注意此处，字段名和values数组必须对应，所以采用数组升序排列和后面对应的字段顺序必须一致
                $values = [];
                foreach ($datas as $row) {
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
                    Trace::addLog('handle_staff_excel_exception', 'error', ['data' => $data, 'values' => $values, 'exception' => $e->__toString(), 'msg' => '解析excel，插入数据异常'], 'swooletask-staff-excel');
                }
                return true;
            } else {
                (new StaffUploadLogModel())->updateStatus($uuid, StaffUploadLogModel::STATUS_PROCESSED_FAIL);
                Trace::addLog('handle_staff_excel_error', 'error', ['data' => $data, 'msg' => '文件类型错误'], 'swooletask-staff-excel');
            }
        } else {
            (new StaffUploadLogModel())->updateStatus($uuid, StaffUploadLogModel::STATUS_PROCESSED_FAIL);
            Trace::addLog('handle_staff_excel_error', 'error', ['data' => $data, 'msg' => '文件类型错误'], 'swooletask-staff-excel');
        }
    }
}
