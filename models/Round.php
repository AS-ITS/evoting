<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "round".
 *
 * @property int $id
 * @property string $voteID
 * @property int $round
 * @property string $name
 * @property string $nameE
 * @property string $showName
 * @property string $enablePasswords
 * @property string $insert_datetime
 * @property string|null $update_datetime
 */
class Round extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'round';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['voteID', 'round', 'name', 'showName'], 'required'],
            [['round'], 'integer'],
            [['update_datetime'], 'safe'],
            [['voteID'], 'string', 'max' => 20],
            [['enablePasswords'], 'string', 'max' => 50],
            [['name', 'nameE'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '序號',
            'voteID' => '投票ID',
            'round' => '輪次',
            'name' => '名稱',
            'nameE' => '英文名稱',
            'showName' => '是否顯示輪次名稱',
            'enablePasswords' => '啟用密碼數量',
            'insert_datetime' => '建立時間',
            'update_datetime' => '更新時間',
        ];
    }
    
    /**
     * 新增輪次
     *
     * @param  string $voteID
     * @param  int $round 第幾輪
     * @param  string $name 輪次名稱
     * @return void
     */
    public function createRound($voteID, $round, $name, $nameE=null)
    {
        $model = new self;

        $model->setAttributes([
            'voteID' => $voteID,
            'round' => $round,
            'name' => $name,
            'nameE' => $nameE,
            'showName' => '0'
        ]);

        if (!$model->save()) {
            foreach($model->errors as $message)
            {
                Yii::$app->session->addFlash('error', $message[0]);
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * 取得最大輪次
     *
     * @param  string $voteID
     * @return void
     */
    public function getMaxRound($voteID)
    {
        $max = self::find()->where(['voteID' => $voteID])->max('round');

        return $max;
    }

    /**
     * 檢查最新一輪是否有投票
     *
     * @param  string $voteID
     * @return void
     */
    public function checkMaxRoundVoted($voteID)
    {
        $max = $this->getMaxRound($voteID);

        $check = Ballots::find()->where(['voteID' => $voteID, 'round' => $max])->exists();

        return $check;
    }

    /**
     * 檢查該輪次是否有建立問題
     *
     * @return bool
     */
    public function checkQuestion($alert=false)
    {
        // 該輪次問題是否存在
        $checkQuestion = Questions::find()->where(['voteID' => $this->voteID, 'round' => $this->round])->exists();
        if (!$checkQuestion) {
            if ($alert) {
                Yii::$app->session->addFlash('error', '此輪次問題尚未設定');
            }
            return false;
        }
        return true;
    }

    /**
     * 檢查該輪次是否有建立候選人
     *
     * @return bool
     */
    public function checkCandi($alert=false)
    {
        // 該輪次候選人是否存在
        $roundQuestions = Questions::find()
            ->select(['questionID'])
            ->where(['voteID' => $this->voteID, 'round' => $this->round]);
        $checkCandi = CandiData::find()->where(['in', 'questionID', $roundQuestions])->exists();
        if (!$checkCandi) {
            if ($alert) {
                Yii::$app->session->addFlash('error', '此輪次候選人尚未設定');
            }
            return false;
        }
        return true;
    }
}
