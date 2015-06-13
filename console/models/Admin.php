<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "admin".
 *
 * @property integer $userid
 * @property string $username
 * @property string $nickname
 * @property string $password
 * @property integer $create_time
 * @property integer $update_time
 */
class Admin extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'admin';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'nickname', 'password', 'create_time', 'update_time'], 'required'],
            [['create_time', 'update_time'], 'integer'],
            [['username', 'password'], 'string', 'max' => 32],
            [['nickname'], 'string', 'max' => 64]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'userid' => 'Userid',
            'username' => 'Username',
            'nickname' => 'Nickname',
            'password' => 'Password',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
