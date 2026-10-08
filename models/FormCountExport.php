<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Json;
use app\components\helper\ArrayHelper;
use app\components\helper\FileLoader;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class FormCountExport extends Model
{
    /**
     * 匯出各組別有效票、廢票統計及候選人得票排序
     *
     * @param  string $voteID
     * @return void
     */
    public function export($voteID, $sort)
    {
        $FileLoader = new FileLoader(Yii::getAlias('@filePool'));
        $objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $manageCount = new FormManageCount($voteID);
        $candiConfigModel = new CandiConfig();
        $voteInfo = $manageCount->VoteInfo;
        $ballotCountSort = $manageCount->ballotCountSort;
        $partiesAry = $manageCount->getPartyAry();
        $modelParties = Parties::find()->where(['voteID' => $manageCount->voteID])->indexBy('party')->all();
        $questionsAry = $manageCount->getVoteQuestions();
        // 選票設定
        $FormBallots = new FormBallots;
        $ballotList = $FormBallots->getBallotList($voteID, $voteInfo->round)->asArray()->all();
        // 取得選票資訊
        $FormManageVote = new FormManageVote($voteID);
        $ballotCountAry = $FormManageVote->getBallotCount();
        // 密碼
        if ($voteInfo->type == Votes::TYPE_ANON) {
            $passwordList = (new Passwords())->getPasswordListByParty($voteID, $voteInfo);
        }
        
        // 所有組別統計
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setCellValue('A1', '組別')
            ->setCellValue('B1', '問題')
            ->setCellValue('C1', '有效票')
            ->setCellValue('D1', '廢票');
        if ($voteInfo->type == Votes::TYPE_ANON) {
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '尚未投票')->setCellValue('F1', '選票數量');
        }
        $i = 2;
        $j = 2;
        $candiListStartColumnIndex = 7;
        // 所有分組及問題
        foreach ($ballotCountSort as $party => $questions) {
            $candiListStartRowIndex = 2;
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, $partiesAry[$party]);
            foreach ($questions as $questionID => $candiData) {
                // 候選名單設定
                $candiConfig = ($voteInfo->candiConfig == Votes::CANDI_CONFIG_BY_Q) ?
                    $candiConfigModel->getConfigWithVoteID($voteID, $questionID)->one() :
                    $manageCount->getCandiConfig();

                $validCount = [];
                $validCount[$questionID] = ['valid' => 0, 'invalid' => 0];
                // 計算有效票及廢票
                // 全部分組共同問題
                $manageCount->getPasswordAndValidCount($party, $questionID, $validCount, $ballotList, $ballotCountAry, $modelParties, $passwordList, $manageCount->voteQuestions);

                // CSV設定有效票及廢票數值
                $objPHPExcel->getActiveSheet()->setCellValue('B'.$j, strip_tags($questionsAry[$questionID]->title))
                    ->setCellValue('C'.$j, $validCount[$questionID]['valid'])
                    ->setCellValue('D'.$j, $validCount[$questionID]['invalid']);

                // 匿名投票統計選票數量
                if ($party === Questions::ALL_PARTY_CODE) {
                    $passwordCount = $manageCount->getBallotNum($modelParties, $passwordList);
                }
                elseif ($voteInfo->type == Votes::TYPE_ANON) {
                    $passwordCount = $manageCount->getBallotNum($modelParties, $passwordList, $party, false);
                }
                if (isset($passwordCount)) {
                    $notVote = $passwordCount - $validCount[$questionID]['valid'] - $validCount[$questionID]['invalid'];
                    $objPHPExcel->getActiveSheet()->setCellValue('E'.$j, $notVote)->setCellValue('F'.$j, $passwordCount);
                }
                $j++;

                // 各分組候選人得票排序
                $objPHPExcel->getActiveSheet()->setCellValue(Coordinate::stringFromColumnIndex($candiListStartColumnIndex).'1', '組別')
                    ->setCellValue(Coordinate::stringFromColumnIndex($candiListStartColumnIndex+1).'1', '問題')
                    ->setCellValue(Coordinate::stringFromColumnIndex($candiListStartColumnIndex+2).'1', '名字/名稱')
                    ->setCellValue(Coordinate::stringFromColumnIndex($candiListStartColumnIndex+3).'1', '得票數')
                    ->setCellValue(Coordinate::stringFromColumnIndex($candiListStartColumnIndex+4).'1', '得票排序');
                // 排序
                $ranking = $manageCount->sortRanking($candiConfig->num, $candiData['ranking'], $sort);
                // 把已達門檻的候選人放到最後面
                ArrayHelper::multisort($ranking, 'isReachThreshold', SORT_ASC);
                foreach ($ranking as $key => $candi) {
                    $objPHPExcel->getActiveSheet()->setCellValue(Coordinate::stringFromColumnIndex($candiListStartColumnIndex).($key+$candiListStartRowIndex), $partiesAry[$party])
                        ->setCellValue(Coordinate::stringFromColumnIndex($candiListStartColumnIndex+1).($key+$candiListStartRowIndex), strip_tags($questionsAry[$questionID]->title))
                        ->setCellValue(Coordinate::stringFromColumnIndex($candiListStartColumnIndex+2).($key+$candiListStartRowIndex), $candi['Name'])
                        ->setCellValue(Coordinate::stringFromColumnIndex($candiListStartColumnIndex+3).($key+$candiListStartRowIndex), $candi['isReachThreshold'] == CandiData::REACH_THRESHOLD ? '-' : $candi['count'])
                        ->setCellValue(Coordinate::stringFromColumnIndex($candiListStartColumnIndex+4).($key+$candiListStartRowIndex), $candi['isReachThreshold'] == CandiData::REACH_THRESHOLD ? '-' : $candi['rank']);
                }
                $candiListStartRowIndex += count($ranking);
            }
            $candiListStartColumnIndex += 7;
            $i += count($questions);
        }

        // 共同問題統計
        $partiesAryRemoveAllParty = ArrayHelper::forget($partiesAry, Questions::ALL_PARTY_CODE);
        if (!empty($ballotCountSort[Questions::ALL_PARTY_CODE])) {
            $i++;
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, '共同問題')
                ->setCellValue('B'.$i, '投票組別')
                ->setCellValue('C'.$i, '有效票')
                ->setCellValue('D'.$i, '廢票');
            if ($voteInfo->type == Votes::TYPE_ANON) {
                $objPHPExcel->getActiveSheet()->setCellValue('E'.$i, '尚未投票')
                    ->setCellValue('F'.$i, '選票數量');
            }
            $i++;
            $j = $i;
            foreach ($ballotCountSort[Questions::ALL_PARTY_CODE] as $questionID => $candiData) {
                $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, strip_tags($questionsAry[$questionID]->title));
                $validCount = [];
                // 計算有效票及廢票
                foreach ($ballotList as $key => $ballot) {
                    $ballotCounts = $ballotCountAry[$ballot['ballotID']] ?? [];
                    $party = $ballot['party'];
                    if (!isset($validCount[$questionID][$party])) {
                        $validCount[$questionID][$party] = ['valid' => 0, 'invalid' => 0];
                    }
                    if ($manageCount->checkBallotValid($questionID, $ballotCounts, $manageCount->voteQuestions)) {
                        $validCount[$questionID][$party]['valid']++;
                    }
                    else {
                        $validCount[$questionID][$party]['invalid']++;
                    }
                }
                // CSV設定有效票及廢票數值
                foreach ($partiesAryRemoveAllParty as $party => $name) {
                    $valid = isset($validCount[$questionID][$party]['valid']) ? $validCount[$questionID][$party]['valid'] : 0;
                    $invalid = isset($validCount[$questionID][$party]['invalid']) ? $validCount[$questionID][$party]['invalid'] : 0;

                    $objPHPExcel->getActiveSheet()->setCellValue('B'.$j, $name)
                        ->setCellValue('C'.$j, $valid)
                        ->setCellValue('D'.$j, $invalid);

                    if ($voteInfo->type == Votes::TYPE_ANON) {
                        $passwordCount = $manageCount->getBallotNum($modelParties, $passwordList, $party, false);
                        $notVote = $passwordCount - $valid - $invalid;
                        $objPHPExcel->getActiveSheet()->setCellValue('E'.$j, $notVote)
                            ->setCellValue('F'.$j, $passwordCount);
                    }
                    $j++;
                }
                $i += count($partiesAryRemoveAllParty);
            }
        }

        // 設定檔名及檔案路徑
        $sortText = Yii::$app->params['ct.result.sortAry'][$sort];
        $date = date('YmdHis');
        $fileName = str_replace('/', '_', "{$voteInfo->Name}_計票單_{$sortText}_{$date}.csv");
        $file = Yii::getAlias('@app')."/runtime/{$fileName}";

        // 使用CSV格式儲存檔案
        $objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Csv($objPHPExcel);
        $objWriter->setUseBOM(true);             // 加入 UTF-8 BOM
        $objWriter->save($file);
        
        // 檔案傳送到客戶端後Log並刪除
        return \Yii::$app->response->sendFile($file, $fileName, ['mimeType' => 'text/csv'])
            ->on(\yii\web\Response::EVENT_AFTER_SEND, function($event) use ($voteID) {
                Logs::add(Logs::VOTE_COUNT_EXPORT, Json::encode(compact('voteID'), 336));
                unlink($event->data);
            }, $file);
    }
}
