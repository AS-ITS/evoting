<?php

namespace app\models;

/**
 * This is the model class for table "ballotsSelected".
 *
 * @property string $voteID
 * @property int $ballotID
 * @property int $selCandiID selectedCandidateID
 * @property string $party whichParty
 * @property string $jobLctn
 * @property string $isValiable 是否為有效票
 */
class BallotsSelected extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ballotsSelected';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['voteID', 'ballotID'], 'required'],
            [['ballotID', 'selCandiID'], 'integer'],
            [['voteID'], 'string', 'max' => 20],
            [['party'], 'string', 'max' => 12],
            [['jobLctn', 'isValiable'], 'string', 'max' => 1],
            [['voteID', 'ballotID', 'selCandiID'], 'unique', 'targetAttribute' => ['voteID', 'ballotID', 'selCandiID']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'voteID' => 'Vote ID',
            'ballotID' => 'Ballot ID',
            'selCandiID' => '選擇的候選人',
            'party' => '組別',
            'jobLctn' => '工作地點',
            'isValiable' => '是否為有效票',
        ];
    }

    /**
     * 取得選票候選人資訊
     */
    public function getCandiData()
    {
        return $this->hasOne( CandiData::className(), ['id' => 'selCandiID']);
    }

    /**
     * 取得選票
     */
    public function getVoteSelected($voteID, $round)
    {
        $questionsID = (new Questions())->getQuestionsIdList($voteID, $round);
        return self::find()->where([
            'voteID'   => $voteID,
            'questionID'   => $questionsID,
        ]);
    }

    /**
     * 取得選票
     */
    public function getSelected()
    {
        return self::find();
    }

    /**
     * 取得投票中特定選票
     */
    public function getVoteBallot($voteID,$ballotID)
    {
        return self::find()->where([
            'voteID'   => $voteID,
            'ballotID' => $ballotID,
        ]);
    }

    /**
     * 清除選票選擇的候選人
     */
    public function daleteBallot($voteID,$ballotID)
    {
        return self::deleteAll([
            'voteID'   => $voteID,
            'ballotID' => $ballotID,
        ]);
    }

    /**
     * 清除投票選擇的候選人
     */
    public function deleteAllBallot($voteID, $round, $party=null)
    {
        $questions = (new Questions)->getQuestionsIdList($voteID, $round);
        if(is_null($party))
        {
            return self::deleteAll([
                'voteID' => $voteID,
                'questionID' => $questions,
            ]);
        }
        return self::deleteAll([
            'voteID' => $voteID,
            'party' => $party,
            'questionID' => $questions,
        ]);
    }

    /**
     * Dynamic Relational Query with Ballots
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBallot()
    {
        return $this->hasOne(Ballots::className(), ['voteID' => 'voteID', 'ballotID' => 'ballotID']);
    }

    /**
     * Dynamic Relational Query with Ballots
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuestion()
    {
        return $this->hasOne(Questions::className(), ['voteID' => 'voteID', 'questionID' => 'questionID']);
    }
}
