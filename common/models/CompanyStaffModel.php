<?php
/**
 * Created by PhpStorm.
 * User: xingjianqiang
 * Date: 16-4-29
 * Time: 下午1:07
 */

namespace common\models;

use apps\lib;
use yii;
use yii\db\ActiveRecord;

/**
 * 花名册员工表
 * @package apps\models
 *
 * @property int $id 自增id
 * @property string $userId 企业id
 * @property string $name 姓名
 * @property string $phoneNum 手机号
 * @property string $idCard  身份证
 * @property int $status 状态
 */
class CompanyStaffModel extends ActiveRecord
{
    const STATUS_UPLOAD_INFO_RIGHT        = 11;
    const STATUS_UPLOAD_WRONG_PHONE       = 12;
    const STATUS_UPLOAD_WRONG_IDCARD      = 13;
    const STATUS_UPLOAD_WRONG_NAME        = 14;
    const STATUS_UPLOAD_NEED_VERIFY       = 21; // 需要审核
    const STATUS_UPLOAD_VERIFY_FAIL       = 22; // 审核失败
    const STATUS_FRIEND_SEND_REQUEST      = 31; // 企业已经向员工发送请求，待同意
    const STATUS_FRIEND_AGREE             = 32; // 员工已经同意好友请求
    const STATUS_FRIEND_DELETE            = 33; // 企业员工好友关系已经删除
    const STATUS_INFO_VERIFY_WRONG_PHONE  = 41; // 三要素验证手机号格式错误
    const STATUS_INFO_VERIFY_NOT_REG      = 42; // 三要素验证未注册
    const STATUS_INFO_NOT_REALNAME        = 43; // 三要素验证未实名
    const STATUS_INFO_NAME_UNMATCH        = 44; // 三要素验证姓名手机号不匹配
    const STATUS_INFO_WRONG_TYPE_IDCARD   = 45; // 三要素验证身份证格式错误
    const STATUS_INFO_VERIFY_WRONG_IDCARD = 46; // 三要素验证身份证号错误

    const STAFF_TYPE_NORMAL = 1;
    const STAFF_TYPE_MIDDLE = 2;
    const STAFF_TYPE_HIGN   = 3;

    const STATUS_GROUP_ALL          = 0; // 全部状态
    const STATUS_GROUP_FRIEND       = 1; // 好友状态
    const STATUS_GROUP_WAIT_CONFIRM = 2; // 待确认状态
    const STATUS_GROUP_WRONG_INFO   = 3; // 信息错误
    const STATUS_GROUP_WAIT_AUDIT   = 4; // 待审核
    const STATUS_GROUP_AUDIT_FAIL   = 5; // 审核失败

    public static $staffTypeMap = [
        1 => '普通员工',
        2 => '中层',
        3 => '高管',
    ];

    public static $statusMap = [
        0 => [
            'text'  => '全部',
            'id'    => 0,
            'types' => [12, 13, 14, 21, 22, 31, 32, 41, 42, 43, 44, 45, 46],
        ],
        1 => [
            'text'  => '好友',
            'id'    => 1,
            'types' => [32],
        ],
        2 => [
            'text'  => '待员工确认',
            'id'    => 2,
            'types' => [31, 42],
        ],
        3 => [
            'text'  => '信息有误',
            'id'    => 3,
            'types' => [12, 13, 14, 41, 43, 44, 45, 46],
        ],
        4 => [
            'text'  => '待审核',
            'id'    => 4,
            'types' => [21],
        ],
        5 => [
            'text'  => '审核失败',
            'id'    => 5,
            'types' => [22],
        ],
        6 => [
            'text'  => '处理中',
            'id'    => 6,
            'types' => [11],
        ],
    ];
    /**
     * @var int 对应表的hashId
     */
    private static $hashId;

    public static function tableName()
    {
        self::$hashId = empty(self::$hashId) ? 0 : self::$hashId;
        return 'company_staff_' . self::$hashId;
    }

    public function rules()
    {
        return [
            [['company_id', 'uuid', 'mobilephone', 'staff_type', 'dimission', 'status', 'create_time', 'update_time', 'upload_no'], 'integer'],
            [['jdb_id'], 'string', 'max' => 32],
            [['id_no'], 'string', 'max' => 18],
            [['name'], 'string', 'max' => 64],
        ];
    }

    public function getStaffTypeByText($text)
    {
        foreach (self::$staffTypeMap as $id => $name) {
            if ($name == $text) {
                return $id;
            }
        }
        return false;
    }

    /**
     * 根据企业id获取新上传的员工列表
     *
     * @param string $companyId 企业id
     * @param int $status 状态 默认为初步验证成功
     * @return array[] CompanyStaffModel
     */
    public static function findStaffByCompany($companyId, $status = null)
    {
        self::setHashId($companyId);
        $status = $status == null ? CompanyStaffModel::STATUS_UPLOAD_INFO_RIGHT : $status;
        return self::find()->where(['company_id' => $companyId, 'status' => $status])->asArray()->all();
    }

    /**
     * 根据企业获取hashId
     * @param int $companyId 企业id
     * @return int $hashId 企业员工所在表的hashId
     */
    public static function setHashId($companyId)
    {
        self::$hashId = bcmod($companyId, 1024); //暂时不定义常量
    }

    /**
     * 更新企业员工信息
     * @param array $companyStaff  新的员工数据
     *
     * @return array $status 更新状态
     */
    public function updateStaff($companyStaff)
    {
        $companyStaff['update_time'] = time();
        try {
            $this->load($companyStaff, '');
            $this->updateAll($companyStaff, 'id=:id', [':id' => $companyStaff['id']]);
        } catch (\Exception $e) {
            lib\Trace::addLog('saveStaff_exception', 'info', ['file' => __FILE__, 'line' => __LINE__, 'data' => ['msg' => $e->getMessage(), 'data' => $companyStaff]]);
            return ['errno' => 500, 'msg' => $e->getMessage()];
        }

        return ['errno' => 200, 'msg' => 'ok'];
    }

    /**
     * 花名册员工三要素校验
     * @param $companyStaffs
     * @param array $companyInfo 企业信息
     */
    public function verifyStaff($companyStaffs, $companyInfo)
    {
        if (empty($companyStaffs)) {
            return;
        }
        //上传的花名册有员工 之前会完成基本的员工信息验证
        foreach ($companyStaffs as $companyStaff) {
            $res = $this->verfiySingleStaff($companyStaff, $companyInfo);
            lib\Trace::addLog("verifyStaff_addLog", "info",
                ['CompanyStaffModel' => $companyStaff, "rs" => $res, 'compInfo' => $companyInfo]
            );
//            if ($res['errno'] != 200) {
            //                continue;
            //            }
        }
    }
    /**
     * @param $where company_id必填
     * @return array|null|ActiveRecord
     * 通过查询条件查询结果
     */
    public static function findOneByParam($company_id, $where)
    {
//        self::$hashId = $company_id;
        self::setHashId($company_id);
        $tableName = self::tableName();
        $query     = new yii\db\Query();
        $ret       = $query->select(['*'])
            ->from($tableName)
            ->where(['company_id' => $company_id])
            ->andWhere($where)
            ->one();
        return $ret;
    }

    /**
     * 根据条件查询信息
     * @param $company_id
     * @param $where
     * @return array
     */
    public static function findAllByParam($company_id, $where)
    {
        self::setHashId($company_id);
        $tableName = self::tableName();
        $query     = new yii\db\Query();
        $ret       = $query->select(['*'])
            ->from($tableName)
            ->where(['company_id' => $company_id])
            ->andWhere($where)
            ->all();
        return $ret;
    }
    /**
     * 根据上传批次号查询
     * @param $companyId
     * @param $uploadNo
     * @return array|yii\db\ActiveRecord[]
     */
    public function findByUploadNo($companyId, $uploadNo, $pageNo, $pageSize)
    {
        self::setHashId($companyId);
        $query = self::find()
            ->select(['id', 'uuid', 'company_id', 'name', 'id_no', 'mobilephone', 'staff_type', 'dimission', 'status', 'create_time', 'update_time'])
            ->where(['company_id' => $companyId, 'upload_no' => $uploadNo]);
        $total = $query->count();
        $list  = $query->orderBy('id DESC')
            ->offset(($pageNo - 1) * $pageSize)
            ->limit($pageSize)
            ->asArray()
            ->all();
        return ['total' => $total, 'list' => $list];
    }

    /**
     * 根据员工类型，状态分页查询，同时返回总员工数，双向关系数
     * @param $companyId
     * @param $staffType
     * @param $status
     * @param $lastId
     * @param int $pageNo
     * @param int $pageSize
     * @return array
     */
    public function findByTypeAndStatus($companyId, $staffType, $status, $lastId, $pageNo = 1, $pageSize = 20)
    {
        self::setHashId($companyId);
        $query = self::find()->select(['id', 'uuid', 'company_id', 'name', 'id_no', 'mobilephone', 'staff_type', 'dimission', 'status', 'create_time', 'update_time', 'is_salary', 'total_salary AS salary', 'jdb_id']);
        $query->where(['company_id' => $companyId]);
        if (!empty($staffType)) {
            $query->andWhere(['staff_type' => $staffType]);
        }
        if (!empty($status)) {
            $query->andWhere(['status' => self::$statusMap[$status]['types']]);
        }
        // 列表不显示已经删除好友关系的
        $query->andWhere(['<>', 'status', self::STATUS_FRIEND_DELETE]);
        if ($lastId > 0) {
            $query->andWhere(['<', 'id', $lastId]);
        }
        if ($lastId == 0) {
            $pageNo = 1;
        }
        $total = $query->count();
        $query->offset(($pageNo - 1) * $pageSize);
        $query->limit($pageSize);
        $query->orderBy('id DESC');
        $list = $query->asArray()->all();

        $totalFriend = self::find()->where(['company_id' => $companyId, 'status' => self::STATUS_FRIEND_AGREE])->count();
        $totalStaff  = self::find()->where(['company_id' => $companyId])->count();
        return ['total' => $total, 'list' => $list, 'totalFriend' => $totalFriend, 'totalStaff' => $totalStaff];
    }

    /**
     * 根据手机号查询企业员工信息
     * @param $companyId
     * @param $mobilePhone
     * @return array|null|ActiveRecord
     */
    public static function findCompanyStaffByMobilePhone($companyId, $mobilePhone)
    {
        self::setHashId($companyId);
        return self::find()->select(['id', 'uuid', 'company_id', 'name', 'id_no', 'mobilephone', 'staff_type', 'dimission', 'status', 'create_time', 'update_time'])->where([
            'company_id'  => $companyId,
            'mobilephone' => $mobilePhone,
        ])->asArray()->one();
    }

    /**
     * 查找不为唯一标识的当前企业的花名册内容
     * @param $company_id
     * @param $where
     * @param $id
     * @return int|string
     */
    public static function getCountByParamsNotEqualId($company_id, $where, $id)
    {
        self::setHashId($company_id);
        $tableName = self::tableName();
        $query     = new yii\db\Query();
        $ret       = $query->select(['*'])
            ->from($tableName)
            ->where($where)
        //->andWhere($where)
            ->andWhere(['<>', 'id', $id])
            ->count();
        return $ret;
    }
    /**
     * 修改花名册的状态和类型
     */
    public function updateStatusAndStaffType($companyStaff)
    {
        $company_id = $companyStaff['company_id'];
        $id         = $companyStaff['id'];
        $status     = $companyStaff['status'];
        $staff_type = $companyStaff['staff_type'] + 1;
        $where      = ['company_id' => $company_id, 'id' => $id];
        self::setHashId($company_id);
        $tableName = self::tableName();
        $query     = new yii\db\Query();
        $ret       = $query->select(['*'])
            ->from($tableName)
            ->where(['company_id' => $company_id, 'id' => $id])
            ->andWhere($where)
            ->all();
        lib\Trace::addLog('saveStaff_exception', 'info', ['data' => $ret]);

        try {
            foreach ($ret as $k => $v) {
                $v['status']      = $status;
                $v['staff_type']  = $staff_type;
                $v['update_time'] = time();
                $this->load($v, '');
                $this->updateAll($v, 'id=:id', [':id' => $v['id']]);
            }
        } catch (\Exception $e) {
            lib\Trace::addLog('saveStaff_exception', 'error', ['file' => __FILE__, 'line' => __LINE__, 'data' => ['msg' => $e->getMessage(), 'data' => $companyStaff]]);
            return ['errno' => 500, 'msg' => $e->getMessage()];
        }

        return ['errno' => 200, 'msg' => 'ok'];
    }
    public function getStaffCount($company_id)
    {

        $query = self::find();
        $query->select("count(1) as c");
        $query->where(['company_id' => $company_id, 'status' => self::STATUS_FRIEND_AGREE]);
        return $query->asArray()->one();
    }
}
