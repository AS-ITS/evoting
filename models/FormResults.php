<?php
namespace app\models;

use Yii;
use app\components\Model;

class FormResults extends Results
{
    /**
     * Returns the validation rules for attributes.
     * 
     * @return array validation rules
     */
    public function rules()
    {
        return [
            [['candID', 'ballotCounts', 'rank'], 'integer'],
            [['voteID'], 'string', 'max' => 20],
            [['party'], 'string', 'max' => 12],
            [['elected'], 'string', 'max' => 2],
            [['jobLctn'], 'string', 'max' => 1],
            [['comment'], 'string', 'max' => 500],
            [['voteID', 'candID', 'party', 'jobLctn', 'ballotCounts', 'elected', 'rank'], 'required', 'on'=>'create'],
            [['voteID', 'candID', 'party', 'jobLctn', 'ballotCounts', 'elected', 'rank', 'comment'], 'safe', 'on'=>'create'],
            [['elected', 'rank'], 'required', 'on'=>'update'],
            [['elected', 'rank'], 'safe', 'on'=>'update'],
            [['party', 'questionID', 'elected'], 'safe', 'on'=>'search'],
            // [['voteID', 'candID'], 'unique', 'targetAttribute' => ['voteID', 'candID']],
        ];
    }

    /**
     * 批量新增資料
     */
    public function createResults($voteID)
    {
        
        $manageCount = new FormManageCount($voteID);
        $ballotCountSort = $manageCount->getBallotCountSort(true);
        $transaction = self::getDb()->beginTransaction();
        
        // 組別
        foreach($ballotCountSort as $party => $questions)
        {
            // 問題
            foreach ($questions as $questionID => $questionData) {
                // 每筆
                foreach($questionData['ranking'] as $rank)
                {
                    $newModel = new self;
                    $newModel->scenario='create';
                    $newModel->voteID = "{$voteID}";
                    $newModel->candID = intval($rank['candi']);
                    $newModel->party = "{$party}";
                    $newModel->questionID = "{$questionID}";
                    $newModel->jobLctn = "0";
                    $newModel->ballotCounts = intval($rank['count']);
                    $newModel->elected = "{$rank['elect']}";
                    $newModel->rank = intval($rank['rank']);
                    $newModel->comment = $rank['femaleKeep'] ? '女性保留名額' : '';
                    $newModel->save();
                }
            }
        }
        try {
            $transaction->commit();// 批量新增資料
        } catch(\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->addFlash('error', "[Exception] 結果匯入失敗: $e");
            return false;
        } catch(\Throwable $e) {
            $transaction->rollBack();
            Yii::$app->session->addFlash('error', "[Throwable] 結果匯入失敗: $e");
            return false;
        }
        return true;
    }

    /**
     * 刪除所有資料
     */
    public function deleteResults($voteID, $round)
    {
        $questionsID = (new Questions())->getQuestionsIdList($voteID, $round);
        $deleteNum = self::deleteAll([
            'voteID' => $voteID,
            'questionID' => $questionsID
        ]);
        return $deleteNum > 0;
    }

    /**
     * 是否有資料
     */
    public function isResults($voteID, $round)
    {
        $questionsID = (new Questions())->getQuestionsIdList($voteID, $round);
        return (
            self::find()
                ->where(['voteID' => $voteID, 'questionID' => $questionsID])
                ->count() > 0
        );
    }
   
    /**
     * 使用voteID取得投票結果資料
     *
     * @param  string $voteID
     * @param  object $config
     * @param  bool $manage 是否為後台管理
     * @return \yii\db\ActiveQuery
     */
    public function search($voteID, $round, $config, $party=true, $status=true, $params=[])
    {
        $this->setScenario('search');// 設置模型的方案
        $questionsID = (new Questions())->getQuestionsIdList($voteID, $round);
        $query = self::find()
            ->where([
                Results::tbnField('voteID') => $voteID,
                Results::tbnField('questionID') => $questionsID,
            ])
            ->joinWith('candiData');

        // 是否針對登入分組顯示
        if ($config->isParty && $party) {
            $formManageVote = new FormManageVote($voteID);
            $query->andWhere(['in', Results::tbnField('party'), [Questions::ALL_PARTY_CODE, $formManageVote->getPartyCode()]]);
        }
        
        // 顯示結果投票狀態
        if (!empty($config->showElectedStatus) && $status) {
            $showElectedStatus = explode(',', $config->showElectedStatus);
            $query->andWhere(['in', Results::tbnField('elected'), $showElectedStatus]);
        }

        $query->orderby([
            Results::tbnField('party') => SORT_ASC,
            Results::tbnField('questionID') => SORT_ASC,
        ]);
        // 依照投票結果排序
        $query->addOrderBy([new \yii\db\Expression('FIELD (elected, "'.implode('","', array_keys(Yii::$app->params['ct.result.electedSortAry'])).'")')]);
        // 依照得票數排序
        switch ($config->sort) {
            case 'N':
                $query->addOrderBy([
                    Results::tbnField('rank') => SORT_ASC,
                    Results::tbnField('ballotCounts') => SORT_DESC,
                ]);
            case 'L':
                $query->addOrderBy([
                    'CONVERT(substr(candiData.Name, 1, 1) USING big5)' => SORT_ASC,
                ]);
                break;
        }

        // 行內搜尋
        $params = Model::trimParams($params, $this->className());
        $this->load($params);
        $query->andFilterWhere(['=', Results::tbnField('party'), $this->party])
              ->andFilterWhere(['=', Results::tbnField('questionID'), $this->questionID])
              ->andFilterWhere(['=', Results::tbnField('elected'), $this->elected]);

        return $query;
    }

    /**
     * 使用voteID取得投票結果DataProvider資料
     *
     * @param  string $voteID
     * @param  object $config
     * @param  bool $view
     * @return \yii\data\ActiveDataProvider
     */
    public function getDataProvider($voteID, $round, $config, $party=true, $status=true, $params = [])
    {
        $DataProvider = new DataProvider;
        return $DataProvider->getBasicDataProvider(
            $this->search($voteID, $round, $config, $party, $status, $params), false
        );
    }

    /**
     * 刪除投票結果
     */
    public function deleteResultsAll($voteID, $round, $party = null)
    {
        if(is_null($party))
        {
            return parent::deleteResultsAll($voteID, $round);
        }
        return parent::deleteResultsAll($voteID, $round, $party);
    }
}
