<?php
namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * 查詢資料表條列
 *
 * @property string $TABLE_SCHEMA 資料庫名稱
 * @property string $TABLE_NAME 資料表名稱
 * @property string $ENGINE 儲存引擎
 * @property int $TABLE_ROWS 資料筆數
 * @property int $DATA_LENGTH 資料長度
 * @property int $DATA_FREE 資料(未使用空間)
 * @property int $AUTO_INCREMENT 下一個AUTO_INCREMENT
 * @property string $UPDATE_TIME 最後更新時間
 * @property string $TABLE_COMMENT 資料表備註
 */
class DataTableModifierSearch extends Model
{
    /** @var string $TABLE_SCHEMA 資料庫名稱 */
    public $TABLE_SCHEMA;
    /** @var string $TABLE_NAME 資料表名稱 */
    public $TABLE_NAME;
    /** @var string $ENGINE 儲存引擎 */
    public $ENGINE;
    /** @var int $TABLE_ROWS 資料筆數 */
    public $TABLE_ROWS;
    /** @var int $DATA_LENGTH 資料長度 */
    public $DATA_LENGTH;
    /** @var string $DATA_FREE 資料(未使用空間) */
    public $DATA_FREE;
    /** @var string $AUTO_INCREMENT 下一個AUTO_INCREMENT */
    public $AUTO_INCREMENT;
    /** @var string $UPDATE_TIME 最後更新時間 */
    public $UPDATE_TIME;
    /** @var string $TABLE_COMMENT 資料表備註 */
    public $TABLE_COMMENT;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['TABLE_ROWS', 'DATA_LENGTH', 'DATA_FREE', 'AUTO_INCREMENT'], 'integer'],
            [['TABLE_SCHEMA', 'TABLE_NAME', 'ENGINE', 'UPDATE_TIME','TABLE_COMMENT'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'TABLE_SCHEMA' => '資料庫名稱',
            'TABLE_NAME' => '資料表名稱',
            'ENGINE' => '儲存引擎',
            'TABLE_ROWS' => '資料筆數',
            'DATA_LENGTH' => '資料長度',
            'DATA_FREE' => '資料未用空間',
            'AUTO_INCREMENT' => '下一個 AI',
            'UPDATE_TIME' => '最後更新時間',
            'TABLE_COMMENT' => '資料表備註',
        ];
    }

    /**
     * 創建表格搜索查詢的 ActiveDataProvider 實例
     *
     * @param \yii\db\Query $query
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($query, $params = [])
    {
        // 基礎過濾條件
        $query->andWhere([
            'TABLE_TYPE' => 'BASE TABLE',
        ]);
        // 設定排序
        $query->orderBy(['TABLE_NAME'=>SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => [
                'enableMultiSort' => true,
                'attributes' => [
                    'TABLE_SCHEMA',  'TABLE_NAME', 'ENGINE', 'TABLE_ROWS',
                    'DATA_LENGTH',   'DATA_FREE',
                    'AUTO_INCREMENT','UPDATE_TIME'
                ],
                'defaultOrder' => [
                    'TABLE_NAME' => SORT_ASC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'TABLE_SCHEMA', $this->TABLE_SCHEMA])
            ->andFilterWhere(['like', 'TABLE_NAME', $this->TABLE_NAME])
            ->andFilterWhere(['like', 'ENGINE', $this->ENGINE])
            ->andFilterWhere(['like', 'TABLE_ROWS', $this->TABLE_ROWS])
            ->andFilterWhere(['like', 'DATA_LENGTH', $this->DATA_LENGTH])
            ->andFilterWhere(['like', 'DATA_FREE', $this->DATA_FREE])
            ->andFilterWhere(['like', 'AUTO_INCREMENT', $this->AUTO_INCREMENT])
            ->andFilterWhere(['like', 'UPDATE_TIME', $this->UPDATE_TIME])
            ->andFilterWhere(['like', 'TABLE_COMMENT', $this->TABLE_COMMENT]);

        return $dataProvider;
    }
}