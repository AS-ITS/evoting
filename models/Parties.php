<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "parties".
 *
 * @property string $voteID
 * @property string $party whichParty
 * @property string $name 分組中文名稱
 * @property string $nameE 分組英文名稱
 * @property int $numBallots 可投票數
 * @property int $leastNumBallots 最少應投票數
 * @property int $maxElect
 * @property int $numOfKeep 遞補人數
 * @property int $numFemaleKeep 女性保留人數
 * @property int $numCounting 清點人數
 */
class Parties extends \yii\db\ActiveRecord
{
    /**
     * 預設組別
     */
    const DEF_PARTY = 'def';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parties';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['voteID', 'party', 'numBallots', 'leastNumBallots', 'numOfKeep', 'maxElect'], 'required'],
            [['name', 'nameE'], 'required', 'when' => function($model) {
                return $model->party != self::DEF_PARTY; // 非預設時，分組中英文名稱為必填
            }],
            [['numBallots', 'leastNumBallots', 'maxElect', 'numOfKeep', 'numFemaleKeep', 'numCounting'], 'integer'],
            [['voteID', 'name'], 'string', 'max' => 20],
            [['party'], 'string', 'max' => 12],
            [['nameE'], 'string', 'max' => 100, 'on'=>['update']],
            [['voteID', 'party'], 'unique', 'targetAttribute' => ['voteID', 'party']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'voteID' => 'Vote ID',
            'party' => '組別',
            'name' => '分組中文名稱',
            'nameE' => '分組英文名稱',
            'numBallots' => '最多可投票數',
            'leastNumBallots' => '最少應投票數',
            'maxElect' => '當選人數',
            'numOfKeep' => '遞補人數',
            'numFemaleKeep' => '女性保留人數',
            'numCounting' => '清點人數',
        ];
    }

    /**
     * 取得對應組別投票限制
     */
    public function getPartyInfo($voteID, $party = null)
    {
        $party = is_null($party) ? self::DEF_PARTY : $party;
        return self::find()
            ->where(['voteID' => $voteID, 'party' => $party])
            ->orderBy('party');
    }

    /**
     * 取得該投票所有組別限制
     */
    public function getPartyAll($voteID)
    {
        return self::find()->where(['voteID' => $voteID]);
    }

    /**
     * 取得投票組別及資料
     */
    public function getOneVote($voteID,$party)
    {
        return self::findOne(['voteID' => $voteID, 'party' => $party]);
    }
}
