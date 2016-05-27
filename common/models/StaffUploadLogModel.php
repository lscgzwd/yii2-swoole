<?php

namespace common\models;

use apps\lib\Trace;
use Yii;

/**
 * This is the model class for table "staff_upload_log".
 *
 * @property integer $id
 * @property integer $company_id
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $status
 * @property string $uuid
 * @property string $file
 */
class StaffUploadLogModel extends \yii\db\ActiveRecord
{
    const STATUS_UPLOADED       = 0; // 上传了
    const STATUS_PROCESSED      = 1; // 已处理
    const STATUS_ROW_LIMIT      = 2; // 超过数目限制
    const STATUS_FRIEND_SENT    = 3; // 已验证三要素，已发送好友请求
    const STATUS_PROCESSED_FAIL = 4; // 解析失败
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'staff_upload_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_id', 'create_time', 'update_time', 'file'], 'required'],
            [['company_id', 'create_time', 'update_time', 'status', 'uuid'], 'integer'],
            [['file'], 'string', 'max' => 128],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'          => '自增',
            'company_id'  => '公司编号',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
            'status'      => '状态 0 未处理 1已处理',
            'uuid'        => '唯一编号',
            'file'        => 'File',
        ];
    }

    /**
     * 更新状态
     * @param $uuid
     * @param $status
     * @return int
     */
    public function updateStatus($uuid, $status)
    {
        return $this->updateAll(['status' => $status, 'update_time' => time()], ['uuid' => $uuid]);
    }

    /**
     * 添加一行记录
     * @param $companyId
     * @param $uuid
     * @param $filename
     * @return bool
     */
    public function addExcel($companyId, $uuid, $filename)
    {
        try {
            $data = [
                'company_id'  => $companyId,
                'status'      => 0,
                'uuid'        => $uuid,
                'file'        => $filename,
                'create_time' => $_SERVER['REQUEST_TIME'],
                'update_time' => $_SERVER['REQUEST_TIME'],
            ];
            if ($this->load($data, '') && $this->save()) {
                return true;
            } else {
                Trace::addLog('upload_staff_save_error', 'error', ['msg' => '上传员工excel保存数据库失败', 'error' => $this->getErrors()]);
                return false;
            }
        } catch (\Exception $e) {
            Trace::addLog('upload_staff_save_exception', 'error', ['msg' => '上传员工excel保存数据库失败', 'error' => $e->__toString()]);
            return false;
        }
    }

    /**
     * 根据UUID查询日志
     * @param $companyId
     * @param $uuid
     * @return array|null|\yii\db\ActiveRecord
     */
    public function findByUUID($companyId, $uuid)
    {
        return self::find()->select(['status', 'id', 'uuid'])->where(['company_id' => $companyId, 'uuid' => $uuid])->asArray()->one();
    }

    /**
     * 根据状态查询上传excel记录
     * @param $status
     * @return array|\yii\db\ActiveRecord[]
     */
    public function findByStatus($status)
    {
        return self::find()->select(['status', 'id', 'uuid', 'company_id'])->where(['status' => $status])->asArray()->all();
    }
}
