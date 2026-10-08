<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "candiConfig".
 *
 * @property int $candiConfig
 * @property string $voteID
 * @property string $questionID 問題
 * @property string $num 編號顯示方式
 * @property string $Name 名稱顯示
 * @property string $NameE 名稱(英)顯示
 * @property string $NameUnit 名稱的單位
 * @property string $NameUnitE 名稱的單位(英)
 * @property int    $columnNum 投票時候選人顯示列數
 * @property string $useBeforeHeader 使用欄位表頭
 * @property string $width 寬度
 * @property string $fontSize 文字大小
 * @property string $headerColor 表格標頭顏色
 * @property string $showFieldSort 要顯示的欄位，且排序
 * @property string $sort 是否排序
 * @property string $sortDefault 預設排序的欄位
 * @property string $sortColumns 排序的欄位及順序
 * @property string $alignLeft 文字向左對齊欄位
 * @property string $otherColNameA 自定義欄位A名稱
 * @property string $otherColNameAE 自定義欄位A名稱(英文)
 * @property string $otherColNameB 自定義欄位B名稱
 * @property string $otherColNameBE 自定義欄位B名稱(英文)
 * @property string $otherColNameC 自定義欄位C名稱
 * @property string $otherColNameCE 自定義欄位C名稱(英文)
 * @property string $otherColNameD 自定義欄位D名稱
 * @property string $otherColNameDE 自定義欄位D名稱(英文)
 * @property string $otherColNameE 自定義欄位E名稱
 * @property string $otherColNameEE 自定義欄位E名稱(英文)
 * @property string $otherColNameF 自定義欄位F名稱
 * @property string $otherColNameFE 自定義欄位F名稱(英文)
 * @property string $beforeHeaderA 欄位表頭A名稱
 * @property string $beforeHeaderAE 欄位表頭A名稱(英文)
 * @property string $beforeHeaderB 欄位表頭B名稱
 * @property string $beforeHeaderBE 欄位表頭B名稱(英文)
 * @property string $beforeHeaderC 欄位表頭C名稱
 * @property string $beforeHeaderCE 欄位表頭C名稱(英文)
 */
class CandiConfig extends \yii\db\ActiveRecord
{
    /** 寬度: 窄 */
    const WIDTH_NARROW = '1';
    /** 寬度: 寬 */
    const WIDTH_WIDE = '2';
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candiConfig';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['voteID', 'num', 'Name', 'NameE'], 'required'],
            [['num', 'useBeforeHeader', 'width', 'sort'], 'string', 'max' => 1],
            [['columnNum'], 'integer', 'max' => 5],
            [['NameUnit'], 'string', 'max' => 10],
            [['fontSize', 'fontSizeE', 'cellHeight', 'cellHeightE'], 'string', 'max' => 15],
            [['voteID', 'Name'], 'string', 'max' => 20],
            [['NameUnitE'], 'string', 'max' => 30],
            [['sortDefault'], 'string', 'max' => 50],
            [['headerColor'], 'string', 'max' => 50],
            [['NameE'], 'string', 'max' => 100],
            [[
                'voteID', 'otherColNameA', 'otherColNameB', 'otherColNameC', 'otherColNameD', 'otherColNameE', 'otherColNameF',
                'beforeHeaderA', 'beforeHeaderB', 'beforeHeaderC',
            ], 'string', 'max' => 120],
            [[
                'otherColNameAE', 'otherColNameBE', 'otherColNameCE', 'otherColNameDE', 'otherColNameEE', 'otherColNameFE',
                'beforeHeaderAE', 'beforeHeaderBE', 'beforeHeaderCE', 'alignLeft'
            ], 'string', 'max' => 255],
            [['showFieldSort', 'sortColumns'], 'string', 'max' => 1000],
            ['questionID', 'safe'],
            [['voteID', 'questionID'], 'unique', 'targetAttribute' => ['voteID', 'questionID']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'candiConfig' => '流水號',
            'voteID' => 'Vote ID',
            'questionID' => '問題',
            'num' => '編號顯示',
            'Name' => '名稱顯示文字',
            'NameE' => '名稱(英)顯示文字',
            'NameUnit' => '名稱的單位',
            'NameUnitE' => '名稱的單位(英)',
            'columnNum' => '投票時候選名單顯示列數',
            'useBeforeHeader' => '使用欄位表頭',
            'width' => '寬度',
            'fontSize' => '文字大小',
            'fontSizeE' => '文字(英)大小',
            'cellHeight' => '表格高度',
            'cellHeightE' => '表格高度(英)',
            'headerColor' => '表格標頭顏色',
            'showFieldSort' => '顯示的欄位跟排序',
            'sort' => '是否使用排序',
            'sortDefault' => '預設排序的欄位',
            'sortColumns' => '排序的欄位及順序',
            'alignLeft' => '文字向左對齊欄位',
            'otherColNameA' => '自定義欄位名稱 1(超過長度的部分會被替換成...)',
            'otherColNameB' => '自定義欄位名稱 2',
            'otherColNameC' => '自定義欄位名稱 3',
            'otherColNameD' => '自定義欄位名稱 4',
            'otherColNameE' => '自定義欄位名稱 5',
            'otherColNameF' => '自定義欄位名稱 6',
            'beforeHeaderA' => '欄位表頭名稱 1',
            'beforeHeaderB' => '欄位表頭名稱 2',
            'beforeHeaderC' => '欄位表頭名稱 3',
            'otherColNameAE' => '自定義欄位名稱 1 (英文)',
            'otherColNameBE' => '自定義欄位名稱 2 (英文)',
            'otherColNameCE' => '自定義欄位名稱 3 (英文)',
            'otherColNameDE' => '自定義欄位名稱 4 (英文)',
            'otherColNameEE' => '自定義欄位名稱 5 (英文)',
            'otherColNameFE' => '自定義欄位名稱 6 (英文)',
            'beforeHeaderAE' => '欄位表頭名稱 1 (英文)',
            'beforeHeaderBE' => '欄位表頭名稱 2 (英文)',
            'beforeHeaderCE' => '欄位表頭名稱 3 (英文)',
        ];
    }

    /**
     * 取得候選人設定
     */
    public function getConfigWithVoteID($voteID, $questionID=null)
    {
        return self::find()
            ->where(['voteID' => $voteID])
            ->andFilterWhere(['questionID' => $questionID]);
    }

    /**
     * 刪除候選設定
     */
    public function deleteAllCandiConfig($voteID)
    {
        return self::deleteAll([
            'voteID' => $voteID,
        ]);
    }

    /**
     * 取得表格標頭顏色
     *
     * @param  int|null $key 如果有多個顏色，取得指定的顏色
     * @return string
     */
    public function getHeaderColor($key=null)
    {
        if ($this->columnNum > 1) {
            $headerColors = explode(',', $this->headerColor);
            $headerColor = $headerColors > 1 && (!is_null($key) && !empty($headerColors[$key])) ? $headerColors[$key] : $this->headerColor;
        }
        else {
            $headerColor = $this->headerColor;
        }

        return $headerColor;
    }
}
