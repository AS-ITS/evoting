<?php

namespace app\models;

use Yii;
use yii\helpers\Json;
use app\models\GroupMember;
use app\components\helper\ArrayHelper;

/**
 * FormGroup represents the model behind the search form of `app\models\Group`.
 */
class FormGroupMember extends GroupMember
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['groupId', 'cn'], 'required', 'on' => ['create', 'update']],
            [['groupId'], 'integer', 'on' => ['create', 'update']],
            [['cn', 'isWrite', 'isOwner'], 'string', 'on' => ['create', 'update']],
            [['isWrite'], 'in', 'range' => array_keys(Yii::$app->params['ct.group.writeAry']), 'on' => ['create', 'update']],
            [['isOwner'], 'in', 'range' => array_keys(Yii::$app->params['ct.group.ownerAry']), 'on' => ['create', 'update']],
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
     * 取得群組成員表格瀏覽
     * 
     * @param int $groupId 群組 ID
     * 
     * @return \yii\data\ActiveDataProvider
     */
    public function getGroupMemberList($groupId)
    {
        $GroupMember = new GroupMember;
        $GroupMember->scenario = 'search';
        $DataProvider = new DataProvider;
        return $DataProvider->getBasicDataProvider($GroupMember->getMemberList($groupId));
    }

    /**
     * 建立群組成員
     *
     * @param array $post 表單資料
     *
     * @return bool
     */
    public function createMember($post)
    {
        $this->scenario = 'create';
        $this->load($post);

        // 檢查成員是否已存在
        $member = self::findOne(['groupId' => $this->groupId, 'cn' => $this->cn]);
        if ($member) {
            Yii::$app->session->addFlash('error', '該成員已存在!');
            Logs::add(Logs::GROUP_MEMBER_CREATE_FAIL, Json::encode(['groupId' => $this->groupId, 'message' => '成員已存在'], 336));
            return false;
        }
        
        // 資料驗證
        if(!$this->save()) 
        {
            foreach($this->errors as $message)
            {
                Yii::$app->session->addFlash('error', $message[0]);
            }
            Logs::add(Logs::GROUP_MEMBER_CREATE_FAIL, Json::encode(['groupId' => $this->groupId]+$this->errors, 336));
            return false;
        }

        Logs::add(Logs::GROUP_MEMBER_CREATE, Json::encode(['groupId' => $this->groupId]+$this->attributes, 336));
        return true;
    }

    /**
     * 編輯群組成員
     *
     * @param int $groupId 群組名稱
     * @param int $cn 使用者ID
     * @param array $post 表單資料
     *
     * @return bool
     */
    public function updateMember($groupId, $cn, $post)
    {
        $member = self::findOne(['groupId' => $groupId, 'cn' => $cn]);
        if (is_null($member))
            return false;
        $member->scenario = 'update';
        $member->load($post);
        $oldAttributes = $member->oldAttributes;

        if(!$member->save()) // 資料驗證
        {
            foreach($member->errors as $message)
            {
                Yii::$app->session->addFlash('error', $message[0]);
            }
            Logs::add(Logs::GROUP_MEMBER_EDIT_FAIL, Json::encode(compact('groupId', 'cn')+$member->errors, 336));
            return false;
        }
        //比較差異
        $attributesDiff = ArrayHelper::getAttributesMigration($member->attributes, $oldAttributes);
        // LOG紀錄: 修改儲存差異
        Logs::add(Logs::GROUP_MEMBER_EDIT, Json::encode(compact('groupId', 'cn')+$attributesDiff, 336));

        return true;
    }

    /**
     * 刪除群組成員
     *
     * @param int $groupId 群組名稱
     * @param int $cn 使用者ID
     * @param array $post 表單資料
     *
     * @return bool
     */
    public function deleteMember($groupId, $cn, $post)
    {
        $member = self::findOne(['groupId' => $groupId, 'cn' => $cn]);

        if ($member === null) {
            Yii::$app->session->addFlash('error', '找不到該群組成員');
            return false;
        }

        if(!$member->delete()) // 資料驗證
        {
            foreach($member->errors as $message)
            {
                Yii::$app->session->addFlash('error', $message[0]);
            }
            Logs::add(Logs::GROUP_MEMBER_DELETE_FAIL, Json::encode(compact('groupId', 'cn')+$member->errors, 336));
            return false;
        }

        Logs::add(Logs::GROUP_MEMBER_DELETE, Json::encode(compact('groupId', 'cn'), 336));
        return true;
    }
}