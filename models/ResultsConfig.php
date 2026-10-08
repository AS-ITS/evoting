<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "resultsConfig".
 *
 * @property string $voteID 投票編號
 * @property string $isShow 是否顯示於投票結果
 * @property string $isLogin 是否需要登入查看投票結果
 * @property string $isParty 是否分組查看投票
 * @property string $sort 排序方式(姓氏筆劃、得票高低)
 * @property string $showFieldSort 欄位順序
 * @property null|string $showElectedStatus 顯示結果投票狀態
 */
class ResultsConfig extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'resultsConfig';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['voteID', 'isShow', 'isLogin', 'isParty', 'sort', 'showFieldSort'], 'required'],
            [['voteID', 'showElectedStatus'], 'string', 'max' => 20],
            [['isShow', 'isLogin', 'isParty', 'sort'], 'string', 'max' => 1],
            [['showFieldSort'], 'string', 'max' => 1000],
            [['voteID'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'voteID' => '投票編號',
            'isShow' => '是否顯示於投票結果',
            'isLogin' => '是否需要登入查看投票結果',
            'isParty' => '是否分組查看投票',
            'sort' => '排序方式(姓氏筆劃、得票高低)',
            'showFieldSort' => '欄位順序',
            'showElectedStatus' => '投票結果顯示投票狀態',
        ];
    }

    /**
     * 刪除投票結果(配置)
     */
    public function deleteResultsConfigAll($voteID, $party = null)
    {
        if(is_null($party))
        {
            return self::deleteAll([
                'voteID' => $voteID
            ]);
        }
        return self::deleteAll([
            'voteID' => $voteID,
            'party' => $party,
        ]);
    }
}
