<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ballots".
 *
 * @property string $voteID
 * @property string $party 組別(whichParty)
 * @property int $ballotID
 * @property string $isAdminAdd 代為輸入（後台依密碼編號代填）
 * @property string $ip
 * @property string $creator
 * @property string $modifier
 * @property string $insTime
 * @property string $updTime
 */
class Ballots extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ballots';
    }

    /**
     * Returns the validation rules for attributes.
     * 
     * @return array validation rules
     */
    public function rules()
    {
        return [
            [['voteID', 'round', 'party', 'ballotID', 'isAdminAdd', 'ip', 'creator', 'modifier', 'insTime', 'updTime'], 'required'],
            [['ballotID'], 'integer'],
            [['insTime', 'updTime'], 'safe'],
            [['voteID'], 'string', 'max' => 20],
            [['party'], 'string', 'max' => 12],
            [['isAdminAdd'], 'string', 'max' => 1],
            [['ip', 'creator', 'modifier'], 'string', 'max' => 50],
            [['voteID', 'party', 'ballotID'], 'unique', 'targetAttribute' => ['voteID', 'party', 'ballotID']],
        ];
    }

    /**
     * 返回指定屬性的文本標籤
     * 
     * @param string $attribute the attribute name
     * @return string the attribute label
     */
    public function attributeLabels()
    {
        return [
            'voteID' => '投票編號',
            'round' => '輪次',
            'ballotID' => '選票編號',
            'party' => '組別', // whichParty
            'jobLctn' => '現職地點', // jobLocation
            'ip' => 'IP',
            'isAdminAdd' => '代為輸入',
            'creator' => '建立者',
            'modifier' => '修改者',
            'insTime' => '新增時間',
            'updTime' => '修改時間',
        ];
    }

    /**
     * 代為輸入顯示（0=否／1=是）
     */
    public static function isAdminAddLabel(?string $value): string
    {
        return Yii::$app->params['ct.yesOrNoAry'][$value ?? ''] ?? '';
    }

    /**
     * 取得選票列表
     */
    public function getBallotList($voteID, $round, $params = [])
    {
        return self::find()->where([
            'voteID'   => $voteID,
            'round'   => $round,
        ]);
    }

    /**
     * 取得選票資訊
     */
    public function getVoteBallot($voteID,$ballotID)
    {
        return self::find()->where([
            'voteID' => $voteID,
            'ballotID' => $ballotID,
        ]);
    }

    /**
     * 取得選票ID
     */
    public function getBallotId($voteID, $round, $party, $creator)
    {
        return self::find()->where([
            'voteID' => $voteID,
            'round' => $round,
            'party' => $party,
            'creator' => $creator,
        ]);
    }

    /**
     * 刪除所有投票的選票資訊
     */
    public function deleteAllBallot($voteID, $round, $party = null)
    {
        $transaction = self::getDb()->beginTransaction();
        try {
            // 取得要刪除的選票
            $ballots = self::find()
                ->where(['voteID' => $voteID, 'round' => $round])
                ->andFilterWhere(['party' => $party])
                ->all();

            // 逐筆刪除選票及其相關的ballotsSelected資料
            foreach ($ballots as $ballot) {
                // 刪除ballotsSelected資料
                BallotsSelected::deleteAll([
                    'voteID' => $ballot->voteID,
                    'ballotID' => $ballot->ballotID
                ]);
                
                // 刪除選票
                $ballot->delete();
            }

            $transaction->commit();
            return true;
        } catch(\Exception $e) {
            $transaction->rollBack();
            return false;
        } catch(\Throwable $e) {
            $transaction->rollBack(); 
            return false;
        }
    }
    
    /**
     * Dynamic Relational Query with BallotsSelected
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBallotsSelected()
    {
        return $this->hasMany(FormBallotsSelected::className(), ['voteID' => 'voteID', 'ballotID' => 'ballotID']);
    }
    
    /**
     * 取得封存單內容
     *
     * @param  string $voteID
     * @param  string|int $round
     * @param  string|null $questionID
     * @return array
     */
    public function getBallotContent($voteID, $round, $questionID=null)
    {
        // 圈選的候選人
        $ballotsSelectedQuery = BallotsSelected::find()
            ->select('
                ballotsSelected.ballotID, 
                ballotsSelected.questionID, 
                ballotsSelected.isValiable, 
                ballotsSelected.selCandiID,
                candiData.Name,
                candiData.id
            ')
            ->leftJoin('candiData', '`ballotsSelected`.`selCandiID` = `candiData`.`id`')
            ->where(['ballotsSelected.voteID' => $voteID]);
            
        $query = Ballots::find()
            ->select('ballots.*, ballotsSelected.questionID, ballotsSelected.isValiable, ballotsSelected.selCandiID, ballotsSelected.Name, ballotsSelected.id')
            ->leftJoin(['ballotsSelected' => $ballotsSelectedQuery], '`ballots`.`ballotID` = `ballotsSelected`.`ballotID`')
            ->where(['ballots.voteID' => $voteID, 'ballots.round' => $round]);
            
        // 如果有指定問題ID，則過濾
        if ($questionID !== null) {
            $query->andFilterWhere(['ballotsSelected.questionID' => $questionID]);
        }
        
        return $query
            ->orderBy(['ballots.ballotID' => SORT_ASC, 'ballotsSelected.id' => SORT_ASC])
            ->createCommand()
            ->queryAll();
    }
    
    /**
     * 如果有分組，取得共同問題各分組投票數
     *
     * @param  array $ballots 每張選票的圈選名單
     * @return array
     */
    public function getPartySelected($ballots)
    {
        $result = [];

        foreach ($ballots as $ballot) {
            $selCandis = $ballot['ballotsSelected'];
            $ballotParty = $ballot['party'];
            if (!empty($selCandis)) {
                foreach ($selCandis as $selCandi) {
                    if (!array_key_exists($selCandi['selCandiID'], $result)) {
                        $result[$selCandi['selCandiID']] = [];
                    }
                
                    if (!array_key_exists($ballotParty, $result[$selCandi['selCandiID']])) {
                        $result[$selCandi['selCandiID']][$ballotParty] = 0;
                    }
                    $result[$selCandi['selCandiID']][$ballotParty]++;
                }
            }
        }

        return $result;
    }
    
    /**
     * 取得每張選票的圈選名單
     *
     * @param  string $voteID
     * @param  string $party
     * @return void
     */
    public function getPartyBallotsDetail($voteID, $party)
    {
        return self::find()
            ->select(['ballots.voteID', 'ballots.ballotID', 'ballots.party'])
            ->joinWith(['ballotsSelected' => function ($query) use ($party) {
                $query
                    ->select(['ballotsSelected.voteID', 'ballotsSelected.ballotID', 'ballotsSelected.selCandiID'])
                    ->onCondition([
                        'ballotsSelected.party' => $party, 
                        'ballotsSelected.isValiable' => '1',
                    ]);
            }])
            ->where(['ballots.voteID' => $voteID])
            ->andWhere(['EXISTS', 
                (new \yii\db\Query())
                    ->from('ballotsSelected')
                    ->where([
                        'ballotsSelected.voteID' => $voteID, 
                        'ballotsSelected.party' => $party, 
                        'ballotsSelected.isValiable' => '1',
                    ])
                    ->andWhere('ballotsSelected.voteID = ballots.voteID AND ballotsSelected.ballotID = ballots.ballotID')
            ])
            ->asArray()->all();
    }
}
