<?php

namespace app\models;

use Yii;
use yii\helpers\Json;
use app\components\Model;
use app\models\CandiData;
use app\components\helper\ArrayHelper;
use yii\data\ActiveDataProvider;
use app\components\helper\FileLoader;

/**
 * FormCandiData represents the model behind the search form of `app\models\CandiData`.
 */
class FormCandiData extends CandiData
{
    /**
     * 需特別處理不得為 NULL 的欄位
     */
    static public $notNullField = [
        'jobLctn'=>'0', 'tCode'=>'', 'instName'=>'', 'title'=>'',
        'instNameE'=>'', 'titleE'=>'', 'NameE'=>'', 'sex'=>'', 'other'=>'',
        'otherColA'=>'', 'otherColB'=>'', 'otherColC'=>'', 'otherColD'=>'', 'otherColE'=>'', 'otherColF'=>'',
        'otherColAE'=>'', 'otherColBE'=>'', 'otherColCE'=>'', 'otherColDE'=>'', 'otherColEE'=>'', 'otherColFE'=>'',
    ];

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [
                ['id', 'orderNum', 'sysId', 'questionID'],
                'integer'
            ],
            [
                ['other'],
                'string'
            ],
            [['photo'], 'file', 'extensions' => 'png, jpg, jpeg', 'on' => ['create', 'update']],
            // search
            [
                ['id', 'voteID', 'party', 'questionID', 'jobLctn', 'instCode', 'tCode', 
                'instName', 'title', 'Name', 'sex', 'isReachThreshold',
                'otherColA', 'otherColB', 'otherColC', 'otherColD', 'otherColE', 'otherColF',
                'orderNum', 'genMode', 'other', 'instNameE', 'titleE', 'NameE',
                'otherColAE', 'otherColBE', 'otherColCE', 'otherColDE', 'otherColEE', 'otherColFE'],
                'safe',
                'on' => ['search']
            ],
            // create
            [
                ['voteID', 'party', 'questionID', 'jobLctn', 'instCode', 'tCode', 
                'instName', 'title', 'Name', 'sex', 'sysId',
                'otherColA', 'otherColB', 'otherColC', 'otherColD', 'otherColE', 'otherColF',
                'orderNum', 'genMode', 'other', 'instNameE', 'titleE', 'NameE',
                'otherColAE', 'otherColBE', 'otherColCE', 'otherColDE', 'otherColEE', 'otherColFE'],
                'safe',
                'on' => ['create'],
            ],
            // update
            [
                ['jobLctn', 'instCode', 'questionID', 'tCode', 
                'instName', 'title', 'Name', 'sex', 'sysId',
                'otherColA', 'otherColB', 'otherColC', 'otherColD', 'otherColE', 'otherColF',
                'orderNum', 'instNameE', 'titleE', 'NameE',
                'otherColAE', 'otherColBE', 'otherColCE', 'otherColDE', 'otherColEE', 'otherColFE', 'other'],
                'safe',
                'on' => ['update'],
            ],
            // delete
            [
                ['voteID', 'party', 'questionID', 'genMode'],
                'safe',
                'on' => ['delete'],
            ],
            // create, update
            [
                ['voteID'],
                'string', 'max' => 20,
                'on' => ['create', 'update']
            ],
            [
                ['party', 'relateParty'],
                'string', 'max' => 12,
                'on' => ['create', 'update', 'delete']
            ],
            [
                ['jobLctn', 'sex', 'isReachThreshold', 'specialHonor'],
                'string', 'max' => 1,
                'on' => ['create', 'update']
            ],
            [
                ['instCode'],
                'string', 'max' => 2,
                'on' => ['create', 'update']
            ],
            [
                ['tCode'],
                'string', 'max' => 3,
                'on' => ['create', 'update']
            ],
            [
                ['genMode'],
                'string', 'max' => 10,
                'on' => ['create', 'update', 'delete']
            ],
            [
                ['backgroundColor'],
                'string', 'max' => 30,
                'on' => ['create', 'update']
            ],
            [
                ['instName', 'title', 'Name', 'otherColA', 'otherColB', 'otherColC', 'otherColD', 'otherColE', 'otherColF'],
                'string', 'max' => 50,
                'on' => ['create', 'update']
            ],
            [
                ['instNameE', 'titleE', 'NameE'],
                'string', 'max' => 100,
                'on' => ['create', 'update']
            ],
            [
                ['otherColAE', 'otherColBE', 'otherColCE', 'otherColDE', 'otherColEE', 'otherColFE'],
                'string', 'max' => 255,
                'on' => ['create', 'update']
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
       return [
            'id' => '自動編號',
            'voteID' => 'Vote ID',
            'party' => '分組',
            'questionID' => '問題',
            'isReachThreshold' => '已達門檻',
            'jobLctn' => '工作地點',
            'instCode' => '單位',
            'tCode' => '人事法規職稱',
            'instName' => '單位',
            'instNameE' => '單位(英文)',
            'title' => '職稱',
            'titleE' => '職稱(英文)',
            'Name' => '名字/名稱',
            'NameE' => '名字/名稱(英文)',
            'sex' => '性別',
            'orderNum' => '自訂排序',
            'otherColA' => '自定義欄位 1',
            'otherColAE' => '自定義欄位 1(英文)',
            'otherColB' => '自定義欄位 2',
            'otherColBE' => '自定義欄位 2(英文)',
            'otherColC' => '自定義欄位 3',
            'otherColCE' => '自定義欄位 3(英文)',
            'otherColD' => '自定義欄位 4',
            'otherColDE' => '自定義欄位 4(英文)',
            'otherColE' => '自定義欄位 5',
            'otherColEE' => '自定義欄位 5(英文)',
            'otherColF' => '自定義欄位 6',
            'otherColFE' => '自定義欄位 6(英文)',
            'genMode' => '生成方式',
            'other' => '保留',
            'photo' => '照片',
            'backgroundColor' => '背景顏色',
            'relateParty' => '關聯組別，給共同問題使用的，用於取得分組的人數',
            'specialHonor' => '特殊榮譽',
            'importParty' => '導入組別',
            'importQuestionID' => '導入問題',
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($voteID, $params = [])
    {
        $tableName = CandiData::tableName();
        $query = CandiData::getCandiListWithVoteID($voteID);

        $this->setScenario('search');
        $params = Model::trimParams($params, $this->className());
        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $query;
        }

        // 輪次
        $voteInfo = (new FormVotes)->getVoteInfo($voteID);
        $query->joinWith(['question' => function (\yii\db\ActiveQuery $query) use ($voteInfo) {
            $query->andOnCondition(['round' => $voteInfo->round]);
        }], true, 'INNER JOIN');

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'orderNum' => $this->orderNum,
        ]);

        $query->andFilterWhere(['=', "$tableName.voteID", $this->voteID])
            ->andFilterWhere(['=', 'party', $this->party])
            ->andFilterWhere(['=', "$tableName.questionID", $this->questionID])
            ->andFilterWhere(['=', 'isReachThreshold', $this->isReachThreshold])
            ->andFilterWhere(['=', 'jobLctn', $this->jobLctn])
            ->andFilterWhere(['=', 'instCode', $this->instCode])
            ->andFilterWhere(['=', 'tCode', $this->tCode])
            ->andFilterWhere(['like', 'instName', $this->instName])
            ->andFilterWhere(['like', 'instNameE', $this->instNameE])
            ->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'titleE', $this->titleE])
            ->andFilterWhere(['like', 'Name', $this->Name])
            ->andFilterWhere(['like', 'NameE', $this->NameE])
            ->andFilterWhere(['=', 'sex', $this->sex])
            ->andFilterWhere(['like', 'otherColA', $this->otherColA])
            ->andFilterWhere(['like', 'otherColAE', $this->otherColAE])
            ->andFilterWhere(['like', 'otherColB', $this->otherColB])
            ->andFilterWhere(['like', 'otherColBE', $this->otherColBE])
            ->andFilterWhere(['like', 'otherColC', $this->otherColC])
            ->andFilterWhere(['like', 'otherColCE', $this->otherColCE])
            ->andFilterWhere(['like', 'otherColD', $this->otherColD])
            ->andFilterWhere(['like', 'otherColDE', $this->otherColDE])
            ->andFilterWhere(['like', 'otherColE', $this->otherColC])
            ->andFilterWhere(['like', 'otherColEE', $this->otherColCE])
            ->andFilterWhere(['like', 'otherColF', $this->otherColD])
            ->andFilterWhere(['like', 'otherColFE', $this->otherColDE])
            ->andFilterWhere(['=', 'genMode', $this->genMode])
            ->andFilterWhere(['like', 'other', $this->other]);

        return $query;
    }

    /**
     * 取得問題候選人列表
     */
    public function getCandiListWithQuestion($voteID, $questionID = null)
    {
        return CandiData::getCandiListWithQuestion($voteID, $questionID);
    }

    /**
     * 取得組別候選人列表
     */
    public function getCandiListWithParty($voteID, $round, $party = null)
    {
        return CandiData::getCandiListWithParty($voteID, $round, $party);
    }

    /**
     * 取得組別及問題候選人列表
     */
    public function getCandiListWithPartyQuestion($voteID, $party = null, $questionID = null, $includeAllParty = true)
    {
        return CandiData::getCandiListWithPartyQuestion($voteID, $party, $questionID, $includeAllParty);
    }

    /**
     * 取得候選人資料
     */
    public function getCandiDataWithVoteID($voteID, $id)
    {
        $model = CandiData::getCandiDataWithVoteID($voteID, $id)->one();
        if(is_null($model))
            return null;
        $this->setScenario('update');
        $this->setAttributes( $model->attributes, false);
        return $this->attributes;
    }

    /**
     * 取得多個候選人
     */
    public function getMultipleCandiData($voteID,$ids)
    {
        return CandiData::getMultipleCandiData($voteID,$ids)->all();
    }

    /**
     * 刪除候選名單資料
     */
    public function deleteCandiDataWithVoteID($voteID, $id)
    {
        $model = CandiData::getCandiDataWithVoteID($voteID, $id)->one();
        if(is_null($model)) {
            return null;
        }    
        $model = CandiData::getModel($model->id);
        if (!empty($model->photo)) {
            $FileLoader = new FileLoader(Yii::getAlias('@filePool'));
            if ($FileLoader->removeImage(DIRECTORY_SEPARATOR.$model->voteID.DIRECTORY_SEPARATOR.$model->photo)) {
                return $model->delete();
            }
            else {
                return false;
            }
        }
        return $model->delete();
    }

    /**
     * 用特定條件刪除候選名單資料
     */
    public function deleteCandiData($voteID, $round, $postData)
    {
        $this->setScenario('delete');
        if($this->load($postData) && $this->validate())
        {
            $this->setAttributes([
                'voteID' => $voteID,
            ], false);
            $delCondition = [];
            foreach($this->attributes as $k => $v)
            {
                if(trim($v) != '') {
                    $delCondition[$k] = $v;
                }
            }
            if (empty($delCondition['questionID'])) {
                $delCondition['questionID'] = (new Questions)->getQuestionsIdList($voteID, $round);
            }
            $delCount = CandiData::deleteAll($delCondition);
            if ($delCount > 0) {
                $formData = ArrayHelper::getValue($postData, $this->formName());
                Logs::add(Logs::VOTE_CANDI_DELETE, Json::encode(compact('voteID')+$formData), 336);
            }
            Yii::$app->session->setFlash('success', "完成刪除候選名單 $delCount 筆！");
            return true;
        }
        foreach($this->errors as $message)
        {
            Logs::add(Logs::VOTE_CANDI_DELETE_FAIL, Json::encode(compact('voteID')+$this->errors), 336);
            Yii::$app->session->addFlash('error', $message[0]);
        }
        return false;
    }

    /**
     * 新增、修改候選名單
     */
    public function updateCandiData($voteID, $id, $postData, $action)
    {
        // 配置驗證器模式、相關模型
        if($action == 'create')
        {
            $model = new CandiData;
        }
        else if($action == 'update')
        {
            $model = CandiData::getModel($id);
        }
        $this->setScenario($action);

        // 加載 post 資料、驗證
        $this->load($postData);
        $this->photo = \yii\web\UploadedFile::getInstance($this, 'photo');
        $this->setAttributes([
            'voteID' => $voteID,
        ], false);
        if($action == 'update')
        {
            $this->setAttributes([
                'id' => $id,
            ], false);
        }

        // 特別處理不得為NULL的欄位
        foreach(FormCandiData::$notNullField as $notNFK => $notNFV)
        {
            if(is_null($this->$notNFK) || trim($this->$notNFK) == '')
                $this->$notNFK = $notNFV;
        }

        if(!$this->validate())
        {
            $status = false;
            $session = Yii::$app->session;
            foreach($this->errors as $message)
            {
                $session->addFlash('error', $message[0]);
            }
            Logs::add(
                $action == 'create' ? Logs::VOTE_CANDI_MANUAL_CREATE_FAIL : Logs::VOTE_CANDI_MANUAL_EDIT_FAIL, 
                Json::encode(compact('voteID')+$this->errors, 336)
            );
            return false;
        }
        
        // 更新資料

        // 處理照片
        if (!empty($this->photo)) {
            $picPath = Yii::getAlias('@filePool').DIRECTORY_SEPARATOR.'candidatePic'.DIRECTORY_SEPARATOR;
            if (!file_exists($picPath.$voteID)) {
                \yii\helpers\FileHelper::createDirectory($picPath.$voteID.DIRECTORY_SEPARATOR, 0755);
            }
            if (empty($model->photo)) {
                $photo = Yii::$app->security->generateRandomString(6).'.'.$this->photo->extension;
            }
            else {
                $photo = $model->photo;
            }
            $this->photo->saveAs($picPath.$voteID.DIRECTORY_SEPARATOR.$photo);
            $this->photo = $photo;
        }
        else {
            $this->photo = $model->photo;
        }

        $model->setAttributes($this->attributes, false);
        $oldAttributes = $model->oldAttributes;

        if(!$model->save() && count($model->errors) > 0)
        {
            $session = Yii::$app->session;
            foreach($model->errors as $message)
            {
                $session->addFlash('error', $message[0]);
            }
            return false;
        }
        $this->id = $model->id;
        if ($action == 'update') {
            //比較差異
            $attributesDiff = ArrayHelper::getAttributesMigration($model->attributes, $oldAttributes);
            // LOG紀錄: 修改儲存差異
            if (!empty($attributesDiff) && $action == 'update') {
                Logs::add(Logs::VOTE_CANDI_MANUAL_EDIT, Json::encode(compact('voteID')+$attributesDiff, 336));
            }
        }
        else {
            Logs::add(Logs::VOTE_CANDI_MANUAL_CREATE, Json::encode(compact('voteID')+$model->attributes, 336));
        }
        
        return true;
    }

    /**
     * 刪除候選名單
     */
    public function deleteAllCandiData($voteID)
    {
        $model = new CandiData;
        return $model->deleteAllCandiData($voteID);
    }
    
    /**
     * 輪次導入候選人
     *
     * @param  string $voteID
     * @param  array $post
     * @param  mixed $questionID
     * @return void
     */
    public function importRound($voteID, $post, $questionID)
    {
        $candi = $this->getCandiListWithQuestion($voteID, $questionID);
        $importParty = $post[self::formName()]['importParty'];
        $importQuestion = $post[self::formName()]['importQuestionID'];
        $question = Questions::findOne(['questionID' => $post[self::formName()]['questionID']]);
        $manageCount = new FormManageCount($voteID, $question->round);
        $ballotCountSort = $manageCount->ballotCountSort;
        $candiData = $ballotCountSort[$question['party']][$questionID]['ranking'];
        $candiData = ArrayHelper::index($candiData, 'candi');

        $transaction = Yii::$app->db->beginTransaction();
        try
        {
            foreach ($candi->each() as $candi) {
                $model = new self;
                $model->setScenario('create');
                $model->setAttributes($candi->attributes, false);
                $model->id = NULL;
                $model->questionID = $importQuestion;
                $model->party = $importParty;
                $model->genMode = 'round';
                $model->isReachThreshold = !empty($post[self::formName()]['id']) && in_array($candi->id, $post[self::formName()]['id']) ? CandiData::REACH_THRESHOLD : '0';
                // 儲存已達門檻的候選人前一輪的輪次和得票數
                if ($model->isReachThreshold && !$candi->isReachThreshold) {
                    $model->other = Json::encode(['round' => $question['round'], 'count' => $candiData[$candi->id]['count']]);
                }
                if (!($flag = $model->save()))
                {
                    foreach($model->errors as $message)
                    {
                        Yii::$app->session->addFlash('error', $message[0]);
                        Logs::add(Logs::VOTE_CANDI_ROUND_FAIL, Json::encode(compact('voteID', 'questionID')+[$message[0]], 336));
                    }
                    $transaction->rollBack();
                    break;
                }
            }
            if ($flag)
            {
                // 完成導入
                $transaction->commit();
                Yii::$app->session->addFlash('success', '輪次導入成功');
                Logs::add(Logs::VOTE_CANDI_ROUND, Json::encode(compact('voteID', 'questionID'), 336));
                return true;
            }
        }
        catch (\Exception $e)
        {
            $transaction->rollBack();
            Yii::error(
                \yii\helpers\VarDumper::dumpAsString(
                    $e->getMessage(),
                    $depth = 10,
                    $highlight = false
                ),
                __METHOD__
            );
            return false;
        }
    }
}
