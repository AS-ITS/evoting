<?php
namespace app\models;

use Yii;
use yii\helpers\Json;
use app\components\helper\ArrayHelper;

class FormResultsConfig extends ResultsConfig
{
    /**
     * 創建時預設值
     */
    static public $defFieldSort = 'instName,Name,ballotCounts,elected';

    /**
     * 從頭新增
     */
    static public $FsBefore = 0;

    /**
     * 從尾新增
     */
    static public $FsAfter = 1;
    
    /**
     * Returns the validation rules for attributes.
     * 
     * @return array validation rules
     */
    public function rules()
    {
        return [
            [['voteID', 'isShow', 'isLogin', 'isParty', 'sort', 'showFieldSort'], 'required', 'on'=>['update','creator']],
            [['voteID'], 'string', 'max' => 20],
            [['isShow', 'isLogin', 'isParty', 'sort'], 'string', 'max' => 1],
            [['showFieldSort'], 'string', 'max' => 1000],
            [['voteID', 'isShow', 'isLogin', 'isParty', 'sort', 'showFieldSort', 'showElectedStatus'], 'safe', 'on'=>['creator']],
            [['isShow', 'isLogin', 'isParty', 'sort', 'showFieldSort', 'showElectedStatus'], 'safe', 'on'=>['update']],
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
            'isShow' => '投票結果顯示',
            'isLogin' => '登入驗證',
            'isParty' => '分組顯示結果',
            'sort' => '投票結果排序',
            'showElectedStatus' => '投票結果顯示投票狀態',
        ];
    }

    /**
     * 取得資料
     */
    public function getData($voteID)
    {
        return self::findOne($voteID);
    }

    /**
     * 取得設定的欄位排序
     * 
     * @param array $columnsAry 所有欄位的Ary
     * @param array $addField 由後端強制增加的欄位
     * 
     * @return array GridView::$columns
     */
    public function getFieldSort($candiConfig, $columnsAry, $addField = [])
    {
        if(is_null($this->showFieldSort))
            $showFieldSort = explode( ',', self::$defFieldSort);
        else
            $showFieldSort = explode( ',', $this->showFieldSort);

        $candiConfig = (new FormCandiConfig)->getConfigWithVoteID($this->voteID);
        $num = is_null($candiConfig->num) ? 'A' : $candiConfig->num;
        $columns = [];
        foreach($showFieldSort as $showFS)
        {
            if($showFS == 'num')
            {
                if($num == 'A')
                    $columns[] = $columnsAry['autoId'];
                else if($num == 'C')
                    $columns[] = $columnsAry['id'];
                continue;
            }
            else if(ArrayHelper::keyExists($showFS,$columnsAry))
                $columns[] = $columnsAry[$showFS];
        }
        // 正序從頭新增
        $addFKey = array_keys($addField);
        foreach($addFKey as $afk)
        {
            if($addField[$afk] == self::$FsBefore)
                array_unshift($columns, $columnsAry[$afk]);
        }
        // 倒序從尾新增
        $addFKey = array_reverse($addFKey);
        foreach($addFKey as $afk)
        {
            if($addField[$afk] == self::$FsAfter)
                array_push($columns, $columnsAry[$afk]);
        }
        return $columns;
    }
    
    /**
     * 新增或更新投票設定
     *
     * @param  \app\models\FormResultsConfig $model
     * @param  array $post
     * @param  bool $saveAs
     * @return bool
     */
    public function updateConfig($model, $post, $saveAs = false)
    {
        $this->setScenario($saveAs ? 'creator' : 'update');// 設置模型的方案
        $this->load($post);
        
        if (ArrayHelper::getValue($post, [$this->formName(), 'showElectedStatus'])) {
            $this->setAttributes([
                'showElectedStatus' => join(',', $this->showElectedStatus)
            ], false);
        }

        $model->setAttributes($this->attributes, false);
        $oldAttributes = $model->oldAttributes;

        if(!$model->save() && count($model->errors) > 0)
        {
            $session = Yii::$app->session;
            foreach($model->errors as $message)
            {
                $session->addFlash('error', $message);
            }
            // LOG紀錄: 驗證失敗
            Logs::add(
                $saveAs ? Logs::VOTE_COUNT_CONFIG_CREATE_FAIL : Logs::VOTE_COUNT_CONFIG_EDIT_FAIL
                , Json::encode(['voteID' => $model->voteID]+$model->errors, 336)
            );
            return false;
        }

        // LOG紀錄: 修改儲存差異，另存當作新增
        $attributeDiff = ArrayHelper::getAttributesMigration($model->attributes, $oldAttributes);
        if (!empty($attributeDiff) && !$saveAs) {
            Logs::add(Logs::VOTE_COUNT_CONFIG_EDIT, Json::encode(['voteID' => $model->voteID]+$attributeDiff, 336));
        }
        elseif ($saveAs) {
            Logs::add(Logs::VOTE_COUNT_CONFIG_CREATE, Json::encode(['voteID' => $model->voteID]+$model->attributes, 336));
        }
        return true;
    }

    /**
     * 刪除投票結果(配置)
     */
    public function deleteResultsConfigAll($voteID, $party = null)
    {
        if(is_null($party))
        {
            return parent::deleteResultsConfigAll($voteID);
        }
        return parent::deleteResultsConfigAll($voteID, $party);
    }
}
