<?php

namespace app\models;

use Yii;
use yii\helpers\Json;
use app\components\Model;
use app\components\helper\ArrayHelper;

/**
 * This is the model class for table "questions".
 *
 * @property int $questionID 問題識別碼
 * @property string $voteID 投票識別碼
 * @property string $round 輪次
 * @property string $party 組別
 * @property string $title 標題
 * @property string $titleE 英文標題
 * @property string $confirmTitle 投票頁面的確認關卡問題顯示名稱
 * @property string $confirmTitleE 投票頁面的確認關卡問題顯示名稱(英)
 * @property string $description 描述
 * @property string $descriptionE 英文描述
 * @property string $ruleText 自定義投票規則文字
 * @property string $ruleTextE 自定義投票規則文字(英)
 * @property int $numBallots 最多可投票數
 * @property int $leastNumBallots 最少投票數
 * @property int $maxElect 當選人數
 * @property int $numOfKeep 遞補人數
 * @property int|null $numFemaleKeep 女性保留人數
 * @property int|null $population 投票母數
 */
class Questions extends \yii\db\ActiveRecord
{
    public $allParty;

    /**
     * 所有分組問題代碼
     */
    const ALL_PARTY_CODE = 'N';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'questions';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'voteID', 'party', 'title', 'titleE', 'numBallots', 'maxElect', 'leastNumBallots', 'numOfKeep'
            ], 'required', 'on' => ['create', 'update']],
            [['questionID', 'numBallots', 'leastNumBallots', 'maxElect', 'numOfKeep', 'numFemaleKeep', 'round', 'population'], 'integer'],
            [['numBallots', 'maxElect'], 'integer', 'min' => 1],
            [['voteID', 'title', 'confirmTitle'], 'string', 'max' => 20],
            [['party'], 'string', 'max' => 12, 'on' => ['search']],
            [['ruleText'], 'string', 'max' => 50],
            [['titleE', 'confirmTitleE'], 'string', 'max' => 100],
            [['ruleTextE'], 'string', 'max' => 200],
            [['description', 'descriptionE'], 'string'],
            [['allParty'], 'safe'],
            [['description', 'descriptionE'], 'filter', 'filter' => function ($value) {
                return \yii\helpers\HtmlPurifier::process($value, Yii::$app->params['HtmlPurifier.config']);
            }],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'voteID' => 'Vote ID',
            'round' => '輪次',
            'party' => '組別',
            'questionID' => 'Question ID',
            'title' => '標題',
            'titleE' => '英文標題',
            'confirmTitle' => '自定義未圈選顯示名稱',
            'confirmTitleE' => '自定義未圈選顯示名稱(英)',
            'description' => '描述',
            'descriptionE' => '英文描述',
            'ruleText' => '自定義投票規則文字',
            'ruleTextE' => '自定義投票規則文字(英)',
            'numBallots' => '最多可投票數',
            'leastNumBallots' => '最少應投票數',
            'maxElect' => '當選人數',
            'numOfKeep' => '遞補人數',
            'numFemaleKeep' => '女性保留人數',
            'population' => '投票母數',
            'allParty' => '共同投票',
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($voteID, $round=null, $params = [])
    {
        $query = self::getQuestionListWithVoteID($voteID);

        $this->setScenario('search');
        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            return $query;
        }

        // grid filtering conditions
        $query->andFilterWhere(['=', 'voteID', $this->voteID])
            ->andFilterWhere(['=', 'party', $this->party])
            ->andFilterWhere(['=', 'round', $round])
            ->andFilterWhere(['like', 'title', $this->title]);

        return $query;
    }

    /**
     * 獲取對應問題投票限制資訊
     *
     * @param  string|int $questionID
     * @return static|null
     */
    public function getQuestionInfo($questionID = null)
    {
        return self::findOne(['questionID' => $questionID]);
    }

    /**
     * 獲取全部組別投票限制資訊
     *
     * @param  string $voteID
     * @return static[]
     */
    public function getQuestionsInfo($voteID, $round=null)
    {
        $result = self::find()
            ->where(['voteID' => $voteID])
            ->andfilterWhere(['round' => $round])
            ->all();

        return $result;
    }

    /**
     * 獲取對應組別投票限制資訊
     *
     * @param  string $voteID
     * @param  string $party
     * @param  string $questionID
     * @return array
     */
    public function getQuestionNumBallots($questionID = null)
    {
        $tmpDataAry = $this->getQuestionInfo($questionID)->attributes;
        if(is_null($tmpDataAry))
            return $tmpDataAry;
        return [
            'mostNum' => $tmpDataAry['numBallots'], // 可投票數
            'leastNum' => $tmpDataAry['leastNumBallots'], // 最少應投票數
        ];
    }

    /**
     * 獲取特定組別投票限制資訊
     *
     * @param  string $voteID
     * @return static[]
     */
    public function getPartyQuestionsInfo($voteID, $party, $allParty = false)
    {
        $round = (new FormVotes())->getVoteInfo($voteID)->round;
        $query = self::find()->where(['voteID' => $voteID, 'round' => $round]);
        if ($allParty) {
            $query->andWhere(['in', 'party', [$party, self::ALL_PARTY_CODE]]);
        }
        else {
            $query->andWhere(['=', 'party', $party]);
        }
        return $query->asArray()->all();
    }
   
    /**
     * 獲取全部組別投票限制資訊
     *
     * @param  string $voteID
     * @return array
     */
    public function getQuestionsNumBallots($voteID)
    {
        $tmpDataAry = $this->getQuestionsInfo($voteID);
        if(is_null($tmpDataAry))
            return $tmpDataAry;
        $result = ArrayHelper::index($tmpDataAry, 'party');
        return $result;
    }

    /**
     * 取得問題列表
     */
    public function getQuestionListWithVoteID($voteID)
    {
        return self::find()->where([
            'voteID' => $voteID,
        ]);
    }

    /**
     * 取得輪次問題列表
     */
    public function getRoundQuestions($voteID, $round)
    {
        return $this->getQuestionListWithVoteID($voteID)
            ->andWhere(['round' => $round]);
    }
    
    /**
     * 取得投票輪次所有問題ID
     *
     * @param  string $voteID
     * @param  mixed $round
     * @return object
     */
    public function getQuestionsIdList($voteID, $round)
    {
        $questions = $this->getRoundQuestions($voteID, $round)->select(['questionID'])->asArray()->all();
        $questionIds = ArrayHelper::index($questions, 'questionID');
        return array_keys($questionIds);
    }

    /**
     * 新增問題
     *
     * @param  array|mixed $post
     * @return bool
     */
    public function createQuestion($post)
    {
        $this->scenario = 'create';
        $this->load($post);
        $formData = ArrayHelper::getValue($post, $this->formName());

        // 資料驗證
        if(!$this->save()) 
        {
            foreach($this->errors as $message)
            {
                Yii::$app->session->addFlash('error', $message[0]);
            }
            Logs::add(Logs::QUESTION_CREATE_FAIL, Json::encode(compact('voteID')+$this->errors, 336));
            return false;
        }

        Logs::add(Logs::QUESTION_CREATE, Json::encode($formData, 336));
        return true;
    }

    /**
     * 一次建立多個問題
     *
     * @param  string $voteID
     * @param  array $post
     * @param  bool $newVote 是否為建立投票時同步新增所有組別的問題
     * @return void
     */
    public function createQuestions($voteID, $post, $newVote=true)
    {
        // 基本資料
        $FormVotes = new FormVotes;
        // 投票資訊
        $voteInfo = $FormVotes->getVoteInfo($voteID);
        //輪次
        $round = $voteInfo->round;
        // 投票組別
        $FormParties = new FormParties;
        
        $transaction = Questions::getDb()->beginTransaction();
        try {
            // 投票建立時一併產生預設問題
            if ($newVote) {
                foreach($post[$FormParties->formName()] as $party => $partyDetail)
                {
                    $question = new self;
                    $question->scenario = 'create';
                    $question->voteID = $voteID;
                    $question->round = $round;
                    $question->party = (string) $party;
                    $question->title = $partyDetail['name'];
                    $question->titleE = $partyDetail['nameE'];
                    $question->numBallots = $partyDetail['numBallots'];
                    $question->leastNumBallots = $partyDetail['leastNumBallots'];
                    $question->maxElect = $partyDetail['maxElect'];
                    $question->numOfKeep = $partyDetail['numOfKeep'];
                    $question->numFemaleKeep = ($voteInfo->addiCondition == 'female') ? $partyDetail['numFemaleKeep'] : NULL;
                    if (!$question->save()) {
                        foreach($question->errors as $message)
                        {
                            Logs::add(Logs::QUESTION_CREATE_FAIL, Json::encode(compact('voteID')+[$message[0]], 336));
                        }
                    }
                }
            }
            // 
            else {
                $parties = ArrayHelper::index($FormParties->getPartyAll($voteID), function ($party) {
                    return $party->party;
                });
                $postData = $post[$this->formName()];

                if (empty($postData['party'])) {
                    Yii::$app->session->addFlash('error', "組別不能為空");
                    Logs::add(Logs::QUESTION_CREATE_FAIL, Json::encode(compact('voteID')+[
                        'message' => '組別不能為空'
                    ], 336));
                    $transaction->rollBack();
                    return false;
                }

                foreach($postData['party'] as $party)
                {
                    $question = new self;
                    $question->scenario = 'create';
                    $question->load($post);
                    $question->round = $round;
                    $question->party = (string) $party;
                    $question->numFemaleKeep = ($voteInfo->addiCondition == 'female') ? $postData['numFemaleKeep'] : NULL;
                    if (!$question->save()) {
                        foreach($question->errors as $message)
                        {
                            Yii::$app->session->addFlash('error', $message[0]);
                            Logs::add(Logs::QUESTION_CREATE_FAIL, Json::encode(compact('voteID')+[$message[0]], 336));
                        }
                        $transaction->rollBack();
                        return false;
                    }
                }
                Logs::add(Logs::QUESTION_CREATE, Json::encode($postData, 336));
            }
            $transaction->commit();// 批量新增資料
        } catch(\Exception $e) {
            $transaction->rollBack();
            Logs::add(Logs::QUESTION_CREATE_FAIL, Json::encode(compact('voteID')+[$e->getMessage()], 336));
            Yii::$app->session->addFlash('error', "問題新增失敗");
            return false;
        }
        return true;
    }
    
    /**
     * 匯入輪次問題
     *
     * @param  string $voteID
     * @param  array $post
     * @return void
     */
    public function importQuestions($voteID, $post)
    {
        $models = Model::createMultiple(self::classname(), [], 'questionID');
        foreach ($models as $model) {
            $model->scenario = 'create';
        }
        Model::loadMultiple($models, $post);
        $transaction = \Yii::$app->db->beginTransaction();
        try
        {
            foreach ($models as $model)
            {
                $oldAttributes = $model->attributes;
                $model->questionID = NULL;
                if (!($flag = $model->save()))
                {
                    foreach($model->errors as $message)
                    {
                        Yii::$app->session->addFlash('error', $message[0]);
                        Logs::add(Logs::QUESTION_IMPORT_FAIL, Json::encode(compact('voteID')+[$message[0]], 336));
                    }
                    $transaction->rollBack();
                    break;
                }
                else { // 建立候選人配置
                    $candiConfig = CandiConfig::findOne(['voteID' => $voteID, 'questionID' => $oldAttributes['questionID']]);
                    if (!empty($candiConfig)) {
                        $newCandiConfig = new CandiConfig;
                        $newCandiConfig->setAttributes($candiConfig->attributes);
                        $newCandiConfig->questionID = $model->questionID;
                        $newCandiConfig->candiConfig = NULL;
                        $newCandiConfig->save(false);
                    }
                }
            }
            if ($flag)
            {
                // 完成現職新增
                $transaction->commit();
                Logs::add(Logs::QUESTION_IMPORT, Json::encode(compact('voteID'), 336));
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
        }
    }
    
    /**
     * 更新問題
     *
     * @param  int $questionID
     * @param  array|mixed $post
     * @return bool
     */
    public function updateQuestion($questionID, $post)
    {
        $question = self::findOne(['questionID' => $questionID]);
        $question->scenario = 'update';
        $question->load($post);
        $question->description = $question->description;
        $question->descriptionE = $question->descriptionE;
        $voteID = $question->voteID;
        $oldAttributes = $question->oldAttributes;

        if(!$question->save()) // 資料驗證
        {
            foreach($question->errors as $message)
            {
                Yii::$app->session->addFlash('error', $message[0]);
            }
            Logs::add(Logs::QUESTION_EDIT_FAIL, Json::encode(compact('voteID')+$question->errors, 336));
            return false;
        }

        //比較差異
        $attributesDiff = ArrayHelper::getAttributesMigration($question->getAttributes(), $oldAttributes);
        // LOG紀錄: 修改儲存差異
        if (!empty($attributesDiff)) {
            Logs::add(Logs::QUESTION_EDIT, Json::encode(compact('voteID')+$attributesDiff, 336));
        }
        
        return true;
    }

    /**
     * 刪除問題
     *
     * @param int $groupId 群組名稱
     * @param array|mixed $post 表單資料
     *
     * @return bool
     */
    public function deleteQuestion($questionID)
    {
        $question = self::findOne(['questionID' => $questionID]);
        $voteID = $question['voteID'];
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            if(!$question->delete()) // 資料驗證
            {
                foreach($question->errors as $message)
                {
                    Yii::$app->session->addFlash('error', $message[0]);
                }
                $transaction->rollBack();
                Logs::add(Logs::QUESTION_DELETE_FAIL, Json::encode(compact('voteID')+$question->errors, 336));
                return false;
            }
            else {
                // 如果候選配置存在，則刪除
                $candiConfig = CandiConfig::findOne(['voteID' => $voteID, 'questionID' => $questionID]);
                if (!empty($candiConfig)) {
                    $candiConfig->delete();
                }
                Logs::add(Logs::QUESTION_DELETE, Json::encode($question, 336));
                $transaction->commit();
                return true;
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error(
                \yii\helpers\VarDumper::dumpAsString(
                    $e->getMessage(), $depth=10, $highlight=false
                ),
                __METHOD__
            );
        }
    }

    /**
     * 刪除特定投票全部問題
     *
     * @param int $voteID 投票場次
     *
     * @return bool
     */
    public function deleteQuestionAll($voteID)
    {
        self::deleteAll(['voteID' => $voteID]);
        Logs::add(Logs::QUESTION_DELETE_ALL, Json::encode($voteID, 336));
        return true;
    }
        
    /**
     * 取得問題限制文字
     *
     * @param  string $prefix 前綴
     * @param  string $suffix 後綴
     * @param  int $candiCount 候選人數
     * @param  object|array $question 問題資訊
     * @param  string $lowerLimitUnit 下限單位
     * @param  string $upperLimitUnit 上限單位
     * @param  bool $invalidText 是否顯示廢票文字
     * @return string
     */
    public static function getQuestionLimitText($prefix, $suffix, $candiCount, $question, $lowerLimitUnit, $upperLimitUnit, $invalidText=false)
    {
        // 自定義投票規則文字
        if (strtolower(Yii::$app->language) == 'zh-tw' && !empty($question['ruleText'])) {
            return (new Votes())->replaceQuestionRule($question['ruleText'], [$question['questionID'] => $question]);
        }
        elseif (strtolower(Yii::$app->language) == 'en-us' && !empty($question['ruleTextE'])) {
            return (new Votes())->replaceQuestionRule($question['ruleTextE'], [$question['questionID'] => $question]);
        }
        
        // 用投票規則自動產生文字
        if ($question['leastNumBallots'] == 0 && $question['numBallots'] >= $candiCount) {
            $ballotLimitText = Yii::t('app', "{$prefix}圈選名額無限制{$suffix}");
        }
        elseif ($question['leastNumBallots'] == 0 && $question['numBallots'] < $candiCount) {
            $ballotLimitText = Yii::t('app', "{$prefix}圈選名額不得超過 {mostNum} {unit}{$suffix}", [
                'mostNum' => $question['numBallots'],
                'unit' => $upperLimitUnit,
            ]);
            $invalidText ? $ballotLimitText .= Yii::t('app', "，超過上限者視為廢票。") : '';
        }
        elseif ($question['numBallots'] >= $candiCount) {
            $ballotLimitText = Yii::t('app', "{$prefix}圈選名額不得少於 {leastNum} {unit}{$suffix}", [
                'leastNum' => $question['leastNumBallots'],
                'unit' => $lowerLimitUnit,
            ]);
            $invalidText ? $ballotLimitText .= Yii::t('app', "，不足者視為廢票。") : '';
        }
        else {
            $ballotLimitText = Yii::t('app', "{$prefix}圈選名額不得少於 {leastNum} {lowerUnit}，亦不得多於 {mostNum} {upperUnit}{$suffix}", [
                'leastNum' => $question['leastNumBallots'],
                'mostNum' => $question['numBallots'],
                'lowerUnit' => $lowerLimitUnit,
                'upperUnit' => $upperLimitUnit,
            ]);
            $invalidText ? $ballotLimitText .= Yii::t('app', "，不足下限或超過上限者視為廢票。") : '';
        }

        return $ballotLimitText;
    }
}
