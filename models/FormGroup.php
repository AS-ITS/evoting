<?php

namespace app\models;

use Yii;
use yii\helpers\Json;
use app\components\helper\ArrayHelper;

/**
 * FormGroup represents the model behind the search form of `app\models\Group`.
 */
class FormGroup extends Group
{
    /**
     * Returns the validation rules for attributes.
     * 
     * @return array validation rules
     */
    public function rules()
    {
        return [
            [['groupId', 'groupName', 'isRoutine'], 'safe', 'on'=>'search'],
            [['groupName', 'isRoutine'], 'required','on'=>['create', 'update']],
            [['isRoutine'], 'string','on'=>['create', 'update']],
            [['isRoutine'], 'in', 'range' => array_keys(Yii::$app->params['ct.group.routineAry']),'on'=>['create', 'update']],
            [['groupName'], 'string', 'max' => 100,'on'=>['create', 'update']],
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
     * 建立群組表格瀏覽
     *
     * @param  string $type
     * @return \yii\data\ActiveDataProvider
     */
    public function getGroupList($type = 'all')
    {
        $DataProvider = new DataProvider;
        $this->scenario = 'search';
        switch ($type) {
            case 'all':
                $query = $this->getList();
                break;

            case 'member':
                $query = $this->getList()->leftJoin('groupMember', 'groupMember.groupId = group.groupId')->where(['groupMember.cn' => Yii::$app->user->id]);
                break;

            default:
                $query = $this->getList();
                break;
        }
        return $DataProvider->getBasicDataProvider($query);
    }

    /**
     * 取得群組資料
     *
     * @param  string $type
     * @return array
     */
    public function getGroups($type = 'all')
    {
        switch ($type) {
            case 'all':
                $result = $this
                    ->getList()
                    ->asArray()->all();
                break;

            case 'member':
                $result = $this
                    ->getList()
                    ->leftJoin('groupMember', 'groupMember.groupId = group.groupId')
                    ->where(['groupMember.cn' => Yii::$app->user->id])
                    ->asArray()->all();
                break;

            default:
                $result = $this->getList();
                break;
        }
        return $result;
    }

    /**
     * 取得群組資料
     * 
     * @param int $groupId 群組 ID
     * 
     * @return \yii\data\ActiveDataProvider
     */
    public function getGroupInfo($groupId)
    {
        $DataProvider = new DataProvider;
        $this->scenario = 'search';
        return $DataProvider->getBasicDataProvider($this->getInfo($groupId));
    }

    /**
     * 建立群組
     *
     * @param array $post 表單資料
     *
     * @return bool
     */
    public function createBase($post)
    {
        $this->scenario = 'create';
        $this->load($post);

        if($this->save())
        {
            Logs::add(Logs::GROUP_CREATE, Json::encode($this->groupId, 336));
            $GroupMember = new GroupMember;
            return $GroupMember->add($this->groupId,Yii::$app->user->identity->getId(), true, true);
        }
        
        foreach($this->errors as $message)
        {
            Logs::add(Logs::GROUP_CREATE_FAIL, Json::encode($this->errors, 336));
            Yii::$app->session->addFlash('error', $message[0]);
        }
        return false;
    }

    /**
     * 編輯群組
     *
     * @param int $groupId 群組名稱
     * @param array $post 表單資料
     *
     * @return bool
     */
    public function updateBase($groupId, $post)
    {
        $group = self::findOne($groupId);
        if (is_null($group))
            return false;
        $group->scenario = 'update';
        $group->load($post);
        $oldAttributes = $group->oldAttributes;

        if(!$group->save()) // 資料驗證
        {
            foreach($group->errors as $message)
            {
                Yii::$app->session->addFlash('error', $message[0]);
            }
            Logs::add(Logs::GROUP_EDIT_FAIL, Json::encode(['groupId' => $group->groupId]+$group->errors, 336));
            return false;
        }
        //比較差異
        $attributesDiff = ArrayHelper::getAttributesMigration($group->attributes, $oldAttributes);
        // LOG紀錄: 修改儲存差異
        Logs::add(Logs::GROUP_EDIT, Json::encode(['groupId' => $group->groupId]+$attributesDiff, 336));

        return true;
    }
}