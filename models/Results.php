<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "results".
 *
 * @property string $voteID 投票編號
 * @property int $candID 候選人編號
 * @property string $party 分組
 * @property int $ballotCounts 總得票數
 * @property string $elected 當選與否
 * @property int $rank 得票排序
 * @property string $comment 備註
 */
class Results extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'results';
    }

    public static function tbnField($field)
    {
        return self::tableName().'.'.$field;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['voteID', 'candID', 'party', 'jobLctn', 'ballotCounts', 'elected', 'rank', 'comment'], 'required'],
            [['candID', 'ballotCounts', 'rank'], 'integer'],
            [['voteID'], 'string', 'max' => 20],
            [['party'], 'string', 'max' => 12],
            [['jobLctn'], 'string', 'max' => 1],
            [['elected'], 'string', 'max' => 2],
            [['comment'], 'string', 'max' => 500],
            [['voteID', 'candID'], 'unique', 'targetAttribute' => ['voteID', 'candID']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'voteID' => '投票編號',
            'candID' => '候選人編號',
            'party' => '分組',
            'jobLctn' => '國內外',
            'ballotCounts' => '總得票數',
            'elected' => '當選與否',
            'rank' => '得票排序',
            'comment' => '備註',
        ];
    }

    /**
     * 取得候選人資訊
     */
    public function getCandiData()
    {
        return $this->hasOne(CandiData::className(), ['id' => 'candID']);
    }
    
    /**
     * 刪除投票結果
     * TODO: 結果多輪次
     */
    public function deleteResultsAll($voteID, $round, $party = null)
    {
        if(is_null($party))
        {
            return self::deleteAll([
                'voteID' => $voteID,
                // 'round' => $round
            ]);
        }
        return self::deleteAll([
            'voteID' => $voteID,
            // 'round' => $round,
            'party' => $party,
        ]);
    }
}
