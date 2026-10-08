<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "candiData".
 *
 * @property int $id 自動編號
 * @property string $voteID
 * @property string $party 分組
 * @property int $questionID 問題ID
 * @property string $isReachThreshold 是否達門檻
 * @property string $jobLctn 工作地點
 * @property string $instCode 單位代碼
 * @property string $tCode 職稱代碼
 * @property string $instName 單位名稱
 * @property string $instNameE
 * @property string $title 職稱名稱
 * @property string $titleE
 * @property string $Name 名字/名稱
 * @property string $NameE
 * @property string $sex 性別
 * @property int $orderNum 自訂排序
 * @property string $otherColA 自定義欄位1
 * @property string $otherColAE
 * @property string $otherColB 自定義欄位2
 * @property string $otherColBE
 * @property string $otherColC 自定義欄位3
 * @property string $otherColCE
 * @property string $otherColD 自定義欄位4
 * @property string $otherColDE
 * @property string $genMode 生成方式
 * @property string $other 保留
 * @property string|object $photo 照片
 * @property string $backgroundColor 背景顏色
 * @property string $relateParty 關聯組別，給共同問題使用的，用於取得分組的人數
 * @property string $specialHonor 特殊榮譽
 */
class CandiData extends \yii\db\ActiveRecord
{
    /** 已達門檻 */
    const REACH_THRESHOLD = '1';
    /** 特殊榮譽 */
    const SPECIAL_HONOR = '1';

    /**
     * 要導入的組別
     */
    public $importParty;
    /**
     * 要導入的問題ID
     */
    public $importQuestionID;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candiData';
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
            [['voteID', 'party', 'questionID', 'instCode', 'Name', 'orderNum', 'genMode'], 'required'],
            [['orderNum','sysId', 'questionID'], 'integer'],
            [['other'], 'string'],
            [['voteID'], 'string', 'max' => 20],
            [['party', 'relateParty'], 'string', 'max' => 12],
            [['jobLctn', 'sex', 'isReachThreshold', 'specialHonor'], 'string', 'max' => 1],
            [['instCode'], 'string', 'max' => 2],
            [['tCode'], 'string', 'max' => 3],
            [['genMode'], 'string', 'max' => 10],
            [['backgroundColor'], 'string', 'max' => 30],
            [['instName', 'title', 'Name', 'otherColA', 'otherColB', 'otherColC', 'otherColD', 'otherColE', 'otherColF'], 'string', 'max' => 50],
            [['instNameE', 'titleE', 'NameE'], 'string', 'max' => 100],
            [['otherColAE', 'otherColBE', 'otherColCE', 'otherColDE', 'otherColEE', 'otherColFE'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '自動編號',
            'voteID' => 'Vote ID',
            'party' => '分組',
            'questionID' => '問題',
            'isReachThreshold' => '是否達門檻',
            'jobLctn' => '工作地點',
            'instCode' => '單位代碼',
            'tCode' => '職稱代碼',
            'instName' => '單位',
            'instNameE' => '單位(英文)',
            'title' => '職稱',
            'titleE' => '職稱(英文)',
            'Name' => '名字/名稱',
            'NameE' => '名字/名稱(英文)',
            'sex' => '性別',
            'sysId' => '識別碼',
            'orderNum' => '自訂排序',
            'otherColA' => '自定義欄位1',
            'otherColAE' => '自定義欄位1(英文)',
            'otherColB' => '自定義欄位2',
            'otherColBE' => '自定義欄位2(英文)',
            'otherColC' => '自定義欄位3',
            'otherColCE' => '自定義欄位3(英文)',
            'otherColD' => '自定義欄位4',
            'otherColDE' => '自定義欄位4(英文)',
            'otherColE' => '自定義欄位 5',
            'otherColEE' => '自定義欄位 5(英文)',
            'otherColF' => '自定義欄位 6',
            'otherColFE' => '自定義欄位 6(英文)',
            'genMode' => '生成方式',
            'other' => '保留',
            'photo' => '照片',
            'backgroundColor' => '背景顏色',
            'relateParty' => '關聯組別，給共同問題使用的，用於取得分組的人數',
            'specialHonor' => '特殊榮譽',
            'importParty' => '導入組別',
            'importQuestionID' => '導入問題',
        ];
    }

    /**
     * 取得模組資料
     */
    public function getModel($id)
    {
        return self::findOne($id);
    }

    /**
     * 取得問題候選人列表
     */
    public function getCandiListWithQuestion($voteID, $questionID = null)
    {
        return self::find()
            ->where([
                'voteID' => $voteID,
                'questionID' => $questionID,
            ])
            ->orderBy('orderNum, questionID, instCode, id');
    }

    /**
     * 取得組別候選人列表
     */
    public function getCandiListWithParty($voteID, $round, $party = null)
    {
        $questionsID = (new Questions())->getQuestionsIdList($voteID, $round);

        return self::find()
            ->where(['voteID' => $voteID, 'questionID' => $questionsID])
            ->andFilterWhere(['party' => $party])
            ->orderBy('orderNum, party, instCode, id');
    }

    /**
     * 取得組別及問題候選人列表
     */
    public function getCandiListWithPartyQuestion($voteID, $party = null, $questionID = null, $includeAllParty = true)
    {
        $query = self::find()
            ->where([
                'voteID' => $voteID,
                'questionID' => $questionID
            ]);

        if ($includeAllParty) {
            $query->andWhere(['in', 'party', [$party, Questions::ALL_PARTY_CODE]]);
        }
        else {
            $query->andWhere(['party' => $party]);
        }
        
        return $query;
    }

    /**
     * 取得投票候選人列表
     */
    public function getCandiListWithVoteID($voteID)
    {
        return self::find()->where([
            self::tableName().'.voteID' => $voteID,
        ]);
    }

    /**
     * 取得多個候選人
     */
    public function getMultipleCandiData($voteID,$ids)
    {
        return self::find()->where([
            'and', 
            ['=', 'voteID', $voteID],
            ['in', 'id', $ids]
        ]);
    }

    /**
     * 取得候選人
     */
    public function getCandiDataWithVoteID($voteID,$id)
    {
        return self::find()->where([
            'voteID' => $voteID,
            'id' => $id,
        ]);
    }

    /**
     * 刪除候選人
     */
    public function deleteAllCandiData($voteID)
    {
        return self::deleteAll([
            'voteID' => $voteID,
        ]);
    }

    /**
     * 問題資料
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuestion()
    {
        return $this->hasOne(Questions::className(), ['voteID' => 'voteID', 'questionID' => 'questionID']);
    }
}
