<?php

namespace app\models;

use Yii;

use app\components\helper\ArrayHelper;

class FormManageVote extends \yii\base\BaseObject
{
    /**
     * 是否可投廢票
     */
    static public $invalidBallot = true;

    /**
     * 投票識別碼
     */
    public $voteID = null;

    /**
     * 投票輪次
     */
    public $round = null;

    /**
     * 投票組別
     */
    public $party = null;

    /**
     * 問題識別碼
     */
    public $questionID = null;

    /**
     * 投票資訊
     */
    protected $_voteInfo = null;

    /**
     * 投票組別類型
     */
    protected $_pattern = null;

    /**
     * 投票完成跳轉頁面
     */
    protected $_finishPage = null;

    /**
     * 初始化
     */
    public function __construct($voteID=null, $party=null, $config=[])
    {
        if(is_null($voteID))
        {
            throw new \yii\base\InvalidConfigException('Please specify the "voteID" property.');
        }
        $this->voteID = $voteID;
        $this->_voteInfo = $this->getVotesInfo();
        $this->round = $this->_voteInfo->round;
        $this->party = is_null($party) ? $this->getPartyCode() : $party;
        $this->_pattern = $this->getPattern();
        parent::__construct($config);
    }

    /**
     * 刪除投票
     */
    public function deleteVote($voteID = null)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;

        // 刪除基本資料: 因為其他與投票相關資料都有Constraint，故只有額外刪除組別(parties)
        $FormVotes = new FormVotes;
        $statusVoteInfo = $FormVotes->deleteVote($this->voteID);

        // 刪除投票限制(票)
        $FormParties = new FormParties;
        $statusParty = $FormParties->deleteParty($this->voteID);

        // 刪除照片
        $picPath = Yii::getAlias('@filePool').DIRECTORY_SEPARATOR.'candidatePic'.DIRECTORY_SEPARATOR;
        if (file_exists($picPath.$voteID)) {
            \yii\helpers\FileHelper::removeDirectory($picPath.$voteID);
        }
        // 刪除附件
        $filePath = Yii::getAlias('@filePool').DIRECTORY_SEPARATOR.'candidateFile'.DIRECTORY_SEPARATOR;
        if (file_exists($filePath.$voteID)) {
            \yii\helpers\FileHelper::removeDirectory($filePath.$voteID);
        }

        return [
            'voteInfo' => $statusVoteInfo,// 基本資料
            'party' => $statusParty,// 投票限制(票)
        ];
    }

    // =================== 取得內部數值 ===================

    /**
     * 取得投票資訊
     */
    public function getVoteInfo()
    {
        if(is_null($this->_voteInfo))
            $this->_voteInfo = $this->getVotesInfo();
        return $this->_voteInfo;
    }

    /**
     * 取得投票組別類型
     */
    public function getPattern()
    {
        if(is_null($this->_pattern))
            $this->_pattern = $this->_voteInfo->pattern;
        return $this->_pattern;
    }

    // =================== 候選名單 ===================

    /**
     * 取得投票候選名單
     */
    public function getDataProvider($voteID = null, $party = null, $questionID = null, $pagination = false, $splitRow = 1)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($party))
            $party = $this->party;
        if(is_null($questionID))
            $questionID = Yii::$app->request->get('questionID');
        if(is_null($voteID) || is_null($party) || is_null($questionID))
            return null;
        $DataProvider = new DataProvider;
        // 候選人列表多行顯示
        if ($splitRow > 1) {
            $total = $this->getCandidateList($voteID, $party, $questionID)->count();
            $limit = ceil($total/$splitRow);
            $result = [];
            for ($i=1; $i <= $splitRow; $i++) {
                $dataProviderResult = $DataProvider->getBasicDataProvider(
                    $this->getCandidateList($voteID, $party, $questionID)->limit($limit)->offset(($i-1)*$limit), // 投票候選名單
                    $pagination
                );
                if (count($dataProviderResult->models) > 0) {
                    $result[] = $dataProviderResult;
                }
            }
            return $result;
        }
        return [$DataProvider->getBasicDataProvider(
            $this->getCandidateList($voteID, $party, $questionID), // 投票候選名單
            $pagination
        )];
    }

    /**
     * 有候選名單
     */
    public function isCandidates($voteID=null, $party=null, $questionID=null, $includeAllParty=true)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($party))
            $party = $this->party;
        if(is_null($questionID))
            $questionID = Yii::$app->request->get('questionID');
        if(is_null($voteID) || is_null($party) || is_null($questionID))
            return null;
        return $this->getCandidateList($voteID, $party, $questionID, $includeAllParty)->exists();
    }

    /**
     * 投票候選名單
     */
    public function getCandidateList($voteID = null, $party = null, $questionID = null, $includeAllParty = true)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($party))
            $party = $this->party;
        if(is_null($questionID))
            $questionID = Yii::$app->request->get('questionID');
        if(is_null($voteID) || is_null($party) || is_null($questionID))
            return null;
        $searchModel = new FormCandiData;
        return $searchModel->getCandiListWithPartyQuestion($voteID, $party, $questionID, $includeAllParty);
    }

    // =================== 投票資訊 ===================

    /**
     * 投票資訊
     */
    public function getVotesInfo($voteID = null)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($voteID))
            return null;
        $votes = new FormVotes;
        return $this->_voteInfo = $votes->getVoteInfo($voteID);
    }

    /**
     * 取得投票問題
     */
    public function getVotePartyQuestion($voteID=null, $round=null, $party=null, $lang='zh-tw')
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($voteID))
            return null;
        if(is_null($round))
            $round = $this->round;
        $votes = new FormVotes;
        return $votes->getVotePartyQuestion($voteID, $round, $party, $lang);
    }

    /**
     * 取得投票組別
     */
    public function getVoteParty($voteID = null, $lang = 'zh-tw', $allParty = false)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($voteID))
            return null;
        $votes = new FormVotes;
        return $votes->getVoteParty($voteID, $lang, $allParty);
    }

    // =================== 投票限制 ===================

    /**
     * 使用者投票組別代碼
     */
    public function getPartyCode($voteInfo = null)
    {
        if(is_null($voteInfo))
            $voteInfo = $this->_voteInfo;
        // 分組
        if ($voteInfo->partyOrNot == '1')
        {
            //匿名
            if ($voteInfo->type == Votes::TYPE_ANON && Yii::$app->anon->identity) {
                return Yii::$app->anon->identity->getAuthData()['party'];
            }
            else {
                return null;
            }
        }
        else if ($voteInfo->partyOrNot == '0')
            return Parties::DEF_PARTY; // def
        return null;
    }

    /**
     * 分組投票限制
     */
    public function getPartyBallotLimit($voteID = null, $party = null)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($voteID))
            return null;
        
        // 是否分組
        $parties = new FormParties;
        if ($this->_voteInfo->partyOrNot)
        {
            if(is_null($party))
                $party = $this->party;
            return $parties->getPartyNumBallots($voteID, $party);
        }
        else
            return $parties->getPartyNumBallots($voteID); // use 'def'
    }

    /**
     * 問題投票限制
     */
    public function getQuestionBallotLimit($questionID = null)
    {
        if(is_null($questionID))
            $questionID = Yii::$app->request->get('questionID');
        
        // 是否分組
        $questions = new Questions();
        return $questions->getQuestionNumBallots($questionID);
    }

    /**
     * 組別所有問題
     */
    public function getPartyQuestions($voteID = null, $party = null, $allParty = false)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        
        // 是否分組
        $questions = new Questions();
        return $questions->getPartyQuestionsInfo($voteID, $party, $allParty);
    }

    /**
     * 投票所有組別限制
     */
    public function getPartyBallotLimitAll($voteID = null)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($voteID))
            return null;
        
        // 是否分組
        $parties = new FormParties;
        $partyAll = $parties->getPartyAll($voteID);
        $questions = new Questions();
        $questionsList = $questions->getQuestionListWithVoteID($voteID)->all();
        $limit = [];
        if(count($partyAll) == 1)
        {
            foreach ($questionsList as $question) {
                $limit[$question->party][$question->questionID] = [
                    'mostNum' => $question->numBallots, // 可投票數
                    'leastNum' => $question->leastNumBallots, // 最少應投票數
                ];
            }
            
            return $limit;
        }
        foreach ($questionsList as $question) {
            $limit[$question->party][$question->questionID] = [
                'mostNum' => $question->numBallots, // 可投票數
                'leastNum' => $question->leastNumBallots, // 最少應投票數
            ];
        }
        return $limit;
    }

    // =================== 候選名單配置 ===================

    /**
     * 投票候選名單配置
     */
    public function getCandidateConfig($voteID=null, $questionID=null)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($voteID))
            return null;
        $FormCandiConfig = new FormCandiConfig;
        return $FormCandiConfig->getConfigWithVoteID($voteID, $questionID);
    }

    /**
     * 取得設定的欄位排序
     * 
     * @param array $columnsAry 所有欄位的Ary
     * 
     * @return array GridView::$columns
     */
    public function getFieldSort($columnsAry, $addField=[], $removeField=[], $header=false)
    {
        $FormCandiConfig = new FormCandiConfig;
        $FormCandiConfig->getConfigWithVoteID($this->voteID);
        return $FormCandiConfig->getFieldSort($columnsAry, $addField, $removeField, $header);
    }

    // =================== 投票者資訊 ===================

    /**
     * 建立新投票者資訊
     */
    public function getNewVoteBallot($creator, $adminAdd = false, $voteID = null, $party = null)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($party))
            $party = $this->party;
        if(is_null($voteID) || is_null($party))
            return null;
        $FormBallots = new FormBallots;
        return $FormBallots->getNewVoteBallot($voteID, $party, $creator, $adminAdd);
    }

    /**
     * 取得投票者資訊
     */
    public function getVoteBallot($ballotID, $voteID = null)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($voteID))
            return null;
        $FormBallots = new FormBallots;
        return $FormBallots->getVoteBallot($voteID,$ballotID);
    }

    /**
     * 取得新選票建立者
     *
     * @param array $sysIds
     * @param mixed $voteInfo
     * @param bool $decryptPlaintext
     */
    public function getNewBallotChanger($sysIds, $voteInfo = null, $decryptPlaintext = false)
    {
        if(is_null($voteInfo))
            $voteInfo = $this->_voteInfo;
        if($voteInfo->type == Votes::TYPE_ANON) // 匿名投票
        {
            $FormPasswords = new FormPasswords;
            return $FormPasswords->getNewBallotChanger($sysIds, $decryptPlaintext);
        }
        return null;
    }

    // =================== 選票資訊 ===================

    /**
     * 保存選票
     */
    public function saveVoteBallot($postData, $voteID=null, $voteInfo=null)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($voteInfo))
            $voteInfo = $this->_voteInfo;
        
        // 保存
        $FormBallots = new FormBallots;
        $status = $FormBallots->creatorBallot($voteID, $postData);
        // 如果略過圈選檢查才進行登出，否則會無法進到check-ballot頁面
        if($status && $voteInfo->skipCheck)
        {
            if ($voteInfo->type == Votes::TYPE_ANON) //匿名
            {
                Yii::$app->anon->identity->logout(false);
            }
        }
        return $status;
    }

    /**
     * 是否已經投票
     */
    public function isVoteBallot($creator=null, $voteID=null, $round=null, $party=null, $voteInfo=null)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($round))
            $round = $this->round;
        if(is_null($party))
            $party = $this->party;
        if(is_null($voteInfo))
            $voteInfo = $this->_voteInfo;
        if(is_null($creator))
        {
            if ($voteInfo->type == Votes::TYPE_NO_AUTH){    // 匿名投票
                return false;
            }
            if ($voteInfo->type == Votes::TYPE_ANON) {
                if (Yii::$app->anon->isGuest) {
                    return Yii::$app->response->redirect($this->getFinishPage());
                }
                $creator = Yii::$app->anon->identity->getAuthData()['id'];
            }
            else {
                return null;
            }
        }

        return !is_null($this->getBallotId($creator, $voteID, $round, $party));
    }

    /**
     * 取得選票
     */
    public function getBallotSelected($ballotID = null, $voteID = null)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($voteID))
            return null;
        if(is_null($ballotID))
            return [];
        $FormBallotsSelected = new FormBallotsSelected;
        return $FormBallotsSelected->getBallotSelected($voteID,$ballotID);
    }

    /**
     * 取得選票
     */
    public function getBallotId($creator, $voteID=null, $round=null, $party=null)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($round))
            $round = $this->round;
        if(is_null($party))
            $party = $this->party;
        if(is_null($voteID) || is_null($party))
            return null;
        $FormBallots = new FormBallots;
        return $FormBallots->getBallotId($voteID, $round, $party, $creator);
    }

    /**
     * 投票數(取得選票數量)
     *
     * @param  bool|null $valid 是否只取得有效票、無效票
     * @param  string|null $voteID
     * @param  string|int|null $round
     * @param  string $group
     * @return array
     */
    public function getBallotCount($valid=null, $voteID=null, $round=null, $group='ballotID')
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($voteID))
            return null;
        if(is_null($round))
            $round = $this->round;

        $q = (new FormBallotsSelected)
            ->getVoteSelected($voteID, $round)
            ->select('ballotID, party, questionID, count(selCandiID) as selCandiID')
            ->groupBy(['ballotID', 'questionID']);

        if(!is_null($valid)) // 是否只取得有效票、無效票
        {
            if($valid === true) { // 有效票
                $q->andWhere(['isValiable' => 1]);
            } 
                
            else if($valid === false) { // 無效票
                $q->andWhere(['isValiable' => 0]);
            }
        }
        return ArrayHelper::map($q->all(), 'questionID', 'selCandiID', $group);
    }

    /**
     * 取得投票者選票的候選人
     */
    public function getBallotCandi($voteID=null, $round=null, $party=null, $creator=null, $voteInfo=null)
    {
        if(is_null($voteID))
            $voteID = $this->voteID;
        if(is_null($round))
            $round = $this->round;
        if(is_null($party))
            $party = $this->party;
        if(is_null($voteInfo))
            $voteInfo = $this->_voteInfo;
        if(is_null($voteID) || is_null($party) || is_null($voteInfo))
            return null;
        if(is_null($creator))
        {
            if ($voteInfo->type == Votes::TYPE_ANON) {
                $creator = Yii::$app->anon->identity->getAuthData()['id'];
            }
            else {
                return null;
            }
        }
        
        $FormBallots = new FormBallots;
        $ballotID = $FormBallots->getBallotId($voteID, $round, $party, $creator);
        $FormBallotsSelected = new FormBallotsSelected;
        return $FormBallotsSelected->getVoteCandi($voteID, $ballotID);
    }

    // =================== 其他功能 ===================

    /**
     * 阿拉伯數字轉英文單字
     */
    public function getEngNum($num)
    {
        $strNo = [
            'zero','one','two','three','four',
            'five','six','seven','eight','nine',
            'ten','eleven','twelve','thirteen','fourteen',
            'fifteen','sixteen','seventeen','eighteen','Nineteen'
        ];
        $strTens = [
            '','ten','twenty','thirty','forty',
            'fifty','sixty','seventy','eighty','ninety'
        ];
        $strUnits = [
            '','hundred','thousand','million','billion','trillion'
        ];
        $numLv = 0;
        
        while ($num > 0){
            $m1000 = $num % 1000;
            $m100 = $m1000 % 100;
            $m10 = $m1000 % 10;
            $num3 = intval($m1000 / 100);
            $num2 = intval($m100 / 10);
            $strTotal = '';
            if($numLv > 0)
            {
                $strTotal = $strUnits[$numLv + 1].' '. $strTotal;
            }
            if($m100 > 0)
            {
                if($m100 <= 19)
                {
                    $strTotal = $strNo[$m100]. ' '. $strTotal;
                }
                else
                {
                    if($m10 > 0)
                    {
                        $strTotal = $strTens[$num2].' '.$strNo[$m10].' '.$strTotal;
                    }
                    else
                    {
                        $strTotal = $strTens[$num2].' '.$strTotal;
                    }
                }
            }
            if($num3 > 0){
                $strTotal = $strNo[$num3].' '.$strUnits[1].' '.$strTotal;
            }
            $num = intval($num / 1000);
            $numLv = $numLv + 1;
        }

        return $strTotal;
    }

    /**
     * 取得投票完成跳轉頁面
     */
    public function getFinishPage()
    {
        return $this->_voteInfo->getFinishPageUrl();
    }
}
