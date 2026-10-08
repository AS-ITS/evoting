<?php

namespace app\models;

use Yii;
use app\components\Model;
use app\components\helper\ArrayHelper;
use yii\db\ActiveQuery;
use yii\helpers\Json;

class FormPasswords extends Passwords
{
    public $voteInfo;

    /** @var array<string, array{passwd: string, passwd_lookup: string|null, crypto_version: int}> */
    private $generatedPasswdPacks = [];

    /**
     * 密碼表 vs 本輪選票場次（本輪已投票篩選用 ballots，非 passwords.voted）
     *
     * @param Votes|object $voteInfo
     * @return array{passwordVoteID: string, ballotVoteID: string, round: int}
     */
    public static function resolvePasswordContext($voteInfo): array
    {
        $isBind = !empty($voteInfo->isBindVote);

        return [
            'passwordVoteID' => $isBind ? (string) $voteInfo->bindWhichVote : (string) $voteInfo->voteID,
            'ballotVoteID' => (string) $voteInfo->voteID,
            'round' => (int) $voteInfo->round,
        ];
    }

    /**
     * 本 voteID + 本輪次已有選票的密碼 id（統一為 int，避免與 passwords.id 嚴格比對失敗）
     *
     * @return int[]
     */
    public static function getVotedPasswordIds(string $ballotVoteID, int $round): array
    {
        return array_map(
            'intval',
            Ballots::find()
                ->select('creator')
                ->where(['voteID' => $ballotVoteID, 'round' => $round])
                ->column()
        );
    }

    /**
     * 本輪已投票條件（voted 為表單參數，非 passwords.voted 欄位）
     */
    public static function applyVotedFilter(ActiveQuery $query, ?string $voted, string $ballotVoteID, int $round): void
    {
        if ($voted === null || $voted === '') {
            return;
        }

        $votedIds = self::getVotedPasswordIds($ballotVoteID, $round);
        if ($voted === '1') {
            $query->andWhere(['in', 'id', $votedIds !== [] ? $votedIds : [0]]);
        } elseif ($voted === '0' && $votedIds !== []) {
            $query->andWhere(['not in', 'id', $votedIds]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            [['sn'], 'integer'],
            [['voteID', 'mark'], 'string', 'max' => 20],
            [['passwd'], 'string', 'max' => 90],
            [['party'], 'string', 'max' => 12],
            [['status', 'dtrack', 'voted'], 'string', 'max' => 1],
            [['party', 'passwd', 'status', 'dtrack', 'voted', 'mark'], 'safe', 'on'=>'search'],
            [['status', 'dtrack', 'voted'], 'safe', 'on'=>'update'],
            [['voteID', 'party', 'sn', 'passwd', 'status', 'dtrack', 'voted'], 'safe', 'on'=>'create'],
            [['voteID', 'passwd'], 'unique', 'targetAttribute' => ['voteID', 'passwd'], 'on'=>'create'],
        ];

        if ($this->hasAttribute('passwd_lookup')) {
            $rules[] = [['passwd_lookup'], 'string', 'max' => 64];
            $rules[] = [['passwd_lookup'], 'safe', 'on' => 'create'];
        }
        if ($this->hasAttribute('crypto_version')) {
            $rules[] = [['crypto_version'], 'integer', 'on' => 'create'];
            $rules[] = [['crypto_version'], 'safe', 'on' => 'create'];
        }

        return $rules;
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param string $passwordVoteID passwords 表 voteID
     * @param array $params
     * @param string|null $ballotVoteID 本輪 ballots 所屬 voteID（篩選本輪已投票時必填）
     * @param int|null $round
     */
    public function search($passwordVoteID, $params = [], $ballotVoteID = null, $round = null)
    {
        $this->setScenario('search');// 設置模型的方案
        $query = Passwords::getVotePasswdList($passwordVoteID);

        $params = Model::trimParams($params, $this->className());
        $this->load($params);

        if (!$this->validate()) {
            return $query;
        }

        // grid filtering conditions（voted 走 ballots，見 applyVotedFilter）
        $query->andFilterWhere([
            'id' => $this->id,
            'sn' => $this->sn,
            'party' => $this->party,
            'mark' => $this->mark,
            'status' => $this->status,
            'dtrack' => $this->dtrack,
        ]);

        if ($ballotVoteID !== null && $round !== null) {
            self::applyVotedFilter($query, $this->voted !== '' ? $this->voted : null, $ballotVoteID, (int) $round);
        }

        $Passwd = new Passwd;
        if (trim((string) $this->passwd) !== '') {
            $plain = trim($this->passwd);
            $lookup = $Passwd->computeLookup($passwordVoteID, $plain);
            $encryptedV0 = $Passwd->encrypt($plain);
            $query->andWhere(['or',
                ['passwd_lookup' => $lookup],
                ['passwd' => $encryptedV0],
            ]);
        }

        return $query;
    }

    /**
     * 密碼清單查詢（匯出密碼函等）
     *
     * @param array<string, mixed> $filters party, mark, dtrack, voted, start, end
     */
    public function buildListQuery(string $passwordVoteID, array $filters, string $ballotVoteID, int $round): ActiveQuery
    {
        $query = parent::getVotePasswdList($passwordVoteID);

        if (!empty($filters['party'])) {
            $query->andWhere(['party' => $filters['party']]);
        }
        if (!empty($filters['mark'])) {
            $query->andWhere(['mark' => $filters['mark']]);
        }
        if (isset($filters['dtrack']) && $filters['dtrack'] !== '') {
            $query->andWhere(['dtrack' => $filters['dtrack']]);
        }
        if (!empty($filters['start']) && !empty($filters['end'])) {
            $query->andWhere(['between', 'sn', $filters['start'], $filters['end']]);
        }

        $voted = $filters['voted'] ?? null;
        self::applyVotedFilter($query, ($voted !== null && $voted !== '') ? (string) $voted : null, $ballotVoteID, $round);

        return $query;
    }

    /**
     * 取得密碼資訊(使用密碼)
     */
    public function getPasswd( $voteID, $password, $status = '1')
    {
        $Passwd = new Passwd;
        $effectiveVoteID = $this->checkBindVote($voteID) ? $this->getBindWhichVote() : $voteID;
        $lookup = $Passwd->computeLookup($effectiveVoteID, $password);

        $row = parent::getPasswdByLookup($effectiveVoteID, $lookup, $status)->asArray()->one();
        if ($row !== null) {
            return $row;
        }

        $passwordEn = $Passwd->encrypt($password);
        return parent::getPasswd($effectiveVoteID, $passwordEn, $status)->asArray()->one();
    }

    /**
     * 取得密碼資訊(使用ID)
     */
    public function getPasswdFromId($id)
    {
        if(is_string($id))
            return self::findOne($id);
        else if(is_array($id))
            return parent::getPasswdFromIds($id)->all();
    }

    /**
     * 取得密碼資訊(使用ID)
     *
     * @param string|array $id
     * @param bool $decryptPlaintext 是否含明文（預設 false，管理 UI 遮罩）
     * @return array|null
     */
    public function getPasswdInfo($id, $decryptPlaintext = false)
    {
        $passwdIdAry = $this->getPasswdFromId($id);
        if(is_string($id))
        {
            if(is_null($passwdIdAry)) return null;
            $passwdAry = $passwdIdAry->attributes;
            $version = (int) ($passwdAry['crypto_version'] ?? Passwd::CRYPTO_V0);
            $passwdAry['passwd'] = $decryptPlaintext
                ? (new Passwd)->decrypt($passwdAry['passwd'], null, null, $version)
                : PasswordDisplay::maskLabel($passwdAry);
            return $passwdAry;
        }
        else if(is_array($id))
        {
            if(count($passwdIdAry) < 1) return null;
            $result = [];
            foreach($passwdIdAry as $passwd)
            {
                $temp = $passwd->attributes;
                $version = (int) ($temp['crypto_version'] ?? Passwd::CRYPTO_V0);
                $temp['passwd'] = $decryptPlaintext
                    ? (new Passwd)->decrypt($temp['passwd'], null, null, $version)
                    : PasswordDisplay::maskLabel($temp);
                $result[] = $temp;
            }
            return $result;
        }
    }

    /**
     * 取得密碼列表
     */
    public function getVotePasswdList($voteID)
    {
        return Passwords::getVotePasswdList($voteID)->all();
    }

    /**
     * 取得表格資料
     */
    public function getDataProvider($passwordVoteID, $params = [], $ballotVoteID = null, $round = null)
    {
        $DataProvider = new \app\models\DataProvider;
        return $DataProvider->getBasicDataProvider(
            $this->search($passwordVoteID, $params, $ballotVoteID, $round)
        );
    }

    /**
     * 改變密碼啟用狀態
     */
    public function setVote($id, $status = '1')
    {
        $this->setScenario('update');// 設置模型的方案
        $model = $this->getPasswdFromId($id);
        $model->voted = $status;
        return $model->save();
    }

    /**
     * 取得新選票建立者（匿名：密碼 id → 遮罩或明文標籤）
     *
     * @param array $sysIds
     * @param bool $decryptPlaintext 已「顯示明文」解鎖時為 true
     */
    public function getNewBallotChanger($sysIds, $decryptPlaintext = false)
    {
        $ary = ['' => '無'];
        if (!empty($sysIds[0])) {
            $passwd = $this->getPasswdInfo((string) $sysIds[0], $decryptPlaintext);
            if ($passwd !== null) {
                $ary[$passwd['id']] = $passwd['passwd'];
            }
        }
        return ArrayHelper::merge(
            $ary,
            ArrayHelper::map(
                Users::find()->where(['cn' => $sysIds])->asArray()->all(),
                'cn',
                'name'
            )
        );
    }
    /**
     * 改變密碼啟用狀態
     */
    public function toggleStatus($id = 1)
    {
        $this->setScenario('update');// 設置模型的方案
        $model = $this->getPasswdFromId($id);
        if (is_null($model))
            return false;
        switch($model->status)
        {
            case '0':
                $status = '1';
                break;
            
            case '1':
            default:
                $status = '0';
                break;
        }
        $model->status = $status;
        $model->save();
        return $status;
    }

    /**
     * 改變雙軌投票
     */
    public function toggleDtrack($id = 1)
    {
        $this->setScenario('update');// 設置模型的方案
        $model = $this->getPasswdFromId($id);
        if (is_null($model))
            return false;
        switch($model->dtrack)
        {
            case '1':
                $dtrack = '0';
                break;
            
            case '0':
            default:
                $dtrack = '1';
                break;
        }
        $model->dtrack = $dtrack;
        $model->save();
        return $dtrack;
    }

    /**
     * 刪除單一投票密碼
     */
    public function DeletePasswd($id = 1)
    {
        $model = self::findOne($id);
        if(is_null($model))
            return false;
        return $model->delete();
    }
  
    /**
     * 刪除多個投票密碼
     *
     * @param  string $voteID
     * @param  string|null $party 分組
     * @param  string|null $mark 標記
     * @return int
     */
    public function DeletePasswdAll($voteID, $party=null, $mark=null)
    {
        if(is_null($party) && is_null($mark)) {
            $condition = [
                'voteID' => $voteID
            ];
        }
        elseif (is_null($party)) {
            $condition = [
                'voteID' => $voteID,
                'mark' => $mark,
            ];
        }
        else {
            $condition = [
                'voteID' => $voteID,
                'party' => $party,
            ];
        }

        return self::deleteAll($condition);
    }

    /**
     * 生成不重複密碼
     */
    public function genPasswd($voteID, $quantity = 1, $length = 6, $type = Passwd::TYPE_MIX_EXCL, $format = NULL)
    {
        $Passwd = new Passwd;
        $pwdAry = [];
        $this->generatedPasswdPacks = [];

        do {
            $temp = $Passwd->genShuffleStr(
                $quantity - count($pwdAry),
                $length,
                $type, 
                $format
            );
            $plainList = is_string($temp) ? [$temp] : $temp;
            foreach ($plainList as $plain) {
                $packed = $Passwd->packForStorage($plain, $voteID, Passwd::CRYPTO_V1);
                $pwdAry[$packed['passwd']] = $packed;
                $this->generatedPasswdPacks[$packed['passwd']] = $packed;
            }

            $repPwdAry = $this->getRepeatPasswd($voteID, array_keys($pwdAry));
            foreach ($repPwdAry as $repPwd) {
                unset($pwdAry[$repPwd], $this->generatedPasswdPacks[$repPwd]);
            }

            $repLookups = ArrayHelper::getColumn(
                parent::getRepeatPasswdLookup($voteID, array_column($pwdAry, 'passwd_lookup')),
                'passwd_lookup'
            );
            foreach ($pwdAry as $enc => $pack) {
                if (in_array($pack['passwd_lookup'], $repLookups, true)) {
                    unset($pwdAry[$enc], $this->generatedPasswdPacks[$enc]);
                }
            }
        } while (count($pwdAry) < $quantity);

        return array_keys($pwdAry);
    }

    /**
     * 將 genPasswd 產生的 v1 中繼資料寫入模型
     */
    protected function applyStoredPasswdCrypto(self $formModel, string $storedPasswd): void
    {
        if (!isset($this->generatedPasswdPacks[$storedPasswd])) {
            return;
        }

        $pack = $this->generatedPasswdPacks[$storedPasswd];
        if ($formModel->hasAttribute('passwd_lookup')) {
            $formModel->passwd_lookup = $pack['passwd_lookup'];
        }
        if ($formModel->hasAttribute('crypto_version')) {
            $formModel->crypto_version = $pack['crypto_version'];
        }
    }

    /**
     * 取得投票重複密碼
     */
    protected function getRepeatPasswd($voteID, $passwdAry)
    {
        return ArrayHelper::getColumn(
            parent::getRepeatPasswd($voteID, $passwdAry), 'passwd'
        );
    }

    /**
     * 創建密碼，並補足缺少的 Sn
     */
    public function creationPasswd($voteID, $passwdAry, $party, $status, $dtrack, $mark=null)
    {
        $sn = 1;

        // 取出缺少的 Sn
        $snAry = parent::getSnList($voteID, $party)->all();
        if(count($snAry) > 0) // 曾經新增過了
        {
            $snAry = ArrayHelper::getColumn($snAry, 'sn');
            $lackSnAry = array_values(array_diff(
                range(1, $snAry[0]),
                $snAry
            ));
            // $snAry = null;
            // 開始建立密碼
            $transaction = Passwords::getDb()->beginTransaction();
            foreach($lackSnAry as $lackSn) // 建立缺少的密碼
            {
                $formModel = new self;
                $formModel->setScenario('create');
                $formModel->voteID = $voteID;
                $formModel->party = $party;
                $formModel->sn = $lackSn;
                $formModel->passwd = array_shift($passwdAry); // 將數組第一個移出
                $this->applyStoredPasswdCrypto($formModel, $formModel->passwd);
                $formModel->status = $status;
                $formModel->dtrack = $dtrack;
                $formModel->voted = '0';
                $formModel->mark = $mark;
                $formModel->save();
            }
            try {
                $transaction->commit(); // 批量操作
            } catch(\Exception $e) {
                $transaction->rollBack();
                throw $e;
            } catch(\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
            
            $sn = ++$snAry[0];// 將之前最大的 Sn + 1 取出
        }

        $transaction = Passwords::getDb()->beginTransaction();
        $numCount = count($passwdAry);
        for ($n = 0; $n < $numCount; $n++)
        {
            $formModel = new self;
            $formModel->setScenario('create');
            $formModel->voteID = $voteID;
            $formModel->party = $party;
            $formModel->sn = $sn++;
            $formModel->passwd = array_shift($passwdAry);
            $this->applyStoredPasswdCrypto($formModel, $formModel->passwd);
            $formModel->status = $status;
            $formModel->dtrack = $dtrack;
            $formModel->voted = '0';
            $formModel->mark = $mark;
            $formModel->save();
        }
        try {
            $transaction->commit(); // 批量操作
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch(\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
        return true;
    }
    
    /**
     * 檢查是否共用密碼
     *
     * @param  string $voteID
     * @return bool
     */
    public function checkBindVote($voteID)
    {
        $vote = new Votes();
        $voteInfo = $vote->getVoteInfo($voteID)->one();
        $this->voteInfo = $voteInfo;

        return $this->voteInfo ? $this->voteInfo->isBindVote : false;
    }
    
    /**
     * 取得共用密碼的投票辨識碼
     *
     * @param  string $voteID
     * @return string
     */
    public function getBindWhichVote()
    {
        return $this->voteInfo->bindWhichVote;
    }
}
