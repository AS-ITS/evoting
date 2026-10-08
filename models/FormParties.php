<?php
namespace app\models;

use Yii;
use yii\helpers\Json;
use app\components\helper\ArrayHelper;

class FormParties extends Parties
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['voteID', 'party', 'numBallots', 'leastNumBallots', 'numOfKeep', 'maxElect'], 'required', 'on'=>['update']],
            [['name', 'nameE'], 'required', 'when' => function($model) {
                return $model->party != self::DEF_PARTY; // 非預設時，分組中英文名稱為必填
            }, 'on'=>['update']],
            [['numBallots', 'leastNumBallots', 'maxElect', 'numOfKeep', 'numFemaleKeep', 'numCounting'], 'integer', 'on'=>['update']],
            [['numBallots', 'maxElect'], 'integer', 'min' => 1],
            [['voteID', 'name'], 'string', 'max' => 20, 'on'=>['update']],
            [['nameE'], 'string', 'max' => 100, 'on'=>['update']],
            [['party'], 'string', 'max' => 12, 'on'=>['update']],
            // [['voteID', 'party'], 'unique', 'targetAttribute' => ['voteID', 'party'], 'on'=>['update']],
        ];
    }

    /**
     * 獲取對應組別投票限制資訊
     */
    public function getPartyInfo($voteID, $party = null)
    {
        return parent::getPartyInfo($voteID, $party)->one();
    }

    /**
     * 獲取對應組別投票限制資訊
     */
    public function getPartyAll($voteID)
    {
        return parent::getPartyAll($voteID)->all();
    }

    /**
     * 更新組別
     */
    public function updateParty($voteID, $post, $saveAs = false)
    {
        $this->setScenario('update');// 設置模型的方案
        $formData = ArrayHelper::getValue($post, $this->formName());
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);
        $status = true;
        $attributesDiff = [];
        foreach($formData as $party => $fd)
        {
            $this->load([$this->formName() => $fd]);
            // $party 可能為數字，因為經過foreach，所以要特別轉字串，否則無法通過型態驗證
            $party = strval($party);

            $this->setAttributes([
                'voteID' => $voteID,
                'party' => $party,
                'name' => ArrayHelper::getValue($fd, 'name'),
                'nameE' => ArrayHelper::getValue($fd, 'nameE'),
                'numFemaleKeep' => ArrayHelper::getValue($fd, 'numFemaleKeep'),
            ], false);

            if($saveAs)
            {
                // 另存
                $upd = new parent;
            }
            else
            {
                // 修改
                $upd = parent::getOneVote($voteID, $party);
            }
            
            if(!$this->validate()) // 資料驗證
            {
                $status = false;
                $session = Yii::$app->session;
                foreach($this->errors as $message)
                {
                    $session->addFlash('error', $message[0]);
                }
                // LOG紀錄: 驗證失敗
                Logs::add(
                    $saveAs ? Logs::VOTE_SAVE_AS_FAIL : Logs::VOTE_PARTY_EDIT_FAIL
                    , Json::encode(compact('voteID')+$this->errors, 336)
                );
                return $status;
            }
            $upd->setAttributes( $this->attributes, false);
            $oldAttributes = $upd->oldAttributes;
            
            // Note that this method will not perform data validation and will not trigger events.
            if(!$upd->save() && count($upd->errors) > 0)
            {
                $status = false;
                $session = Yii::$app->session;
                foreach($upd->errors as $message)
                {
                    $session->addFlash('error', $message[0]);
                }
                return $status;
            }

            if ($saveAs == false) {
                // 比較更新前即更新後差異
                $attributesDiff[$party] = ArrayHelper::getAttributesMigration($upd->getAttributes(), $oldAttributes);
                if (empty($attributesDiff[$party])) {
                    unset($attributesDiff[$party]);
                }
            }
        }
        
        // LOG紀錄: 修改儲存差異，另存當作新增
        if (!empty($attributesDiff) && !$saveAs) {
            Logs::add(Logs::VOTE_PARTY_EDIT, Json::encode(compact('voteID')+$attributesDiff, 336));
        }
        
        return $status;
    }

    /**
     * 刪除組別
     */
    public function deleteParty($voteID)
    {
        $numRow = parent::deleteAll(['voteID'=>$voteID]);
        if($numRow > 0)
            return true;
        return false;
    }

    /**
     * 獲取對應組別投票限制資訊
     */
    public function getPartyNumBallots($voteID, $party = null)
    {
        $partyModel = $this->getPartyInfo($voteID, $party);
        if (is_null($partyModel))
            return null;
        $tmpDataAry = $partyModel->attributes;
        return [
            'mostNum' => $tmpDataAry['numBallots'], // 可投票數
            'leastNum' => $tmpDataAry['leastNumBallots'], // 最少應投票數
        ];
    }
}
