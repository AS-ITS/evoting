<?php

namespace app\models;

use Yii;
use app\components\helper\ArrayHelper;
// TODO: 整理程式碼，$ballotList, $ballotCountAry, $parties, $passwordList可以用property
class FormManageCount extends \yii\base\BaseObject implements \app\interfaces\CountInterface
{
    /**
     * 投票識別碼
     */
    public $voteID = null;

    /**
     * 投票資訊
     */
    protected $_voteInfo = null;

    /**
     * 投票樣板
     */
    protected $_pattern = null;

    /**
     * 候選人配置
     */
    protected $_candiConfig = null;

    /**
     * 組別投票限制
     */
    protected $_parties = null;

    /**
     * 問題投票限制
     */
    protected $_questions = null;

    /**
     * 輪次
     */
    protected $_round = null;
    

    /**
     * 初始化
     */
    public function __construct($voteID = null, $round = null, $config = [])
    {
        if(is_null($voteID))
        {
            throw new \yii\base\InvalidConfigException('Please specify the "voteID" property.');
        }
        $this->voteID = $voteID;

        // 投票資訊
        $votes = new FormVotes;
        $this->_voteInfo = $votes->getVoteInfo($this->voteID);
        $this->_pattern  = $this->_voteInfo->pattern;
        // 輪次
        $this->_round = !empty($round) ? $round : $this->_voteInfo->round;
        // 候選人配置
        $candiConfig = new FormCandiConfig;
        $this->_candiConfig = $candiConfig->getConfigWithVoteID($this->voteID);
        // 組別
        $this->_parties = $this->_voteInfo->getVoteParty($this->voteID, Yii::$app->language, $this->_voteInfo->partyOrNot);
        // 問題
        $this->_questions = (new Questions())->getRoundQuestions($this->voteID, $this->_round)->indexBy('questionID')->all();

        parent::__construct($config);
    }
    
    /**
     * 計票單匯出搜尋
     *
     * @param  array $allModels
     * @param  array $params
     * @return array
     */
    public function search($allModels, $params)
    {
        if (count($params) > 1) {
            $data = array_filter($allModels, function ($value) use ($params) {
                $conditions = [true];
                if ($params['party'] !== '') {
                    $conditions[] = strpos($value['party'], $params['party']) !== false;
                }
                if ($params['questionID'] !== '') {
                    $conditions[] = strpos($value['questionID'], $params['questionID']) !== false;
                }
                return array_product($conditions);
            });

            return $data;
        }
        else {
            return $allModels;
        }
        
    }
    
    /**
     * 計票單匯出的DataProvider，不分頁
     *
     * @param  array $allModels
     * @param  array $params
     * @return \yii\data\ArrayDataProvider
     */
    public function exportDataProvider($allModels, $params)
    {
        $dataProvider = new DataProvider;
        return $dataProvider->getBasicArrayProvider(
            $this->search($allModels, $params), false
        );
    }

    /**
     * 取得候選人設定
     */
    public function getCandiConfig()
    {
        return $this->_candiConfig;
    }
  
    /**
     * 取得開票或計票統計，資料結構[組別ID][問題ID][內容]
     *
     * @param  bool $asResult 是否為開票統計
     * @return array
     */
    public function getBallotCountSort($asResult = false)
    {
        // 取得所有組別之選票
        $divisionAry = $this->PartyAry;

        // 回傳結果
        $result = [];

        // 有效票數量
        $validCount = $this->getBallotValidCount();

        // 所有候選人
        $CandiData = new CandiData;
        $candiList = ArrayHelper::index(
            $CandiData->getCandiListWithParty($this->voteID, $this->_round)->asArray()->all(), 'id'
        );
        $questionByParty = ArrayHelper::map($this->_questions, 'questionID', '', 'party');
        // 取得候選人得票數(從多到少)
        $ballotCountByParty = $this->getBallotSelected();

        // 分組處理
        foreach($divisionAry as $partyCode => $label)
        {
            $ballotCount = ArrayHelper::map($ballotCountByParty[$partyCode] ?? [], 'selCandiID', 'ballotID', 'questionID');
            ksort($ballotCount);
            // 計票單補足沒票的分組及問題
            if (!$asResult) {
                $partyQuestions = $questionByParty[$partyCode] ?? [];
                foreach ($partyQuestions as $questionID => $value) {
                    if (!ArrayHelper::keyExists($questionID, $ballotCount)) {
                        $ballotCount[$questionID] = [];
                    }
                }
            }
            $rank = $this->_getRanking($ballotCount, $candiList, $asResult);            
            // 分問題處理
            foreach ($ballotCount as $questionID => $ballot) {
                // 由得票數加上得票排序的相關資訊
                $r = ArrayHelper::merge(
                    [
                        'ballotCount' => $ballot,
                        'validCount'  => $validCount[$partyCode][$questionID] ?? 0,
                    ],
                    $rank[$questionID]
                );
                $result[$partyCode][$questionID] = $r;
            }
        }

        // 返回所有分組資料
        return $result;
    }

    /**
     * 取得候選人排序
     *
     * @param  array $ballotCount
     * @param  string $party
     * @param  bool $asResult 是否使用在投票結果
     * @return array
     */
    protected function _getRanking($ballotCount, $candiList, $asResult)
    {
        // 取得有票的人的排序
        if (!$asResult) { // 計票
            $data = $this->_countSort($ballotCount, $candiList); 
        }
        else {  // 開票
            $data = $this->_resultCountSort($ballotCount, $candiList); 
        }

        // 刪除已有選票之候選人
        foreach($ballotCount as $questionID => $candiIdList)
        {
            foreach ($candiIdList as $candiId => $count) {
                unset($candiList[$candiId]);
            }
        }

        $result = [];
        // 將無選票候選人補上
        foreach ($data as $questionID => $rankData) {
            $ranking = &$rankData['ranking'];
            if(is_null($ranking)) $ranking = [];
            foreach($candiList as $id => $candiData)
            {
                if ($candiData['questionID'] == $questionID) {
                    $rank = is_null($rankData['rankingNext']) ? 1 : $rankData['rankingNext'];
                    // 組合表格所需 array 格式 (沒票的候選人)
                    $ranking[] = $this->getStoreRanking(
                        $candiData['id'], ($candiData['isReachThreshold'] == CandiData::REACH_THRESHOLD ? 0 : $rank), 0, false, $candiData
                    );
                }
            }

            // 保存 rankingNext 值後再 unset
            $savedRankingNext = $rankData['rankingNext'] ?? null;
            // 沒有票的候選人名次
            unset($rankData['rankingNext']);
            // 標記投票結果
            $rankData['ranking'] = $this->_electedSort($ranking, $questionID);
            $result[$questionID]['ranking'] = $rankData['ranking'];
            $result[$questionID]['rankingNext'] = $savedRankingNext;
        }

        return $result;
    }

    /**
     * 取得開票結果排序，以問題ID當作index
     *
     * @param  array $ballotCount 以問題分類的各候選人選票統計
     * @param  array $getCandiList 以候選人ID作為index的被選候選人
     * @return array
     */
    protected function _resultCountSort($ballotCount, $getCandiList)
    {
        $r = [];
        foreach($ballotCount as $questionID => $selCandiList)
        {
            arsort($selCandiList);
            
            // 把各候選人選票統計以性別分Group
            $sexBallotCount = [];
            foreach ($selCandiList as $selCandiID => $countNum) {
                $sexBallotCount[$selCandiID] = $getCandiList[$selCandiID];
                $sexBallotCount[$selCandiID]['count'] = $selCandiList[$selCandiID];
            }
            $sexBallotCount = ArrayHelper::index($sexBallotCount, 'id', 'sex');
            
            $rankNum = 0;// 得票排序(起始數減一)
            $lastNum = null;// 上一個票數
            $femaleNum = 0; // 女性已保留人數

            // 女性保留名額統計
            if ($this->_voteInfo->addiCondition == 'female') {
                $femaleKeepNum = $this ->_questions[$questionID]->numFemaleKeep;// 女性保留人數設定
                foreach ($sexBallotCount[0] as $selCandiID => $candiData) {
                    // 若女性已保留人數小於設定值且女性有票候選人數量大於0
                    if ($femaleNum < $femaleKeepNum && count($sexBallotCount[0]) > 0) {
                        // 組合表格所需 array 格式 (有票的候選人)
                        $r[$questionID]['ranking'][] = $this->getStoreRanking(
                            $selCandiID, (($lastNum == $countNum && $lastNum != 0) ? $rankNum : ++$rankNum), $candiData['count'], true, $candiData
                        );
        
                        // 紀錄當前數量，給下一次迴圈判斷使用
                        $lastNum = $countNum;
                        ++$femaleNum;
                        // 清除處理過的女性候選人資料
                        unset($selCandiList[$selCandiID]);
                        unset($sexBallotCount[0][$selCandiID]);
                    }
                    else {
                        break;
                    }
                }
            }
            // 剩餘得票候選人統計
            foreach ($selCandiList as $selCandiID => $countNum) {
                $candiData = &$getCandiList[$selCandiID];

                // 組合表格所需 array 格式 (有票的候選人)
                $r[$questionID]['ranking'][] = $this->getStoreRanking(
                    $selCandiID, (($lastNum == $countNum && $lastNum != 0) ? $rankNum : ++$rankNum), $countNum, false, $candiData
                );

                // 紀錄當前數量，給下一次迴圈判斷使用
                $lastNum = $countNum;
            }
            // 沒有票的候選人名次
            $r[$questionID]['rankingNext'] = ++$rankNum;
        }
        
        return $r;
    }

    /**
     * 取得計票排序，以問題ID當作index
     *
     * @param  array $ballotCount 以問題分類的各候選人選票統計
     * @param  array $getCandiList 以候選人ID作為index的被選候選人
     * @return array
     */
    protected function _countSort($ballotCount, $getCandiList)
    {
        $r = [];
        foreach($ballotCount as $questionID => $selCandiList)
        {
            arsort($selCandiList);

            $rankNum = 1;// 得票排序(起始數減一)
            $lastNum = null;// 上一個票數
            $i = 1; // 處理數量

            // 得票候選人統計
            foreach ($selCandiList as $selCandiID => $countNum) {
                // 得票排序計算
                if ($lastNum == $countNum || $lastNum == null) {
                    $rank = $rankNum;
                }
                else {
                    $rank = $i;
                    $rankNum = $rank;
                }
                $candiData = &$getCandiList[$selCandiID];

                // 組合表格所需 array 格式 (有票的候選人)
                $r[$questionID]['ranking'][] = $this->getStoreRanking($selCandiID, $rank, $countNum, false, $candiData);

                // 紀錄當前數量，給下一次迴圈判斷使用
                $lastNum = $countNum;
                $i++;
            }
            // 沒有票的候選人名次
            $r[$questionID]['rankingNext'] = count($selCandiList)+1;
        }

        return $r;
    }
    
    /**
     * 返回候選名單圈選資訊
     *
     * @param  string $selCandiID 候選名單編號
     * @param  string $rank 排序
     * @param  string $count 得票數
     * @param  bool $femaleKeep 是否女性保留名額
     * @param  array $candiData 候選名單資料
     * @return array
     */
    protected function getStoreRanking($selCandiID, $rank, $count, $femaleKeep, $candiData)
    {
        $other = ArrayHelper::isJson($candiData['other']) ? json_decode($candiData['other'], true) : $candiData['other'];
        return [
            'candi' => $selCandiID,
            'rank'  => $rank,
            'count' => $count,
            'party' => $candiData['party'],
            'Name' => $candiData['Name'],
            'NameE' => $candiData['NameE'],
            'isReachThreshold' => $candiData['isReachThreshold'],
            'reachThresholdRound' => ArrayHelper::isJson($candiData['other']) ? ($other['round'] ?? '') : '',
            'reachThresholdCount' => ArrayHelper::isJson($candiData['other']) ? ($other['count'] ?? '') : '',
            'femaleKeep' => $femaleKeep,          // 是否女性保留名額
            'orderNum' => $candiData['orderNum'],   // 自訂排序
            'questionID' => $candiData['questionID'],
            'relateParty' => $candiData['relateParty'],
            'specialHonor' => $candiData['specialHonor'],
            'other' => $candiData['other'],
        ];
    }
  
    /**
     * 判斷選舉結果(當選、遞補、落選)
     *
     * @param  array $ranking
     * @param  string $questionID
     * @return array
     */
    public function _electedSort($ranking, $questionID)
    {
        $electNum = $this->_questions[$questionID]->maxElect; // 當選人數
        $keepNum  = $this ->_questions[$questionID]->numOfKeep; // 遞補人數
        $tempRankAry = ArrayHelper::index($ranking, null, 'rank'); // 以 rank 去 group Array
        // 將已達門檻的放在最前面(rank0)
        ksort($tempRankAry);
        $result = [];
        foreach($tempRankAry as $rankNum => $rankAry)
        {
            if($electNum != 0) // 當選、待當選
            {
                if(($cRA = count($rankAry)) <= $electNum)
                {
                    $electNum -= $cRA;
                    $electStatus = 'E';// 當選
                }
                else
                {
                    $electNum = 0;
                    $keepNum -= $cRA - $electNum; // 處理待當選與遞補交集 * 可能需記錄或下次重算
                    $electStatus = 'CE';// 待當選
                }
            }
            else if($keepNum != 0) // 遞補、待遞補
            {
                if(($cRA = count($rankAry)) <= $keepNum)
                {
                    $keepNum -= $cRA;
                    $electStatus = 'W';// 遞補
                }
                else
                {
                    $keepNum = 0;
                    $electStatus = 'CW';// 待當選
                }
            }
            else // 非當選跟遞補，都落選
            {
                $electStatus = 'LE';// 落選
            }
            foreach($rankAry as $rank)
            {
                $result[] = array_merge($rank,[
                    'elect' => $electStatus
                ]);
            }
        }
        
        return $result;
    }

    /**
     * 取得表格元件
     */    
    /**
     * getArrayProvider
     *
     * @param  array $data
     * @param  int $splitRow 分成幾行排列
     * @param  string $sort 排序方式(得票排序:N、姓名筆劃:L、候選人順序:I)
     * @param  bool $group 是否合成一個表格
     * @return array
     */
    public function getArrayProvider(&$data, $splitRow = 3, $sort=FormManageCount::COUNT_BY_BALLOTS, $group=false)
    {
        $dataProvider = new DataProvider;
        $candiConfigModel = new CandiConfig;

        $result = [];
        $models = [];
        // ksort($data);
        foreach($data as $questionID => $d)
        {
            // 候選名單配置
            if ($this->_voteInfo->candiConfig == Votes::CANDI_CONFIG_BY_Q) {
                $candiConfig = $candiConfigModel->getConfigWithVoteID($this->voteID, $questionID)->one();
            }
            else {
                $candiConfig = $this->_candiConfig;
            }
            // 排序
            $ranking = $this->sortRanking($candiConfig->num, $d['ranking'], $sort);
            // 按得票高低排序: 把已達門檻的候選人放到最後面
            if ($sort == FormManageCount::COUNT_BY_BALLOTS) {
                ArrayHelper::multisort($ranking, 'isReachThreshold', SORT_ASC);
            }

            // 是否合成一個表格
            if ($group) {
                $models = array_merge($models, $ranking);
            }
            else {
                // 每行最多數量
                $rowCount = ceil(count($ranking)/$splitRow);
                while(!empty($ranking))
                {
                    $result[$questionID][] = $dataProvider
                        ->getBasicArrayProvider(array_splice($ranking, 0, $rowCount), false);
                }
            }
        }

        // 是否合成一個表格
        if ($group) {
            $rowCount = ceil(count($models)/$splitRow);
            while(!empty($models)) {
                $result[] = $dataProvider->getBasicArrayProvider(array_splice($models, 0, $rowCount), false);
            }
        }
        
        return $result;
    }
    
    /**
     * 計算選票數量: 只要有任一分組有清點人數，就不採用啟用密碼數量的資料
     *
     * @param  mixed $modelParties 組別資料
     * @param  array $passwordList 密碼清單
     * @param  mixed $party 組別
     * @param  bool $isAllParty 是否為共同分組問題
     * @return int
     */
    public function getBallotNum($modelParties, $passwordList, $party=null, $isAllParty=true)
    {
        $passwordCount = 0;
        if ($isAllParty) { // 共同分組
            foreach ($modelParties as $modelParty) {
                if (!empty($modelParty->numCounting)) {
                    $passwordCount += $modelParty->numCounting;
                }
                else {
                    $passwordCount += isset($passwordList[$modelParty->party]) ? count($passwordList[$modelParty->party]) : 0;
                }
            }
        }
        else { // 各別分組
            if (!empty($modelParties[$party]->numCounting)) {
                $passwordCount = $modelParties[$party]->numCounting;
            }
            else {
                $passwordCount = isset($passwordList[$party]) ? count($passwordList[$party]) : 0;
            }
        }
        
        return $passwordCount;
    }

    /**
     * 取得各組別密碼數量
     *
     * @param  array $passwordList
     * @param  array $parties
     * @return array
     */
    public function getPartyPasswordCount($passwordList, $parties)
    {
        $result = [];

        foreach ($parties as $party) {
            // 如果分組有設定清點人數，以清點人數為準
            $result[$party['party']] = !empty($party['numCounting']) ? $party['numCounting'] : count($passwordList[$party['party']]);
        }

        return $result;
    }
    
    /**
     * 取得密碼及有/無效票數量
     *
     * @param  string $party 組別
     * @param  string $questionID 問題ID
     * @param  array $validCount 有/無效票數量統計
     * @param  array $ballotList 選票清單
     * @param  array $ballotCountAry 投票數
     * @param  array $modelParties 組別資料
     * @param  array $passwordList 密碼清單
     * @param  array $questions 問題資料
     * @return void
     */
    public function getPasswordAndValidCount($party, $questionID, &$validCount, $ballotList, $ballotCountAry, $modelParties, $passwordList, $questions)
    {
        // 全部分組共同問題
        if ($party === Questions::ALL_PARTY_CODE) {
            $ballots = ArrayHelper::index($ballotList, 'ballotID');
            foreach ($ballotCountAry as $ballotID => $ballotCount) {
                if ($this->checkBallotValid($questionID, $ballotCount, $questions)) {
                    $validCount[$questionID]['valid'] ++;
                }
                else {
                    $validCount[$questionID]['invalid'] ++;
                }
                unset($ballots[$ballotID]);
            }
            // 處理沒有圈選候選人的選票
            if (count($ballots) > 0) {
                foreach ($ballots as $ballotID => $ballot) {
                    if ($this->checkBallotValid($questionID, [], $questions)) {
                        $validCount[$questionID]['valid'] ++;
                    }
                    else {
                        $validCount[$questionID]['invalid'] ++;
                    }
                }
            }
            // 選票數量
            $passwordCount = $this->getBallotNum($modelParties, $passwordList);
        }
        // 分組問題
        else {
            $ballotListGroupByParty = ArrayHelper::map($ballotList ?: [], 'ballotID', '', 'party');
            foreach ($ballotListGroupByParty[$party] ?? [] as $ballotID => $value) {
                if ($this->checkBallotValid($questionID, $ballotCountAry[$ballotID], $questions)) {
                    $validCount[$questionID]['valid'] ++;
                }
                else {
                    $validCount[$questionID]['invalid'] ++;
                }
            }
            // 選票數量
            $passwordCount = $this->getBallotNum($modelParties, $passwordList, $party, false);
        }

        return ['password' => $passwordCount, 'valid' => $validCount];
    }
    
    /**
     * 計票排序
     *
     * @param  string $candiConfigNum 編號顯示方式
     * @param  array $ranking 排序資料
     * @param  string $sort 排序方式
     * @return array
     */
    public function sortRanking($candiConfigNum, $ranking, $sort)
    {
        // 排序
        switch ($sort) {
            case self::COUNT_BY_BALLOTS:
                $sortColumn = $candiConfigNum == 'A' ? 'candi' : 'orderNum';
                ArrayHelper::multisort($ranking, ['rank', $sortColumn], [SORT_ASC, SORT_ASC]);
                break;
            case self::COUNT_BY_NAME:
                array_walk($ranking, function($value, $key) use (&$ranking) {
                    /* $ranking[$key]['sort'] = iconv('UTF-8', 'big5', mb_substr($value['Name'], 0, 1, 'utf-8')); 中文會亂碼
                    if (!$ranking[$key]['sort']) {
                        echo substr($ranking[$key]['Name'], 0, 1);
                    }   */
                    // 取得第一個字（UTF-8）
                    $firstChar = mb_substr($value['Name'], 0, 1, 'UTF-8');
                    // 轉換為 BIG-5 用於筆劃排序（BIG-5 編碼按筆劃順序排列）
                    // 使用 mb_convert_encoding 而非 iconv（更可靠）
                    $big5Char = mb_convert_encoding($firstChar, 'BIG-5', 'UTF-8');
                    // 使用 BIG-5 的十六進位值排序，確保按筆劃順序
                    $ranking[$key]['sort'] = $big5Char;
                });
                ArrayHelper::multisort($ranking, 'sort', SORT_ASC);
                break;
            case self::COUNT_BY_LIST:
                $sortColumn = $candiConfigNum == 'A' ? 'candi' : 'orderNum';
                ArrayHelper::multisort($ranking, $sortColumn, SORT_ASC);
                break;
        }

        return $ranking;
    }
    
    /**
     * 檢查是否為有效票: 如果有特殊規則，則檢查特殊規則，否則檢查一般規則
     *
     * @param  string $questionID 問題辨識碼
     * @param  array $ballotCount 投票數
     * @param  array $questions 該輪次所有問題
     * @return bool
     */
    public function checkBallotValid($questionID, $ballotCount, $questions)
    {
        // 一般的問題上下限處理
        return $this->processNormalRule($questionID, $ballotCount, $questions);
    }
    
    /**
     * 檢查是否為有效票: 有圈選候選人就看是否符合投票限制，沒圈選就看是否符合最少投票數
     *
     * @param  string $questionID
     * @param  array $ballotCount
     * @param  array $questions 該輪次所有問題
     * @return bool
     */
    public function processNormalRule($questionID, $ballotCount, $questions)
    {
        if ((ArrayHelper::keyExists($questionID, $ballotCount)
            && $ballotCount[$questionID] >= $questions[$questionID]['leastNumBallots']
            && $ballotCount[$questionID] <= $questions[$questionID]['numBallots'])
            || (empty($ballotCount[$questionID]) && $questions[$questionID]['leastNumBallots'] == 0)
        ) {
            return true;
        }
        else {
            return false;
        }
    }
    
    /**
     * 取得所有組別
     */
    public function getPartyAry()
    {
        return $this->_parties;
    }

    /**
     * 取得所有組別之問題
     */
    public function getVoteQuestions()
    {
        return $this->_questions;
    }

    /**
     * 取得選票數量、候選人資料並排序
     */
    public function getBallotSelected($party = null)
    {
        $ballotsSelected = new FormBallotsSelected;
        $result = $ballotsSelected->getValidBallot($this->voteID, $this->_round, $party);
        return $result;
    }

    /**
     * 取得有圈選的有效及無效選票數量
     */
    public function getBallotValidCount()
    {
        $ballotsSelected = new FormBallotsSelected;
        return $ballotsSelected->getBallotValidCount($this->voteID, $this->_round);
    }

    /**
     * 取得組別候選人
     */
    public function getCandiList($party)
    {
        $candiData = new FormCandiData;
        return $candiData->getCandiListWithParty($this->voteID, $this->_round, $party)->all();
    }
    
    /**
     * 取得投票樣板
     */
    public function getPattern()
    {
        return $this->_pattern;
    }

    /**
     * 取得投票名稱
     */
    public function getVoteName()
    {
        return $this->_voteInfo->Name;
    }

    /**
     * 取得投票資訊
     */
    public function getVoteInfo()
    {
        return $this->_voteInfo;
    }

    /**
     * 取得投票輪次
     */
    public function getRound()
    {
        return $this->_round;
    }
}
