<?php

namespace app\models;

use Yii;
use app\components\helper\ArrayHelper;
use yii\helpers\Json;

/**
 * This is the model class for table "logins".
 *
 * @property int $sn
 * @property string $login
 * @property string $voteID
 * @property string $passwd
 * @property string|null $passwd_lookup HMAC 登入索引（crypto_version=1）
 * @property int $crypto_version 0=固定 IV 舊制，1=per-record IV
 * @property string $role sa):systemAdm; va):voteAdm; vas):voteAssistant; ve:)voteEcecutor; vt):voter
 * @property string $party whichParty
 * @property string $status 啟用與否
 * @property string $dtrack
 * @property string $voted
 * @property string $mark 標記
 */
class Passwords extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'passwords';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            [['voteID', 'passwd', 'party', 'status', 'voted', 'sn'], 'required'],
            [['sn'], 'integer'],
            [['voteID', 'mark'], 'string', 'max' => 20],
            [['passwd'], 'string', 'max' => 90],
            [['party'], 'string', 'max' => 12],
            [['status', 'dtrack', 'voted'], 'string', 'max' => 1],
            [['voteID', 'passwd'], 'unique', 'targetAttribute' => ['voteID', 'passwd']],
        ];

        if ($this->hasAttribute('passwd_lookup')) {
            $rules[] = [['passwd_lookup'], 'string', 'max' => 64];
        }
        if ($this->hasAttribute('crypto_version')) {
            $rules[] = [['crypto_version'], 'integer'];
        }

        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'voteID' => 'Vote ID',
            'sn' => 'Sn',
            'passwd' => '投票密碼',
            'passwd_lookup' => '登入索引',
            'crypto_version' => '加密版本',
            'party' => '投票組別',
            'status' => '啟用與否',
            'dtrack' => '雙軌投票',
            'voted' => '是否完成投票',
            'mark' => '標記',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if (!$insert) {
            // 如果是調整狀態，更新輪次啟用密碼數量
            if (in_array('status', array_keys($changedAttributes))) {
                $this->updateRoundPasswordsCount();
            }
        }
    }
    
    /**
     * 更新輪次啟用密碼數量
     *
     * @param  string $voteID
     * @return void
     */
    public function updateRoundPasswordsCount($voteID=null)
    {
        $voteID = is_null($voteID) ? $this->voteID : $voteID;
        $vote = Votes::findOne($voteID);
        $round = $vote->getCurrentRound()->one();
        $passwords = $this->getPasswordListByParty($voteID, $vote);

        if ($round && !empty($passwords)) {
            $round->enablePasswords = Json::encode(array_map('count', $passwords));
            $round->save(false);
        }
    }

    /**
     * 取得密碼
     */
    public function getPasswdFromIds($ids)
    {
        return self::find()->where(['in', 'id', $ids]);
    }

    /**
     * 取得密碼
     */
    public function getPasswd($voteID,$passwd,$status = '1')
    {
        return self::find()->where([
            'voteID' => $voteID,
            'passwd' => $passwd,
            'status' => $status,
        ]);
    }

    /**
     * 以 HMAC lookup 查詢密碼（v1）
     */
    public function getPasswdByLookup($voteID, $lookup, $status = '1')
    {
        return self::find()->where([
            'voteID' => $voteID,
            'passwd_lookup' => $lookup,
            'status' => $status,
        ]);
    }

    /**
     * 取得投票所有密碼列表
     */
    public function getVotePasswdList($voteID)
    {
        return self::find()
            ->where([
                'voteID' => $voteID
            ])
            ->orderBy('party ASC,sn ASC');
    }
    
    /**
     * 取得投票所有密碼列表
     *
     * @param  string $voteID
     * @param  object $votesInfo
     * @return array
     */
    public function getPasswordListByParty($voteID, $votesInfo)
    {
        $bindVoteInfo = null;
        if ($votesInfo['isBindVote']) {
            $passwordVoteID = $votesInfo['bindWhichVote'];
            $bindVoteInfo = Votes::findOne($passwordVoteID);
        }
        else {
            $passwordVoteID = $voteID;
        }
        $password = new Passwords();
        $passwordList = $password->getVotePasswdList($passwordVoteID)->andWhere(['status' => 1])->asArray()->all();
        $passwordListByParty = ArrayHelper::map($passwordList, 'sn', 'id', 'party');
        // 該場次沒有分組，但是綁定的場次有分組
        if ($votesInfo->partyOrNot == 0 && $bindVoteInfo !== null && $bindVoteInfo->partyOrNot == 1) {
            $defPasswordList = [];
            foreach ($passwordListByParty as $passowords) {
                foreach ($passowords as $passowordId) {
                    $defPasswordList[Parties::DEF_PARTY][] = $passowordId;
                }
            }
            $passwordListByParty = $defPasswordList;
        }

        return $passwordListByParty;
    }

    /**
     * 取得投票所有密碼列表
     */
    public function getSnList($voteID, $party)
    {
        return self::find()
            ->select('sn')
            ->where([
                'voteID' => $voteID,
                'party' => $party,
            ])
            ->orderBy('sn DESC');
    }

    /**
     * 列印密碼函列表
     */
    public function getPrintVotePasswd($voteID,$party,$start,$end)
    {
        return self::find()
            ->where([
                'voteID' => $voteID,
                'party' => $party,
            ])
            ->andWhere(['between', 'sn', $start, $end])
            ->orderBy('sn ASC');
    }
    
    /**
     * 取得所有標記
     *
     * @return array
     */
    public function getMarks($voteID)
    {
        $result = FormPasswords::find()
            ->select('mark')->distinct()
            ->where(['voteID' => $voteID])
            ->andWhere(['not', ['mark' => NULL]])
            ->orderBy('id')
            ->asArray()
            ->indexBy('mark')
            ->all();

        return ArrayHelper::map($result, 'mark', 'mark');
    }

    /**
     * 取得投票重複密碼
     */
    protected function getRepeatPasswd($voteID, $passwdAry)
    {
        return self::find()
            ->where([ 'and',
                [ '=', 'voteID', $voteID],
                [ 'in', 'passwd', $passwdAry],
            ]);
    }

    /**
     * 取得投票重複 lookup（v1 明文碰撞檢查）
     *
     * @param string $voteID
     * @param string[] $lookupAry
     * @return static[]
     */
    protected function getRepeatPasswdLookup($voteID, array $lookupAry)
    {
        $lookupAry = array_values(array_filter($lookupAry));
        if ($lookupAry === []) {
            return [];
        }

        return self::find()
            ->where(['and',
                ['=', 'voteID', $voteID],
                ['in', 'passwd_lookup', $lookupAry],
            ])
            ->all();
    }

    /**
     * 刪除密碼下拉選單items
     *
     * @param  string $voteID
     * @param  array $parties 分組
     * @param  array $marks 標記
     * @param  callback $confirm 確認刪除提示
     * @return array
     */
    public function deleteDropDownItems($voteID, $parties, $marks, $confirm) 
    {
        $items = [
            [
                'label' => '所有分組',
                'url' => ['passwd/delete-all', 'voteID' => $voteID],
                'linkOptions' => ['data' => $confirm('所有分組')]
            ]
        ];
        // 分組
        foreach($parties as $party => $label) {
            $items[] = [
                'label' => "分組 - $label",
                'url' => ['passwd/delete-all', 'voteID' => $voteID, 'party' => $party],
                'linkOptions' => ['data' => $confirm("分組 - $label")]
            ];
        }
        // 標記
        foreach ($marks as $mark) {
            $items[] = [
                'label' => "標記 - $mark",
                'url' => ['passwd/delete-all', 'voteID' => $voteID, 'mark' => $mark],
                'linkOptions' => ['data' => $confirm("標記 - $mark")]
            ];
        }

        return $items;
    }
 
    /**
     * 設定狀態: 如果沒有輸入條件就視為該場次投票所有密碼
     *
     * @param  string $voteID
     * @param  array $post
     * @return int
     */
    public function setPasswdStatus($passwordVoteID, $post, $ballotVoteID = null, $round = null)
    {
        $conditions = ['voteID' => $passwordVoteID];
        $conditionCols = ArrayHelper::only($post, ['party', 'mark', 'dtrack']);
        foreach ($conditionCols as $col => $value) {
            if ($value != '') {
                $conditions[$col] = $value;
            }
        }

        $query = self::find()->where($conditions);
        if ($ballotVoteID !== null && $round !== null) {
            $voted = $post['voted'] ?? null;
            FormPasswords::applyVotedFilter(
                $query,
                ($voted !== null && $voted !== '') ? (string) $voted : null,
                $ballotVoteID,
                (int) $round
            );
        }

        $ids = $query->select('id')->column();
        if ($ids === []) {
            return 0;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $count = self::updateAll(['status' => $post['status']], ['id' => $ids]);
            $this->updateRoundPasswordsCount($passwordVoteID);
            $transaction->commit();
            return $count;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error(
                \yii\helpers\VarDumper::dumpAsString(
                    $e->getMessage(), $depth=10, $highlight=false
                ),
                __METHOD__
            );
            return 0;
        }
    }
}
