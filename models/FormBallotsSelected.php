<?php
namespace app\models;

use Yii;
use app\components\helper\ArrayHelper;

class FormBallotsSelected extends BallotsSelected
{
    /**
     * Returns the validation rules for attributes.
     * 
     * @return array validation rules
     */
    public function rules()
    {
        return [
            [['voteID', 'ballotID', 'selCandiID', 'party', 'isValiable'], 'required', 'on'=>['search','create']],
            [['ballotID', 'selCandiID'], 'integer', 'on'=>['search','create']],
            [['voteID'], 'string', 'max' => 20, 'on'=>['search','create']],
            [['party'], 'string', 'max' => 12, 'on'=>['search','create']],
            [['jobLctn', 'isValiable'], 'string', 'max' => 1, 'on'=>['search','create']],
            [[
                'voteID', 'ballotID', 'selCandiID', 'party', 'isValiable'
            ], 'safe', 'on'=>'create'],
        ];
    }

    /**
     * 取得投票的所有票含所有組別
     */
    public function getVoteSelected($voteID, $round)
    {
        return BallotsSelected::getVoteSelected($voteID, $round);
    }

    /**
     * 取得選票及候選人資訊
     */
    public function getVoteCandi($voteID, $ballotID)
    {
        $query = BallotsSelected::getSelected();
        $tableName = BallotsSelected::tableName();
        $query
            ->where([
                "{{{$tableName}}}.voteID"   => $voteID,
                "{{{$tableName}}}.ballotID" => $ballotID,
            ])
            ->joinWith('candiData')
            ->orderBy([
                "{{{$tableName}}}.jobLctn" => SORT_ASC,
                "{{{$tableName}}}.party" => SORT_ASC,
                "{{{$tableName}}}.questionID" => SORT_ASC,
                "{{{$tableName}}}.selCandiID" => SORT_ASC,
                "{{{$tableName}}}.ballotID" => SORT_ASC,
            ]);
        return $query;
    }

    /**
     * 取得候選人選票數量及候選人資訊
     */
    public function getValidBallot($voteID, $round, $party=null, $isValiable = '1')
    {
        $questionIDs = (new Questions)->getQuestionsIdList($voteID, $round);
        $query = BallotsSelected::getSelected();

        $query
            ->select('party, selCandiID, questionID, count(ballotID) as ballotID')
            ->where([
                'voteID' => $voteID,
                'questionID' => $questionIDs,
                'isValiable' => $isValiable,
            ])
            ->andFilterWhere(['party' => $party])
            ->groupBy('selCandiID');
        
        return ArrayHelper::index(
            $query->asArray()->all(), null, 'party'
        );
    }

    /**
     * 取得候選人選票數量及候選人資訊
     */
    public function getBallotValidCount($voteID, $round)
    {
        $questionIDs = (new Questions)->getQuestionsIdList($voteID, $round);
        $query = BallotsSelected::getSelected();
        $query
            ->select('party, questionID, isValiable, count(ballotID) as ballotID')
            ->where(['voteID' => $voteID, 'questionID' => $questionIDs])
            ->groupBy('party, questionID, isValiable');
        
        $result = [];
        $data = $query->all();
        
        foreach($data as $d)
        {
            $result[$d->party][$d->questionID][$d->isValiable] = $d->ballotID;
        }
        return $result;
    }

    /**
     * 取得選票及候選人資訊
     */
    public function getVoteBallot($voteID, $ballotID)
    {
        return BallotsSelected::getVoteBallot($voteID,$ballotID);
    }

    /**
     * 取得選票列表
     * 
     * @param string $voteID 投票識別碼
     * @param int $ballotID 選票識別碼
     * 
     * @return array
     */
    public function getBallotSelected( $voteID, $ballotID)
    {
        $ballot = $this->getVoteBallot( $voteID, $ballotID);
        $result = $ballot->select('DISTINCT `selCandiID`')->all();
        $selCandiID = ArrayHelper::getColumn($result,'selCandiID');
        return $selCandiID;
    }

    /**
     * 建立選票
     * 
     * @param string $voteID 投票識別碼
     * @param int $ballotID 選票識別碼
     * @param array $addSelCandi 更改的候選人資料
     */
    public function createBallotSelected($voteID, $ballotID, $addSelCandi = [])
    {
        // 將被圈選候選人用問題分類
        $selCandiQuestion = ArrayHelper::index($addSelCandi, null, 'questionID');
        $selCandiQuestionCount = array_map('count', $selCandiQuestion);
        
        // 取問題列表用問題ID分類
        $formManageCount = new FormManageCount($voteID);
        $questions = $formManageCount->getVoteQuestions();

        // 判斷有效票及廢票
        foreach($selCandiQuestion as $questionID => $selCandi)
        {
            $questionInfo = $questions[$questionID];
            $isValiable[$questionID] = $formManageCount->processNormalRule($questionID, $selCandiQuestionCount, $questions) ? '1' : '0';
        }

        $this->setScenario('create');
        $transaction = BallotsSelected::getDb()->beginTransaction();
        try {
            foreach($addSelCandi as $addSCI)
            {
                $ballot = new BallotsSelected;
                $ballot->voteID = $voteID;
                $ballot->ballotID = $ballotID;
                $ballot->selCandiID = $addSCI['id'];
                $ballot->party = $addSCI['party'];
                $ballot->questionID = $addSCI['questionID'];
                $ballot->jobLctn = $addSCI['jobLctn'];
                $ballot->isValiable = ArrayHelper::getValue($isValiable, $addSCI['questionID'], '0');
                $ballot->save();
            }
            $transaction->commit();// 批量新增資料
        } catch(\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->addFlash('error', "[Exception] 選票更新失敗: $e");
            return false;
        } catch(\Throwable $e) {
            $transaction->rollBack();
            Yii::$app->session->addFlash('error', "[Throwable] 選票更新失敗: $e");
            return false;
        }
        return true;
    }

    /**
     * 刪除選票選擇的候選人
     * 
     * @param string $voteID 投票識別碼
     * @param int $ballotID 選票識別碼
     */
    public function deleteBallot($voteID, $ballotID)
    {
        return BallotsSelected::daleteBallot($voteID, $ballotID);
    }
}
