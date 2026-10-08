<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "group".
 *
 * @property int $groupId 群組識別碼
 * @property string $groupName 群組名稱
 * @property string $isRoutine 是否為例行投票
 */
class Group extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'group';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['groupName', 'isRoutine'], 'required','on'=>['create', 'update']],
            [['groupId'], 'integer', 'on'=>['create', 'update']],
            [['isRoutine'], 'string', 'on'=>['create', 'update']],
            [['isRoutine'], 'in', 'range' => array_keys(Yii::$app->params['ct.group.routineAry']), 'on'=>['create', 'update']],
            [['groupName'], 'string', 'max' => 100, 'on'=>['create', 'update']],
            [['groupId'], 'unique', 'on'=>['create', 'update']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'groupId' => '群組 ID',
            'groupName' => '群組名稱',
            'isRoutine' => '例行投票與否',
        ];
    }

    /**
     * 取得群組資訊
     *
     * @return \yii\db\ActiveQuery
     */
    public function getList()
    {
        return $this->find();
    }

    /**
     * 取得群組資訊
     * 
     * @param int $groupId 群組 ID
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInfo($groupId)
    {
        return $this->find()
            ->where(['groupId'=>$groupId]);
    }
}
