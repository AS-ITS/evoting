<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "groupMember".
 *
 * @property int $groupId 群組識別碼
 * @property int $cn 群組成員(cn)
 * @property string $isWrite 可否修改群組投票
 * @property string $isOwner 可否修群組成員
 */
class GroupMember extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'groupMember';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['groupId', 'cn'], 'required', 'on' => 'create'],
            [['groupId'], 'integer', 'on' => 'create'],
            [['cn', 'isWrite', 'isOwner'], 'string', 'on' => 'create'],
            [['isWrite'], 'in', 'range' => array_keys(Yii::$app->params['ct.group.writeAry']),'on' => 'create'],
            [['isOwner'], 'in', 'range' => array_keys(Yii::$app->params['ct.group.ownerAry']),'on' => 'create'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'groupId' => '群組 ID',
            'cn' => '群組成員',
            'isWrite' => '編輯投票與否',
            'isOwner' => '群組管理與否',
        ];
    }

    /**
     * 取得群組資訊
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMemberList($groupId)
    {
        return $this->find()
            ->where(['groupId'=>$groupId])
            ->orderBy(['isOwner'=>SORT_DESC,'isWrite'=>SORT_DESC]);
    }

    /**
     * 新增群組成員
     */
    public function add($groupId, $cn, $isWrite=false, $isOwner=false)
    {
        $this->scenario = 'create';
        $this->groupId = $groupId;
        $this->cn = $cn;
        $this->isWrite = $isWrite ? 'Y' : 'N';
        $this->isOwner = $isOwner ?'Y' : 'N';
        return $this->save();
    }
}
