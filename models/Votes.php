<?php

namespace app\models;

use Yii;
use yii\bootstrap5\Html;
use app\components\Model;
use app\components\helper\ArrayHelper;

/**
 * This is the model class for table "votes".
 *
 * @property string $contact
 * @property string $contactE
 * @property int $creator
 * @property string $voteID
 * @property string $candComment
 * @property string $candCommentE
 * @property string $Name
 * @property string $NameE
 * @property string $openStart 投票開放時間(起)
 * @property string $openEnd 投票開放時間(迄)
 * @property string $verifyStart 驗證時間(起)
 * @property string $verifyEnd 驗證時間(迄)
 * @property string $type 投票類型
 * @property string $partyOrNot 是否分組
 * @property string $addiCondition 當選保留條件
 * @property string $hosted
 * @property string $hostedE
 * @property string|null $tel
 * @property string|null $email
 * @property string $isByParty 是否依組別看選票 0)否 1)是
 * @property string $isBindVote
 * @property string $bindWhichVote
 * @property string $notice
 * @property string $noticeE
 * @property string $information
 * @property string $informationE
 * @property string $otherInfoTitle
 * @property string $otherInfoTitleE
 * @property string $otherInfo
 * @property string $otherInfoE
 * @property string $active
 * @property string $isFinish
 * @property string $finishPage
 * @property string $pattern
 * @property string $loginLayout 投票密碼登入樣板
 * @property string $themeColor 主題色
 * @property int $sort
 * @property string $session
 * @property string $shortUrl
 * @property int $groupId
 * @property string $authBeforeDetail
 * @property string $isShow
 * @property int $round
 * @property string $candiConfig
 */
class Votes extends \yii\db\ActiveRecord
{
    /** 投票類型-表決投票 */
    const TYPE_NO_AUTH = '0';
    /** 投票類型-匿名投票 */
    const TYPE_ANON = '1';

    /** 投票狀態-等待投票 */
    const STATUS_READY = '0';
    /** 投票狀態-開始投票 */
    const STATUS_ACTIVE = '1';
    /** 投票狀態-截止投票 */
    const STATUS_TERMINATE = '2';
    /** 投票狀態-補登投票 */
    const STATUS_BACKFILL = '3';

    /** 候選人配置-依問題個別設定 */
    const CANDI_CONFIG_BY_Q = '2';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'votes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'creator', 'voteID', 'Name', 'type', 'openStart', 'openEnd', 'verifyStart', 'verifyEnd', 
                'partyOrNot', 'addiCondition', 'hosted', 'isByParty', 'isBindVote', 'active', 'isFinish', 
                'finishPage', 'pattern', 'authBeforeDetail', 'isShow'
            ], 'required'],
            [['sort', 'groupId', 'round'], 'integer'],
            [['candComment', 'candCommentE', 'notice', 'noticeE', 'information', 'informationE', 'otherInfoTitle', 'otherInfoTitleE', 'otherInfo', 'otherInfoE'], 'string'],
            [['openStart', 'openEnd', 'verifyStart', 'verifyEnd'], 'safe'],
            [['contact', 'contactE', 'hosted', 'hostedE', 'email'], 'string', 'max' => 64],
            [['voteID', 'session', 'creator'], 'string', 'max' => 20],
            [['themeColor'], 'string', 'max' => 30],
            [['partyOrNot', 'type', 'isByParty', 'isBindVote', 'active', 'isFinish', 'finishPage', 'candiConfig'], 'string', 'max' => 1],
            [['Name', 'NameE', 'addiCondition', 'tel'], 'string', 'max' => 255],
            [['bindWhichVote'], 'string', 'max' => 128],
            [['pattern', 'loginLayout'], 'string', 'max' => 10],
            [['shortUrl'], 'string', 'max' => 8],
            [['voteID', 'shortUrl'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'voteID' => 'Vote ID',

            'Name' => '投票名稱',
            'NameE' => '投票名稱(英)',
            'creator' => '投票建立者',

            'openStart' => '投票開放時間(起)',
            'openEnd' => '投票開放時間(迄)',
            'verifyStart' => '驗證時間(起)',
            'verifyEnd' => '驗證時間(迄)',

            'type' => '投票類型',
            'partyOrNot' => '是否分組',
            'addiCondition' => '當選保留條件',
            'isByParty' => '是否依組別看選票',
            'active' => '投票狀態',
            'isFinish' => '是否完成投票',
            'finishPage' => '投票完成跳轉頁面',
            'pattern' => '投票樣版',
            'loginLayout' => '投票密碼登入樣板',
            'themeColor' => '主題色',
            'showNum' => '投票畫面的序號顯示',
            'sort' => '投票列表排序',
            'session' => '場次碼',
            'shortUrl' => '短網址編號',
            'groupId' => '群組',
            'authBeforeDetail' => '查看投票資訊是否驗證',
            'isShow' => '是否於首頁顯示',
            'round' => '輪次',
            'candiConfig' => '候選名單',
            
            'hosted' => '主辦單位',
            'hostedE' => '主辦單位(英)',
            'contact' => '聯絡人',
            'contactE' => '聯絡人(英)',
            'tel' => '聯絡電話',
            'email' => '聯絡信箱',

            'isBindVote' => '是否共用其他投票之密碼',
            'bindWhichVote' => '共用密碼之投票',
            
            'notice' => '投票要點',
            'noticeE' => '投票要點(英)',
            'information' => '圈選須知',
            'informationE' => '圈選須知(英)',
            'candComment' => '候選人名單備註',
            'candCommentE' => '候選人名單備註(英)',
            'otherInfoTitle' => '自定義資訊標題',
            'otherInfoTitleE' => '自定義資訊英文標題',
            'otherInfo' => '自定義資訊',
            'otherInfoE' => '自定義資訊英文',
        ];
    }

    /**
     * 取得投票結果
     */
    public function getResultsConfig()
    {
        return $this->hasOne(FormResultsConfig::className(), ['voteID' => 'voteID']);
    }

    /**
     * 取得投票組別
     */
    public function getParties()
    {
        return $this->hasMany(Parties::className(), ['voteID' => 'voteID']);
    }

    /**
     * 開放中的投票列表
     */
    public function getOpenVote()
    {
        return self::find()
            ->where([
                'and',
                ['active'=>'1'],
                ['isFinish'=>'0']
            ]);
    }

    /**
     * 所有投票列表
     */
    public function getAllVote()
    {
        return self::find();
    }

    /**
     * 取得單一投票
     */
    public function getOneVote($voteID)
    {
        return self::findOne($voteID);
    }

    /**
     * 投票詳情
     */
    public function getVoteInfo($voteID)
    {
        return self::find()
            ->where([
                self::tableName().'.voteID' => $voteID
            ]);
    }

    /**
     * 新排序號碼
     */
    public function newSort()
    {
        return self::find()->select('max(`sort`) as `sort`')->one()['sort']+1;
    }

    /**
     * 所有輪次資料
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRounds()
    {
        return $this->hasMany(Round::className(), ['voteID' => 'voteID']);
    }

    /**
     * 所有問題
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions()
    {
        return $this->hasMany(Questions::className(), ['voteID' => 'voteID']);
    }

    /**
     * 當前輪次資料
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCurrentRound()
    {
        return $this->hasOne(Round::className(), ['voteID' => 'voteID', 'round' => 'round']);
    }

    /**
     * 取得投票和輪次名稱
     *
     * @return string
     */
    public function getVoteName($encode=false, $custom=false, $lang=false, $showRoundName=true)
    {
        $round = $this->currentRound;
        $voteChName = $custom ? $this->Name.$custom : $this->Name;
        // 特定語系
        if ($lang == 'en-us') {
            $voteName = Model::i18n($this->NameE, $this->NameE, $encode);
            $roundName = $round ? Model::i18n($round->nameE, $round->nameE, $encode) : '';
        }
        elseif ($lang == 'zh-tw') {
            $voteName = Model::i18n($voteChName, $voteChName, $encode);
            $roundName = $round ? Model::i18n($round->name, $round->name, $encode) : '';
        }
        else {
            $voteName = Model::i18n($this->NameE, $voteChName, $encode);
            $roundName = $round ? Model::i18n($round->nameE, $round->name, $encode) : '';
        }
        // 顯示輪次名稱
        if ($showRoundName && $round && $round->showName) {
            return $voteName.$roundName;
        }
        return $voteName;
    }

    /**
     * 取得輪次文字
     *
     * @return string
     */
    public function getRoundBadge()
    {
        return Html::tag('span', "輪次: {$this->round}", ['class' => 'badge badge-dark']);
    }

    /**
     * 取得投票編號文字
     *
     * @return string
     */
    public function getVoteIdBadge()
    {
        return Html::tag('span', "{$this->voteID}", ['class' => 'badge badge-primary']);
    }

    /**
     * 取得投票狀態文字
     *
     * @return string
     */
    public function getStatusBadge()
    {
        return Html::tag('span', "投票狀態: ".Yii::$app->params['ct.activeAry'][$this->active], ['class' => 'badge badge-dark']);
    }

    /**
     * 取得投票問題
     * 
     * [
     *    'questionID' => 'title',
     * ]
     * 
     * @param string $voteID
     * @param string $round
     * @param string $lang
     * @return array
     */
    public function getVoteQuestion($voteID, $round, $lang = 'zh-tw')
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
            ->where(['voteID' => $voteID, 'round' => $round])->asArray()->all();
        $result = ArrayHelper::map($questions, 'questionID', $title);
        return $result;
    }
    
    /**
     * 取得民國年日期
     *
     * @param  string $datetime
     * @return string
     */
    public function getRocDate($datetime)
    {
        // 將datetime轉成date
        $date = date('Y-m-d', strtotime($datetime));
        // 將date轉成array
        $date = explode('-', $date);
        // 將西元年轉成民國年
        $date[0] = $date[0] - 1911;
        // 將array轉成string
        $date = '中華民國'.$date[0].'年'.ltrim($date[1], '0').'月'.ltrim($date[2], '0').'日';

        return $date;
    }
    
    /**
     * 把特定關鍵字替換成問題的投票規則
     *
     * @param  string $text
     * @param  array $questions
     * @return string
     */
    public function replaceQuestionRule($text, $questions=[])
    {
        if (empty($questions)) {
            $questions = Questions::find()->where(['voteID' => $this->voteID])->indexBy('questionID')->asArray()->all();
        }
        $pattern = '/{%(\d+)_(.*?)%}/';

        // 使用 preg_match_all 函數來找出所有符合正規表達式的結果
        preg_match_all($pattern, $text, $matches);

        // 使用 call_user_func_array 函數將 $matches 陣列的結構從多維陣列轉換為一維陣列
        $matches = call_user_func_array('array_map', array_merge([null], $matches));

        // $matches[0] 現在包含所有符合正規表達式的結果
        foreach ($matches as $match) {
            // 從 $questions 陣列中取得對應的值
            if (isset($questions[$match[1]][$match[2]])) {
                // 使用 str_replace 函數替換文字中的關鍵字
                $text = str_replace($match[0], $questions[$match[1]][$match[2]], $text);
            }
        }

        return $text;
    }

    /**
     * 取得投票完成跳轉頁面
     * 
     * @return array
     */
    public function getFinishPageUrl()
    {
        switch ($this->finishPage) {
            case '1':
                $page = ['index'];
                break;
            case 'value':
                $page = ['vote/vote-detail', 'voteID' => $this->voteID];
                break;
            default:
                $page = ['vote/vote-detail', 'voteID' => $this->voteID];
                break;
        }

        return $page;
    }
    
    /**
     * 檢查是否尚未開始投票
     *
     * @return bool
     */
    public function checkVoteReady()
    {
        if ($this->active == Votes::STATUS_READY || strtotime(date('Y-m-d H:i:s')) < strtotime($this->openStart)) {
            Yii::$app->session->setFlash('error', Yii::t('app', '投票尚未開始').'！');
            if ($this->type == Votes::TYPE_ANON && !Yii::$app->anon->isGuest) {
                Yii::$app->anon->identity->logout(false);
            }
            return true;
        }
        return false;
    }

    /**
     * 檢查是否超過投票時間
     *
     * @return bool
     */
    public function checkVoteExpired()
    {
        if ($this->active == Votes::STATUS_TERMINATE || strtotime(date('Y-m-d H:i:s')) > strtotime($this->openEnd)) {
            Yii::$app->session->setFlash('error', Yii::t('app', '投票已結束').'！');
            if ($this->type == Votes::TYPE_ANON && !Yii::$app->anon->isGuest) {
                Yii::$app->anon->identity->logout(false);
            }
            return true;
        }
        return false;
    }

    /**
     * 取得投票組別
     */
    public function getVoteParty($voteID, $lang = 'zh-tw', $allParty = false)
    {
        switch (strtolower($lang)) {
            case 'zh-tw':
                $name = 'name';
                break;
            case 'en-us':
                $name = 'nameE';
                break;
            default:
                $name = 'name';
                break;
        }
        $parties = Parties::find()->select(['party', $name])->where(['voteID' => $voteID])->asArray()->all();
        $result = ArrayHelper::map($parties, 'party', $name);
        if ($allParty) {
            $result += [Questions::ALL_PARTY_CODE => Model::i18n('All Parties', '共同投票')];
        }
        return $result;
    }
}
