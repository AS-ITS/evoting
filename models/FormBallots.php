<?php
namespace app\models;

use Yii;
use yii\helpers\Json;
use app\models\Ballots;
use app\components\Model;
use app\components\helper\ArrayHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class FormBallots extends Ballots
{
    /**
     * 有效票或廢票
     */
    public $selectNum;
    
    /**
     * 問題ID
     */
    public $questionID;

    /**
     * Returns the validation rules for attributes.
     * 
     * @return array validation rules
     */
    public function rules()
    {
        return [
            [['voteID', 'round'], 'required', 'on' => ['update', 'creator']],
            [['ballotID'], 'integer', 'on' => ['search', 'update', 'creator']],
            [['voteID'], 'string', 'max' => 20, 'on' => ['search', 'update', 'creator']],
            [['party'], 'string', 'max' => 12, 'on' => ['search', 'update', 'creator']],
            [['isAdminAdd'], 'string', 'max' => 1, 'on' => ['search', 'update', 'creator']],
            [['ip'], 'string', 'max' => 128, 'on' => ['search', 'update', 'creator']],
            [['creator', 'modifier'], 'string', 'max' => 50, 'on' => ['search', 'update', 'creator']],
            [[
                'voteID','insTime', 'updTime', 'party', 'ballotID', 'isAdminAdd',
                'ip', 'creator', 'modifier', 'insTime', 'updTime', 'selectNum'
            ], 'safe', 'on' => 'search'],
            [[
                'voteID','insTime', 'party', 'isAdminAdd', 'ip',
                'creator', 'insTime', 'modifier', 'updTime'
            ], 'safe', 'on' => 'creator'],
            [[
                'modifier', 'updTime'
            ], 'safe', 'on' => ['update']],
            [
                ['voteID', 'round', 'party', 'creator'], 
                'unique', 
                'targetAttribute' => ['voteID', 'round', 'party', 'creator'], 
                'on' => ['update', 'creator']
            ],
        ];
    }

    /**
     * 取得選票列表
     */
    public function getBallotList($voteID, $round, $params = [])
    {
        $this->setScenario('search');
        $query = parent::find();
        $query->where(['voteID' => $voteID, 'round' => $round])
            ->orderBy(['ballotID' => SORT_ASC]);

        $params = Model::trimParams($params, $this->className());
        $this->load($params);

        if (!$this->validate()) {
            return $query;
        }

        // grid filtering conditions
        $query
            ->andFilterWhere(['like', 'voteID', $this->voteID])
            ->andFilterWhere(['like', 'party', $this->party])
            ->andFilterWhere(['isAdminAdd' => $this->isAdminAdd])
            ->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'creator', $this->creator])
            ->andFilterWhere(['like', 'insTime', $this->insTime])
            ->andFilterWhere(['like', 'updTime', $this->updTime])
            ->andFilterWhere(['like', 'modifier', $this->modifier]);

        return $query;
    }
 
    /**
     * 取得修改者與新增者名字
     *
     * @param  mixed $query
     * @param  mixed $type 投票類型
     * @param  bool $decryptPlaintext 匿名投票是否顯示密碼明文（匯出／解鎖後列表）
     * @return void
     */
    public function getSysidByBallots($query, $type, $decryptPlaintext = false)
    {
        $q = clone $query;
        $creator = ArrayHelper::getColumn($q->select('DISTINCT `creator` as `creator`')->all(), 'creator');
        $modifier = ArrayHelper::getColumn($q->select('DISTINCT `modifier` as `modifier`')->all(), 'modifier');

        if($type == Votes::TYPE_ANON)
        {
            $FormPasswords = new FormPasswords;
            $passwdAry = $FormPasswords->getPasswdInfo($creator, $decryptPlaintext);
            $ary = ArrayHelper::merge(
                ['' => '無'],
                ArrayHelper::map($passwdAry ?: [], 'id', static function ($row) {
                    return (string) ($row['passwd'] ?? '');
                })
            );
            return ArrayHelper::merge(
                $ary,
                ArrayHelper::map(
                    Users::find()->where(['cn' => $modifier])->asArray()->all(),
                    'cn', 'name'
                )
            );
        }
        return null;
    }

    /**
     * 取得選票
     */
    public function getVoteBallot($voteID,$ballotID)
    {
        return parent::getVoteBallot($voteID, $ballotID)->one();
    }

    /**
     * 取得選票ID
     */
    public function getBallotId($voteID, $round, $party, $creator)
    {
        $result = parent::getBallotId($voteID, $round, $party, $creator)->one();
        return $result?->ballotID;
    }

    /**
     * 取得選票建立者
     */
    public function getBallotChanger($voteID,$ballotID)
    {
        $result = parent::getVoteBallot($voteID, $ballotID)->one();
        if(is_null($result)) return null;

        $sysids = [];
        if(!empty($result->creator))
            $sysids[] = $result->creator;
        if(!empty($result->modifier))
            $sysids[] = $result->modifier;
        
        return ArrayHelper::merge(
            ['' => '無'],
            array_combine($sysids, $sysids)
        );
    }

    /**
     * 取得新選票
     */
    public function getNewVoteBallot($voteID,$party,$creator,$adminAdd = false)
    {
        $newModel = new self;
        $newModel->party = $party;
        $newModel->ip = Yii::$app->getRequest()->getUserIP();
        $newModel->creator = $creator;
        $newModel->insTime = date('Y-m-d H:i:s');
        if($adminAdd)
        {
            $newModel->isAdminAdd = '1';
        }
        // 新增尚未修改：modifier / updTime 留空，僅 updateBallot 才寫入
        $newModel->modifier = '';
        $newModel->updTime = '0000-00-00 00:00:00';
        return $newModel;
    }

    /**
     * 取得新選票建立者
     */
    public function getNewBallotChanger($sysIds)
    {
        return ArrayHelper::merge(
            ['' => '無'],
            array_combine($sysIds, $sysIds)
        );
    }

    /**
     * 建立選票
     */
    public function creatorBallot($voteID, $postData, $adminAdd=false)
    {
        // 取投票資訊
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);
        if (is_null($voteInfo)) {
            return false;
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $this->setScenario('creator');
            if($adminAdd)
            {
                // 後台新增
                $this->load($postData);
                $this->voteID = $voteID;
                $this->isAdminAdd = '1';
                if ($voteInfo->type == FormVotes::TYPE_NO_AUTH) {
                    $this->party = Parties::DEF_PARTY;
                    $this->creator = self::newNoAuthCreator();
                }
            }
            else
            {
                // 投票頁面新增
                $this->isAdminAdd = '0';
                if($voteInfo->type == FormVotes::TYPE_ANON && !Yii::$app->anon->isGuest)   // 匿名投票
                {
                    $loginData = Yii::$app->anon->identity->getAuthData();
                    if($loginData['voteID'] != $voteID && $loginData['voteID'] != $voteInfo->bindWhichVote) {
                        $transaction->rollBack();
                        return false;
                    }
                    $this->voteID = $voteID;
                    $this->party = $voteInfo->partyOrNot ? $loginData['party'] : Parties::DEF_PARTY;
                    $this->creator = strval($loginData['id']);
                }
                else if($voteInfo->type == FormVotes::TYPE_NO_AUTH)   // 表決投票
                {
                    // 表決投票不分組（FormVotes 驗證規則），也沒有投票者身分
                    $this->voteID = $voteID;
                    $this->party = Parties::DEF_PARTY;
                    $this->creator = self::newNoAuthCreator();
                }
                else
                {
                    $transaction->rollBack();
                    return false;
                }
            }
            $this->round = $voteInfo->round; // 輪次

            // 圈選的候選人須屬於本場次、本輪次，且為投票者組別或共同投票的問題
            $candiData = $this->filterAllowedCandidates(
                $this->voteID, $this->round, $this->party, ArrayHelper::getValue($postData, 'selection', [])
            );
            if (is_null($candiData)) {
                $transaction->rollBack();
                return false;
            }

            $this->insTime = date('Y-m-d H:i:s');
            $this->ip = Yii::$app->getRequest()->getUserIP();
            // 新增尚未修改：不寫入修改者／修改時間（僅 updateBallot 才寫入）
            $this->modifier = '';
            $this->updTime = '0000-00-00 00:00:00';
            $newStatus = $this->save();
            Yii::debug($this->errors, __METHOD__);
            if (!$newStatus) {
                $transaction->rollBack();
                return false;
            }

            if($candiData !== [])
            {
                // 更新選票所有選擇的候選人
                $ballotsSelected = new FormBallotsSelected;
                if($adminAdd)
                    $ballotsSelected->deleteBallot($this->voteID, $this->ballotID);// 清除該選票所有選擇的候選人
                if (!$ballotsSelected->createBallotSelected($this->voteID, $this->ballotID, $candiData)) {
                    $transaction->rollBack();
                    return false;
                }
            }

            if($voteInfo->type == FormVotes::TYPE_ANON && $voteInfo->isBindVote != 1) // 匿名投票且不共用密碼
            {
                $FormPasswords = new FormPasswords;
                $FormPasswords->setVote($this->creator); // 修改投票密碼狀態
            }

            $transaction->commit();
            return true;
        } catch(\Throwable $e) {
            $transaction->rollBack();
            Yii::error(
                \yii\helpers\VarDumper::dumpAsString(
                    $e->getMessage(), $depth=10, $highlight=false
                ),
                __METHOD__
            );
            return false;
        }
    }

    /**
     * 表決投票的選票識別碼：每張選票不重複，與投票者無關聯
     */
    public static function newNoAuthCreator(): string
    {
        return 'n' . bin2hex(random_bytes(12));
    }

    /**
     * 檢查圈選的候選人，並回傳寫入 ballotsSelected 所需的候選人資料
     *
     * 每一個 id 都必須是本場次的候選人，且其問題屬於指定輪次、指定組別或共同投票（N）。
     *
     * @param string $voteID
     * @param int|string $round
     * @param string $party 投票者組別
     * @param mixed $selection 送出的候選人 id 清單
     * @return array|null 以候選人 id 為 key 的資料；任一筆不符時回傳 null
     */
    public function filterAllowedCandidates($voteID, $round, $party, $selection): ?array
    {
        if (!is_array($selection)) {
            return null;
        }
        $ids = [];
        foreach ($selection as $id) {
            if (!is_scalar($id) || !ctype_digit((string) $id)) {
                Yii::warning("ballot {$voteID}: invalid candidate id", __METHOD__);
                return null;
            }
            $ids[(int) $id] = (int) $id;
        }
        if ($ids === []) {
            return [];
        }

        $candiData = ArrayHelper::map(
            (new FormCandiData)->getMultipleCandiData($voteID, array_values($ids)) ?: [],
            'id', 'attributes'
        );
        $allowedQuestions = array_map('intval', Questions::find()
            ->select('questionID')
            ->where(['voteID' => $voteID, 'round' => $round, 'party' => [$party, Questions::ALL_PARTY_CODE]])
            ->column());

        foreach ($ids as $id) {
            if (!isset($candiData[$id]) || !in_array((int) $candiData[$id]['questionID'], $allowedQuestions, true)) {
                Yii::warning("ballot {$voteID}: candidate {$id} is not allowed for round {$round} party {$party}", __METHOD__);
                return null;
            }
        }
        return $candiData;
    }

    /**
     * 更新選票
     *
     * @return bool|null 選票不存在回傳 null；圈選不合法或寫入失敗回傳 false（不變更任何資料）
     */
    public function updateBallot($voteID,$ballotID,$postData)
    {
        // 取得選票當事者的設定
        $ballotData = parent::findOne(compact('voteID', 'ballotID'));
        if(is_null($ballotData)) return null;

        // 圈選的候選人須屬於該選票的輪次與組別
        $candiData = $this->filterAllowedCandidates(
            $voteID, $ballotData->round, $ballotData->party, ArrayHelper::getValue($postData, 'selection', [])
        );
        if (is_null($candiData)) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $ballotData->modifier = Yii::$app->user->identity->getId();
            $ballotData->updTime = date('Y-m-d H:i:s');
            if (!$ballotData->save()) {
                throw new \RuntimeException(json_encode($ballotData->errors, JSON_UNESCAPED_UNICODE));
            }

            // 清除該選票之前選擇的候選人，再寫入新的圈選
            $ballotsSelected = new FormBallotsSelected;
            $ballotsSelected->deleteBallot($voteID, $ballotID);
            if ($candiData !== [] && !$ballotsSelected->createBallotSelected($voteID, $ballotID, $candiData, $ballotData->round)) {
                throw new \RuntimeException('ballotsSelected write failed');
            }

            // 取投票資訊
            $FormVotes = new FormVotes;
            $voteInfo = $FormVotes->getVoteInfo($voteID);
            if($voteInfo->type == Votes::TYPE_ANON) // 匿名投票
            {
                $FormPasswords = new FormPasswords;
                $FormPasswords->setVote($ballotData->creator); // 修改投票密碼狀態
            }

            $transaction->commit();
            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error("ballot {$voteID}/{$ballotID} update failed: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * 刪除選票
     */
    public function deleteBallot($voteID, $ballotID)
    {
        // 取得選票當事者的設定
        $ballotData = parent::find()
            ->where(['AND', 'voteID = :voteID', 'ballotID = :ballotID'])
            ->addParams([':voteID' => $voteID, ':ballotID' => $ballotID])
            ->one();

        if(is_null($ballotData)) return null;
        $ballotData->delete();

        // 取投票資訊
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);
        if($voteInfo->type == Votes::TYPE_ANON) // 匿名投票
        {
            $FormPasswords = new FormPasswords;
            $FormPasswords->setVote($ballotData->creator, '0'); // 修改投票密碼狀態
        }
        
        // 清除該選票所有選擇的候選人
        $ballotsSelected = new FormBallotsSelected;
        return $ballotsSelected->deleteBallot($voteID, $ballotID);
    }

    /**
     * 刪除選票
     */
    public function deleteAllBallot($voteID, $round, $party = null)
    {
        // 取得選票及圈選候選人的資料
        parent::deleteAllBallot($voteID, $round, $party);

        // 取投票資訊
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);
        if($voteInfo->type == Votes::TYPE_ANON && $voteInfo->round == 1) // 匿名投票，修改投票密碼狀態
        {
            if (is_null($party)) {
                FormPasswords::updateAll(['voted' => '0'], ['voteID' => $voteID]);
            }
            else {
                FormPasswords::updateAll(['voted' => '0'], ['voteID' => $voteID, 'party' => $party]); 
            }
        }

        return true;
    }
    
    /**
     * 選票匯出
     *
     * @param  string $voteID
     * @param  int|null $type 匯出類型 (1: 無IP、修改者、修改時間)
     * @return void
     */
    public function export($voteID, $type=null)
    {
        // 取投票資訊
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);
        $parties = $FormVotes->getVoteParty($voteID);
        // 問題
        $voteAllQuestions = (new Questions())->getQuestionsInfo($voteID, $voteInfo->round);
        $questionsGroupByParty = ArrayHelper::toArray(ArrayHelper::index($voteAllQuestions, 'questionID', 'party'), [
            'app\models\Questions' => [
                'title'
            ]
        ]) ;
        
        // 投票者資訊（紙本封存需明文密碼）
        $sysidList = $this->getSysidByBallots(
            $this->getBallotList($voteID, $voteInfo->round),
            $voteInfo->type,
            $voteInfo->type == Votes::TYPE_ANON
        );
        // 選票資訊
        $ballots = $this->getBallotContent($voteID, $voteInfo->round);
        $ballots = ArrayHelper::index($ballots, null, 'ballotID');

        $objPHPExcel = new Spreadsheet();
        $objPHPExcel->setActiveSheetIndex(0);

        // 表頭設定
        $sheet = $objPHPExcel->getActiveSheet();
        $this->writeExportRow($sheet, 1, $this->exportHeaders($type));

        $i = 2;
        foreach ($ballots as $ballotID => $candi) {
            // 將候選人用問題group
            $candiGroupByQuestion = ArrayHelper::index($candi, null, 'questionID');
            
            $j = $i;
            $ballotHasQuestions = [];

            if (isset($questionsGroupByParty[Questions::ALL_PARTY_CODE])) {
                $ballotHasQuestions += $questionsGroupByParty[Questions::ALL_PARTY_CODE];
            }
            else {
                $ballotHasQuestions += $questionsGroupByParty[$candi[0]['party']];
            }
            $questionNum = count($ballotHasQuestions);
            // 根據問題列出圈選結果
            foreach ($candiGroupByQuestion as $questionID => $candi) {
                if (!empty($questionID)) {
                    $selCandiName = [];
                    foreach ($candi as $value) {
                        $selCandiName[] = $value['Name'];
                    }
                    $this->writeExportRow($sheet, $j, $this->exportRowValues(
                        $type,
                        $ballotID,
                        $candi[0],
                        $sysidList,
                        $parties,
                        $ballotHasQuestions[$questionID]['title'],
                        implode("、", $selCandiName)
                    ));
                    
                    // 刪除有圈選的問題
                    unset($ballotHasQuestions[$questionID]);
                    $j++;
                }
            }
            // 列出未圈選的問題
            if (count($ballotHasQuestions) > 0) {
                foreach ($ballotHasQuestions as $questionID => $title) {
                    $this->writeExportRow($sheet, $j, $this->exportRowValues(
                        $type,
                        $ballotID,
                        $candi[0],
                        $sysidList,
                        $parties,
                        $title['title'],
                        ''
                    ));
                    
                    $j++;
                }
            }
            $i += $questionNum;
        }
        // 設定檔名及檔案路徑
        $date = date('YmdHis');
        $fileName = str_replace('/', '_', "{$voteInfo->Name}_選票結果_{$date}.csv");
        $file = Yii::getAlias('@app')."/runtime/{$fileName}";

        // 使用CSV格式儲存檔案
        $objWriter = new Csv($objPHPExcel);
        $objWriter->setUseBOM(true);
        $objWriter->save($file);
        
        // 檔案傳送到客戶端後刪除暫存（操作日誌由 BallotController::actionExport 記錄）
        return \Yii::$app->response->sendFile($file, $fileName, ['mimeType' => 'text/csv'])
            ->on(\yii\web\Response::EVENT_AFTER_SEND, function ($event) {
                unlink($event->data);
            }, $file);
    }
    

    /**
     * 選票統計匯出表頭（含代為輸入）
     *
     * @param mixed $type
     * @return list<string>
     */
    public function exportHeaders($type = null): array
    {
        if ($type == '1') {
            return ['選票編號', '投票者', '投票者組別', '代為輸入', '投票時間', '', '是否有效', '圈選結果'];
        }

        return ['選票編號', '投票者', '投票者組別', '代為輸入', '投票者IP', '投票時間', '修改者', '修改時間', '', '是否有效', '圈選結果'];
    }

    /**
     * @param mixed $type
     * @param mixed $ballotID
     * @param array<string, mixed> $row
     * @param array<string, mixed>|null $sysidList
     * @param array<string, mixed> $parties
     * @return list<mixed>
     */
    protected function exportRowValues($type, $ballotID, array $row, $sysidList, array $parties, $questionTitle, $selection): array
    {
        $sysidList = is_array($sysidList) ? $sysidList : [];
        $adminAdd = Ballots::isAdminAddLabel($row['isAdminAdd'] ?? null);
        $voter = $sysidList[$row['creator'] ?? ''] ?? '';
        $party = $parties[$row['party'] ?? ''] ?? '';
        $valid = Yii::$app->params['ct.ballots.selectNum'][$row['isValiable'] ?? ''] ?? '';
        if ($type == '1') {
            return [$ballotID, $voter, $party, $adminAdd, $row['insTime'] ?? '', $questionTitle, $valid, $selection];
        }

        return [
            $ballotID,
            $voter,
            $party,
            $adminAdd,
            $row['ip'] ?? '',
            $row['insTime'] ?? '',
            $sysidList[$row['modifier'] ?? ''] ?? '',
            $row['updTime'] ?? '',
            $questionTitle,
            $valid,
            $selection,
        ];
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param list<mixed> $values
     */
    protected function writeExportRow($sheet, int $rowNum, array $values): void
    {
        $col = 1;
        foreach ($values as $value) {
            $sheet->setCellValue(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $rowNum,
                $value
            );
            $col++;
        }
    }

    /**
     * 選票匯入
     *
     * @param  \app\models\FormVotes $voteInfo
     * @param  array $questionIDs
     * @param  array $postData
     * @return int
     */
    public function importBallots($voteInfo, $questionIDs, $postData): int
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 匯入的場次
            $sessionA = $postData[self::formName()]['voteID'];
            // 被匯入的場次
            $sessionB = $voteInfo->voteID;

            // Step 1: 從匯入的場次取得所有選票
            $ballotsA = self::find()
                ->where(['voteID' => $sessionA, 'round' => $voteInfo->round])
                ->indexBy('ballotID')
                ->all();
            $totalBallots = count($ballotsA);

            // Step 2: 建立場次A及場次B的問題對應表
            $questionMapping = array_combine(
                $postData[self::formName()]['questionID'],
                $questionIDs
            );

            // Step 3: 建立場次A及場次B的候選人對應表
            $candidateMapping = [];
            $candiDataA = CandiData::find()->select(['id', 'questionID', 'orderNum'])
                ->where(['voteID' => $sessionA, 'questionID' => array_keys($questionMapping)])
                ->asArray()->all();
            $candiDataA = ArrayHelper::index($candiDataA, 'orderNum', 'questionID');
            $candiDataB = CandiData::find()->select(['id', 'questionID', 'orderNum'])
                ->where(['voteID' => $sessionB, 'questionID' => array_values($questionMapping)])
                ->asArray()->all();
            $candiDataB = ArrayHelper::index($candiDataB, 'orderNum', 'questionID');

            foreach ($candiDataA as $questionID => $candiA) {
                $candiB = $candiDataB[$questionMapping[$questionID]] ?? [];
                ksort($candiA);
                ksort($candiB);
                foreach ($candiA as $orderNum => $candi) {
                    $candidateMapping[$candi['id']] = $candiB[$orderNum]['id'] ?? null;
                }
            }

            // Step 4: 從場次A(匯入的場次)複製選票及選擇的候選人到場次B(被匯入的場次)
            foreach ($ballotsA as $ballotA) {
                // 複製選票
                $attributes = $ballotA->attributes;
                unset($attributes['ballotID']);
                $newBallot = self::findOne([
                    'voteID' => $sessionB,
                    'round' => $ballotA->round,
                    'party' => $ballotA->party,
                    'creator' => $ballotA->creator
                ]);

                if ($newBallot !== null) {
                    $totalBallots--;
                    continue;
                }

                $newBallot = new self;
                $newBallot->setScenario('creator');
                $newBallot->setAttributes($attributes);
                $newBallot->voteID = $sessionB;
                $newBallot->isNewRecord = true;

                if (!$newBallot->save()) {
                    foreach ($newBallot->errors as $message) {
                        Yii::$app->session->addFlash('error', $message[0]);
                    }
                    $transaction->rollBack();
                    return 0;
                }

                // 複製選擇的候選人
                $ballotsSelected = ArrayHelper::toArray($ballotA->ballotsSelected, [
                    FormBallotsSelected::class => [
                        'voteID',
                        'id' => function ($ballotSelected) use ($candidateMapping) {
                            return $candidateMapping[$ballotSelected->selCandiID] ?? null;
                        },
                        'party',
                        'questionID' => function ($ballotSelected) use ($questionMapping) {
                            return $questionMapping[$ballotSelected->questionID] ?? null;
                        },
                        'jobLctn'
                    ],
                ]);

                $status = (new FormBallotsSelected)->createBallotSelected(
                    $sessionB, $newBallot->ballotID, $ballotsSelected
                );

                if (!$status) {
                    $transaction->rollBack();
                    return 0;
                }
            }
            $transaction->commit();
            // 完成訊息: 共匯入了幾張選票
            return $totalBallots;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
