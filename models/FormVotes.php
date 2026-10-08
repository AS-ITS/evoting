<?php
namespace app\models;

use Yii;
use yii\helpers\Json;
use app\components\Model;
use app\components\helper\ArrayHelper;

class FormVotes extends Votes
{
    public $process = null;

    /**
     * Declaring Rules
     * 
     * @link $Scenarios https://www.yiiframework.com/doc/guide/2.0/en/structure-models#scenarios
     * @link rules() https://www.yiiframework.com/doc/guide/2.0/en/input-validation#declaring-rules
     */
    public function rules()
    {
        return [
            // Scenario: Global
            [['voteID', 'session', 'creator'], 'string', 'max' => 20],
            [['groupId', 'round'], 'integer'],
            ['round', 'default', 'value' => 1],

            // Scenario: Search
            [[
                'voteID', 'Name', 'NameE', 'openStart', 'openEnd', 'process',
                'verifyStart', 'verifyEnd', 'type', 'partyOrNot', 'active', 'isFinish',
            ], 'safe', 'on'=>'search'],
            
            // Scenario: Update
            [[
                'voteID', 'creator', 'Name',
                'openStart', 'openEnd', 'verifyStart', 'verifyEnd',
                'partyOrNot', 'addiCondition', 'isByParty', 'isBindVote',
                'active', 'isFinish', 'finishPage', 'authBeforeDetail', 'skipDetail', 
                'isShow', 'candiConfig', 'skipCheck'
            ], 'required', 'on' => ['update']],
            
            // Scenario: Create
            [[
                'creator', 'Name', 'type', 'hosted',
                'openStart', 'openEnd', 'verifyStart', 'verifyEnd',
                'partyOrNot', 'addiCondition', 'isByParty', 'isBindVote',
                'active', 'authBeforeDetail', 'skipDetail', 'isShow', 'candiConfig', 'skipCheck'
            ], 'required', 'on' => ['create']],

            // Scenario: Create、Update
            [[  'candComment', 'candCommentE',
                'notice', 'noticeE',
                'information', 'informationE',
                'otherInfoTitle', 'otherInfoTitleE', 
                'otherInfo', 'otherInfoE',
            ], 'string', 'on' => ['create','update']],
            [[
                'type', 'partyOrNot', 'skipCheck',
                'isByParty', 'isBindVote', 'active', 'isFinish', 'finishPage', 'authBeforeDetail', 'skipDetail', 'isShow'
            ], 'string', 'max' => 1, 'on'=>['create', 'update']],
            ['type', 'in', 'range' => [self::TYPE_NO_AUTH, self::TYPE_ANON], 'on' => ['create', 'update']],
            [['pattern', 'loginLayout'], 'string', 'max' => 10, 'on'=>['create', 'update']],
            [['themeColor'], 'string', 'max' => 30, 'on'=>['create', 'update']],
            [['contact', 'contactE', 'hosted', 'hostedE', 'email'], 'string', 'max' => 64, 'on'=>['create','update']],
            [['bindWhichVote'], 'string', 'max' => 128, 'on'=>['create','update']],
            [['shortUrl'], 'string', 'max' => 8, 'min' => 8, 'on' => ['create']],
            [['shortUrl'], 'string', 'max' => 8, 'min' => 1, 'on' => ['update']],
            [['Name', 'NameE', 'addiCondition', 'tel'], 'string', 'max' => 255, 'on'=>['create','update']],
            [['openStart', 'openEnd', 'verifyStart', 'verifyEnd', 'sort'], 'safe', 'on'=>['create','update']],
            [[
                'candComment', 'candCommentE', 'notice', 'noticeE',
                'otherInfoTitle', 'otherInfoTitleE', 'otherInfo', 'otherInfoE'], 'filter', 'filter' => function ($value) {
                    return \yii\helpers\HtmlPurifier::process($value, Yii::$app->params['HtmlPurifier.config']);
                }, 'on'=>['create','update']],
            // 與其他 HTML 欄位一致，統一使用 HtmlPurifier 過濾（防止儲存型 XSS）
            [['information', 'informationE'], 'filter', 'filter' => function ($value) {
                return \yii\helpers\HtmlPurifier::process($value, Yii::$app->params['HtmlPurifier.config']);
            }, 'on'=>['create','update']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'type' => '投票類型',
            'contact' => '聯絡人',
            'contactE' => '聯絡人(英)',
            'creator' => '投票創建者',
            'voteID' => '投票編號',
            'candComment' => '候選名單備註',
            'candCommentE' => '候選名單備註(英)',
            'Name' => '投票名稱',
            'NameE' => '投票名稱(英)',
            'openStart' => '投票時間(起)',
            'openEnd' => '投票時間(迄)',
            'verifyStart' => '驗證時間(起)',
            'verifyEnd' => '驗證時間(迄)',
            'partyOrNot' => '是否分組',
            'addiCondition' => '保留名額',
            'hosted' => '主辦單位',
            'hostedE' => '主辦單位(英)',
            'tel' => '聯絡電話',
            'email' => '聯絡信箱',
            'isByParty' => '投票結果顯示',// 0)否 1)是
            'isBindVote' => '是否共用投票密碼',
            'bindWhichVote' => '共用密碼之投票',
            'notice' => '投票要點',
            'noticeE' => '投票要點(英)',
            'information' => '圈選須知',
            'informationE' => '圈選須知(英)',
            'otherInfoTitle' => '自定義資訊標題',
            'otherInfoTitleE' => '自定義資訊英文標題',
            'otherInfo' => '自定義資訊',
            'otherInfoE' => '自定義資訊英文',
            'active' => '投票狀態',
            'isFinish' => '是否完成投票',
            'finishPage' => '投票完成跳轉頁面',
            'pattern' => '投票樣版',
            'loginLayout' => '投票密碼登入樣板',
            'themeColor' => '主題色',
            'sort' => '投票列表排序',
            'session' => '場次碼',
            'shortUrl' => '短網址編號',
            'groupId' => '群組',
            'authBeforeDetail' => '查看投票資訊是否驗證',
            'skipDetail' => '是否略過投票資訊頁面',
            'skipCheck' => '是否略過圈選結果頁',
            'isShow' => '是否於首頁顯示',
            'round' => '輪次',
            'candiConfig' => '候選名單配置',
        ];
    }

    /**
     * 投票基本資訊
     */
    public function getVoteInfo($voteID, $useResult = false)
    {
        $query = parent::getVoteInfo($voteID);

        if($useResult) $query->joinWith('resultsConfig');

        $result = $query->one();

        if(is_null($result))
            return null;

        return $result;
    }

    /**
     * 取得投票分組的問題
     */
    public function getVotePartyQuestion($voteID, $round, $party, $lang = 'zh-tw')
    {
        switch (strtolower($lang)) {
            case 'zh-tw':
                $title = 'title';
                break;
            case 'en-us':
                $title = 'titleE';
                break;
            default:
                $title = 'title';
                break;
        }
        $questions = Questions::find()->select(['questionID', $title])
            ->where(['voteID' => $voteID, 'round' => $round])
            ->andWhere(['in', 'party', [$party, Questions::ALL_PARTY_CODE]])
            ->asArray()
            ->all();
        $result = ArrayHelper::map($questions, 'questionID', $title);
        return $result;
    }

    /**
     * 是否可以投票
     */
    public function isVoteOpen($voteID)
    {
        $q = parent::getVoteInfo($voteID);
        $query = clone $q;
        if(is_null($query->one())) return null;
        $q->andWhere(['in', 'active', [self::STATUS_ACTIVE, self::STATUS_BACKFILL]]);
        $q->andWhere(['>=', 'openEnd', date('Y-m-d H:i')]);
        $q->andWhere(['<=', 'openStart', date('Y-m-d H:i')]);
        return !is_null(
            $q->one()
        );
    }

    /**
     * 投票列表
     */
    public function getVoteList($item, $params = [])
    {
        $identity = Yii::$app->user->identity;

        switch($item)
        {
            case 'open': // 主頁列表
                $query = parent::getOpenVote();
                $query->andWhere(['in', 'type', [self::TYPE_ANON]]);
                $query->andWhere(['=', 'isShow', '1']);
                $query->andWhere(['>=', 'verifyEnd', date('Y-m-d H:i')]);
                $query->orderBy('sort DESC');
                break;
            case 'result': // 投票結果列表
                // 資料表名稱
                $ptn = parent::tableName();
                $rctn = ResultsConfig::tableName();

                // 條件
                $query = parent::getAllVote();
                $query->joinWith('resultsConfig');
                $query->andWhere(['in', "$ptn.type", [self::TYPE_ANON]]);
                $query->andWhere(['<=', "$ptn.verifyEnd", date('Y-m-d H:i')]);
                $query->andWhere(['=', "$rctn.isShow", '1']);
                // 必須投票截止才顯示
                $query->andWhere(["$ptn.active" => 2]);
                break;
            case 'group': // 群組投票管理列表
                $query = parent::getAllVote();
                $query->andWhere(['in', 'type', [self::TYPE_NO_AUTH, self::TYPE_ANON]]);
                $query->andWhere(['=', 'groupId', Yii::$app->request->get('groupId')]);
                $query->orderBy('sort DESC');
                break;
            case 'bind': // 可以共用密碼的清單: 匿名投票及無共用密碼
                $query = parent::getAllVote();
                $query->andWhere(['isBindVote' => '0', 'type' => self::TYPE_ANON]);
                if(!$identity->inUserRole('sa')) {
                    $query->andWhere(['creator' => Yii::$app->user->identity->getId()]);
                    if (!empty(Yii::$app->requestedParams['voteID'])) {
                        $voteInfo = parent::getVoteInfo(Yii::$app->requestedParams['voteID'])->one();
                        $query->orWhere(['voteID' => $voteInfo->bindWhichVote]);
                    }
                }
                $query->orderBy('sort DESC');
                break;
            case 'ballotWork':
                $query = parent::getAllVote();
                if (($identity->inUserRole('ga') || $identity->inUserRole('gm'))) {
                    $userGroups = GroupMember::find()->select('groupId')->distinct()->where(['cn' => Yii::$app->user->id])->indexBy('groupId')->asArray()->all();
                    $userGroups = array_keys($userGroups);
                    $query->andWhere(['in', 'groupId', $userGroups]);
                }
                elseif ($identity->inUserRole('va')) {
                    $query->andWhere(['creator' => Yii::$app->user->identity->getId()]);
                }
                $query->orderBy('sort DESC');
                break;
            case 'all':
                $query = parent::getAllVote();

                if(!$identity->inUserRole('sa')) {
                    $query->andWhere(['creator' => Yii::$app->user->identity->getId()]);
                }
                $query->orderBy('sort DESC');
                break;
        }

        if (!empty($params)) {
            $params = Model::trimParams($params, $this->className());
        }
        $this->load($params);

        if (!$this->validate()) {
            return $query;
        }

        $query->andFilterWhere([
            'creator' => $this->creator,
            'type' => $this->type
        ]);

        // $toDate = date('Y-m-d H:i:s');
        // switch($this->process)
        // {
        //     case '0': // 狀態中止
        //         $this->active = self::STATUS_TERMINATE;
        //         break;
        //     case '1': // 等待投票
        //         $this->active = self::STATUS_READY;
        //         $query->andFilterWhere(['>', 'openStart', $toDate]);
        //         break;
        //     case '2': // 開始投票
        //         $this->active = self::STATUS_ACTIVE;
        //         $query->andFilterWhere(['<=', 'openStart', $toDate])
        //             ->andFilterWhere(['>', 'openEnd', $toDate]);
        //         break;
        //     case '3': // 等待驗證
        //         $this->active = self::STATUS_ACTIVE;
        //         $query->andFilterWhere(['<=', 'openEnd', $toDate])
        //             ->andFilterWhere(['>', 'verifyStart', $toDate]);
        //         break;
        //     case '4': // 開始驗證
        //         $this->active = self::STATUS_ACTIVE;
        //         $query->andFilterWhere(['<=', 'verifyStart', $toDate])
        //             ->andFilterWhere(['>', 'verifyEnd', $toDate]);
        //         break;
        //     case '5': // 等待開票
        //         $this->active = self::STATUS_ACTIVE;
        //         $query->andFilterWhere(['<=', 'verifyEnd', $toDate]);
        //         break;
        //     case '6': // 完成投票
        //         $this->isFinish = '1';
        //         $this->active = self::STATUS_TERMINATE;
        //         $query->andFilterWhere(['<=', 'verifyEnd', $toDate]);
        //         break;
        // }

        $query->andFilterWhere(['like', 'voteID', $this->voteID])
            ->andFilterWhere(['like', 'Name', $this->Name])
            ->andFilterWhere(['like', 'NameE', $this->NameE])
            ->andFilterWhere(['like', 'partyOrNot', $this->partyOrNot])
            ->andFilterWhere(['like', 'active', $this->active])
            ->andFilterWhere(['like', 'isFinish', $this->isFinish]);

        return $query;
    }

    /**
     * 投票列表
     * 
     * @see ArrayHelper::getColumn https://www.yiiframework.com/doc/guide/2.0/en/helper-array#retrieving-columns
     * @see ArrayHelper::map https://www.yiiframework.com/doc/guide/2.0/en/helper-array#building-maps
     */
    public function getCnByVote($query)
    {
        $q = clone $query;
        $sysId = ArrayHelper::getColumn($q->select('DISTINCT `creator` as `creator`')->all(), 'creator');
        $users = Users::find()->where(['cn' => $sysId])->all();
        return ArrayHelper::map($users, 'cn', 'name');
    }

    /**
     * 取得所有投票列表
     * 
     * @see ArrayHelper::getColumn https://www.yiiframework.com/doc/guide/2.0/en/helper-array#retrieving-columns
     * @see ArrayHelper::map https://www.yiiframework.com/doc/guide/2.0/en/helper-array#building-maps
     */
    public function getVoteAllAry()
    {
        return ArrayHelper::map(
            $this->getVoteList('all')->all(), 'voteID', 'Name'
        );
    }
    
    /**
     * 取得所有可以共用密碼的投票列表
     *
     * @return array
     */
    public function getCanBindVoteAry()
    {
        return ArrayHelper::map(
            $this->getVoteList('bind')->all(), 'voteID', 'Name'
        );
    }

    /**
     * 投票創辦人列表
     * 
     * @see ArrayHelper::getColumn https://www.yiiframework.com/doc/guide/2.0/en/helper-array#retrieving-columns
     * @see ArrayHelper::map https://www.yiiframework.com/doc/guide/2.0/en/helper-array#building-maps
     */
    public function getVoteCreatorAry($creator = null)
    {
        $users = Users::find()->select(['cn', 'name'])->asArray()->all();
        return ArrayHelper::map($users, 'cn', 'name');
    }

    /**
     * 建立新投票的model
     */
    public function createVote()
    {
        $model = new self;
        $model->setAttributes([
            'creator'  => Yii::$app->user->identity->getId(),
            'type' => self::TYPE_ANON,
        ], false);
        return $model;
    }

    /**
     * 更新投票
     * 
     * @see yii\base\Model::setScenario https://www.yiiframework.com/doc/api/2.0/yii-base-model#setScenario()-detail
     */
    public function updateVoteInfo($voteID, $post, $saveAs = false)
    {
        $this->setScenario($saveAs ? 'create' : 'update');// 設置模型的方案
        $this->load($post);

        if($saveAs)
        {
            // 另存
            $upd = new parent;
            $this->setAttributes([
                'voteID' => $voteID,
                'creator'  => Yii::$app->user->identity->getId(),
                'isFinish' => '0',
                'sort' => $upd->newSort(),
                'shortUrl' => $this->genShortUrl(),
            ], false);
        }
        else
        {
            // 修改
            $upd = parent::getOneVote($this->voteID);
            $shortUrl = Yii::$app->user->can('sa') ? $this->shortUrl : $upd->shortUrl;
            $this->setAttributes([
                'voteID' => $voteID,
                'isFinish' => $upd->isFinish == '' ? '0' : $upd->isFinish,
                'sort' => !empty($post[$this->formName()]['sort']) ? $post[$this->formName()]['sort'] : $upd->newSort(),
                'shortUrl' => $upd->shortUrl != '' ? $shortUrl : $this->genShortUrl(),
            ], false);
        }

        // 檢查共用密碼投票資格
        if ($this->isBindVote) {
            $bindVoteInfo = $this->getVoteInfo($this->bindWhichVote);
            $bindVotePartiesCount = Parties::find()->where(['voteID' => $bindVoteInfo->voteID])->count();
            $FormParties = new FormParties;
            $voteParties = ArrayHelper::getValue($post, $FormParties->formName());
            if (is_null($bindVoteInfo)) {
                // LOG紀錄: 驗證失敗
                Logs::add(
                    $saveAs ? Logs::VOTE_CREATE_FAIL : Logs::VOTE_DETAIL_EDIT_FAIL
                    , Json::encode(compact('voteID')+['message' => '共用密碼失敗，查無共用密碼投票場次'], 336)
                );
                Yii::$app->session->addFlash('error', '共用密碼失敗，查無共用密碼投票場次');
                return false;
            }
            elseif ($bindVoteInfo->partyOrNot == '0' && $this->partyOrNot == '1') {
                // LOG紀錄: 驗證失敗
                Logs::add(
                    $saveAs ? Logs::VOTE_CREATE_FAIL : Logs::VOTE_DETAIL_EDIT_FAIL
                    , Json::encode(compact('voteID')+['message' => '共用密碼失敗，分組投票無法共用不分組投票的密碼'], 336)
                );
                Yii::$app->session->addFlash('error', '共用密碼失敗，分組投票無法共用不分組投票的密碼');
                return false;
            }
            elseif ($this->partyOrNot == '1' && $bindVotePartiesCount != count($voteParties)) {
                // LOG紀錄: 驗證失敗
                Logs::add(
                    $saveAs ? Logs::VOTE_CREATE_FAIL : Logs::VOTE_DETAIL_EDIT_FAIL
                    , Json::encode(compact('voteID')+['message' => '共用密碼失敗，分組數量及順序必須與共用密碼的投票相同'], 336)
                );
                Yii::$app->session->addFlash('error', '共用密碼失敗，分組數量及順序必須與共用密碼的投票相同，建議使用另存投票');
                return false;
            }
        }
        
        if(!$this->validate()) // 資料驗證
        {
            $status = false;
            $session = Yii::$app->session;
            foreach($this->errors as $message)
            {
                $session->addFlash('error', $message[0]);
            }
            // LOG紀錄: 驗證失敗
            Logs::add(
                $saveAs ? Logs::VOTE_CREATE_FAIL : Logs::VOTE_DETAIL_EDIT_FAIL
                , Json::encode(compact('voteID')+$this->errors, 336)
            );
            return $status;
        }

        $upd->setAttributes($this->attributes, false);
        
        $oldAttributes = $upd->oldAttributes;

        if(!$upd->save() && count($upd->errors) > 0)
        {
            $session = Yii::$app->session;
            foreach($upd->errors as $message)
            {
                $session->addFlash('error', $message);
            }
            return false;
        }
        // 另存新增預設問題
        if ($saveAs) {
            (new Questions())->createQuestions($voteID, $post, true);
        }

        $attributeDiff = [];
        // 比較更新前即更新後差異
        if (!$saveAs) {
            $compare = $upd->getAttributes();
            $compare['openStart'] = date('Y-m-d H:i:s', strtotime($compare['openStart']));
            $compare['openEnd'] = date('Y-m-d H:i:s', strtotime($compare['openEnd']));
            $compare['verifyStart'] = date('Y-m-d H:i:s', strtotime($compare['verifyStart']));
            $compare['verifyEnd'] = date('Y-m-d H:i:s', strtotime($compare['verifyEnd']));
            $attributeDiff = ArrayHelper::getAttributesMigration($compare, $oldAttributes);
            // LOG紀錄: 修改儲存差異，另存當作新增
            if (!empty($attributeDiff)) {
                Logs::add(Logs::VOTE_DETAIL_EDIT, Json::encode(compact('voteID')+$attributeDiff, 336));
            }
        }
        return true;
    }

    /**
     * 刪除投票
     */
    public function deleteVote($voteID)
    {
        $vote = parent::getOneVote($voteID);
        return $vote->delete();
    }

    /**
     * 建立新的 voteId
     */
    public function genVoteId($quantity = 1, $length = 6, $type = 'mixLower')
    {
        $Passwd = new Passwd;
        do {
            $newVoteId = $Passwd->genShuffleStr($quantity, $length, $type);
        } while ( !is_null(parent::getVoteInfo($newVoteId)->one()) );
        return $newVoteId;
    }

    /**
     * 建立新的短網址
     */
    public function genShortUrl($quantity = 1, $length = 8, $type = Passwd::TYPE_MIX_EXCL)
    {
        $Passwd = new Passwd;
        do {
            $newShortUrl = $Passwd->genShuffleStr($quantity, $length, $type);
        } while (!is_null(parent::find()->where(['shortUrl' => $newShortUrl])->one()));
        
        return $newShortUrl;
    }
    
    /**
     * 取得共用密碼的投票
     *
     * @param  mixed $voteID
     * @return void
     */
    public function getBindVotes($voteID)
    {
        $bindVotes = parent::find()
            ->select(['voteID', 'Name'])
            ->where(['isBindVote' => 1, 'bindWhichVote' => $voteID])
            ->asArray()
            ->all();

        return ArrayHelper::map($bindVotes, 'voteID', 'Name');
    }
}
