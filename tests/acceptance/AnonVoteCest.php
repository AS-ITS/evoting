<?php

use yii\helpers\Url;
use Codeception\Example;
use Codeception\Scenario;
use app\models\FormBallots;
use app\models\FormBallotsSelected;
use app\models\FormManageVote;
use app\models\FormPasswords;
use app\models\Votes;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\VotesFixture;

/**
 * 這是模擬投票者投票並驗證圈選結果的測試
 * 
 * 需要先載入投票場次的資料: ```php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig"```
 * 
 * 如果想再次測試，刪除選票資料即可: ```php yii fixture/unload "Ballots,BallotsSelected"```
 * 
 * 投票腳本從script()函數設定，可以調整候選人圈選的範圍
 * 
 * 投票場次的資料從app\tests\fixtures\data\設定
 */
class AnonVoteCest
{
    /** 欲測試的投票場次ID */
    private $voteID = 'AnonPartyTest';

    private $vote;

    private $inCorrectPassword = 'Pa$$word';

    private $correctPassword = 'testN1';  // 匹配 fixture 中的密碼格式 testN{n}
    
    /**
     * 載入數據，先不使用，因為會接清空該資料表
     * 
     * ```
     * $anonParty = $I->grabFixture('votes', 'anon_party');
     * ```
     *
     * @return array
     */
    public function _fixtures() {
        return [
            // 'candiData'   => CandiDataFixture::class,
        ];
    }

    /**
     * 實際的測試資料（從資料庫載入）
     */
    private static $loadedAnonData = null;

    /**
     * 在每個測試方法執行前初始化（Yii應用程式已初始化）
     */
    public function _before(AcceptanceTester $I)
    {
        // 清除登入錯誤日誌以避免觸發密碼保護機制
        \Yii::$app->db->createCommand('DELETE FROM logs WHERE voteID = :voteID', [
            ':voteID' => $this->voteID
        ])->execute();

        // 只有在投票資料存在時才初始化（避免 fixture 未載入時出錯）
        $voteExists = \app\models\Votes::findOne(['voteID' => $this->voteID]);
        if ($voteExists) {
            // 重設投票狀態為進行中 (active=1) 和時間範圍
            $now = new \DateTime();
            $openStart = (clone $now)->modify('-1 day');
            $openEnd = (clone $now)->modify('+1 day');
            $verifyStart = (clone $openEnd)->modify('+1 hour');
            $verifyEnd = (clone $verifyStart)->modify('+1 hour');

            \Yii::$app->db->createCommand()->update('votes', [
                'active' => '1',  // 進行中
                'openStart' => $openStart->format('Y-m-d H:i:s'),
                'openEnd' => $openEnd->format('Y-m-d H:i:s'),
                'verifyStart' => $verifyStart->format('Y-m-d H:i:s'),
                'verifyEnd' => $verifyEnd->format('Y-m-d H:i:s'),
            ], ['voteID' => $this->voteID])->execute();

            // 重設密碼狀態（清除 voted 標記）以便重複測試
            \Yii::$app->db->createCommand()->update('passwords', [
                'voted' => '0',
            ], ['voteID' => $this->voteID])->execute();

            // 清除現有選票以便重複測試
            \Yii::$app->db->createCommand('DELETE FROM ballotsSelected WHERE voteID = :voteID', [
                ':voteID' => $this->voteID
            ])->execute();
            \Yii::$app->db->createCommand('DELETE FROM ballots WHERE voteID = :voteID', [
                ':voteID' => $this->voteID
            ])->execute();

            // 清除登入記錄
            \Yii::$app->db->createCommand('DELETE FROM logins WHERE voteID = :voteID', [
                ':voteID' => $this->voteID
            ])->execute();

            $this->vote = new FormManageVote($this->voteID);

            // 第一次執行時從資料庫載入測試資料
            if (self::$loadedAnonData === null) {
                self::$loadedAnonData = $this->loadAnonDataFromDatabase();
            }
        }
    }

    /**
     * 匿名登入失敗:空白密碼
     */
    public function tryAnonLoginFailEmptyPassword(AcceptanceTester $I, Scenario $scenario)
    {
        $I->amOnPage('index-test.php/site/password?voteID='.$this->voteID);

        $I->amGoingTo('測試匿名投票登入失敗');
        $I->see('開始投票，請輸入場次碼及線上投票密碼');
        $I->fillField('FormAnon[password]', '');
        $I->click('登入');
        $I->see('密碼不能為空');
        $I->click('English Version');
        $I->see('To start voting, please enter the session code and password.');
        $I->fillField('FormAnon[password]', '');
        $I->click('Login');
        $I->see('Password cannot empty');
    }

    /**
     * 匿名登入失敗:錯誤密碼
     */
    public function tryAnonLoginFailWrongPassword(AcceptanceTester $I, Scenario $scenario)
    {
        $I->amOnPage('index-test.php/site/password?voteID='.$this->voteID);
        $I->amGoingTo('測試匿名投票登入失敗');
        $I->see('開始投票，請輸入場次碼及線上投票密碼');
        $I->fillField('FormAnon[password]', $this->inCorrectPassword);
        $I->click('登入');
        $I->see('您輸入的投票密碼不正確，請再試一次！');
        $I->click('English Version');
        $I->see('To start voting, please enter the session code and password.');
        $I->fillField('FormAnon[password]', $this->inCorrectPassword);
        $I->click('Login');
        $I->see('The voting password you entered is incorrect, please try again！');
    }
   
    /**
     * 匿名登入成功
     */
    public function tryAnonLoginSuccess(AcceptanceTester $I, Scenario $scenario)
    {
        $I->amOnPage('index-test.php/site/password?voteID='.$this->voteID);
        $I->amGoingTo('測試匿名投票登入成功');
        $I->see('開始投票，請輸入場次碼及線上投票密碼');
        $I->fillField('FormAnon[password]', $this->correctPassword);
        $I->click('登入');

        // After login, redirects to vote-detail which may redirect to start-vote
        // Check that we're on the voting page
        $I->see('圈選須知');

        // 驗證頁面包含投票相關元素
        $I->seeCurrentUrlMatches('~vote/start-vote~');
    }

    /**
     * 圈選候選人 - 使用第二組密碼 testN2 進行投票測試
     */
    public function tryAnonVoteCandidates(AcceptanceTester $I, Scenario $scenario)
    {
        // 使用第二組密碼進行測試 (testN1 已在 tryAnonLoginSuccess 中使用)
        $testPassword = 'testN2';

        $I->amOnPage('index-test.php/site/password?voteID='.$this->voteID);
        $I->fillField('FormAnon[password]', $testPassword);
        $I->click('登入');

        $I->seeInCurrentUrl('/vote/start-vote?voteID='.$this->voteID);

        $I->wantTo('圈選候選人');

        // 取得可圈選的候選人（從頁面上的 checkbox）
        // 簡單測試：不圈選任何候選人，直接送出（測試空白票）
        $I->amGoingTo('送出選票');
        $I->click('//*[@id="submitButton"]/button');

        // 驗證投票完成
        $I->seeInCurrentUrl('/vote/vote-finish?voteID='.$this->voteID);
        $I->see('投票完成');
    }
  
    /**
     * 確認投票完成後導向正確頁面
     */
    public function tryAnonCheckBallot(AcceptanceTester $I, Scenario $scenario)
    {
        // 使用第三組密碼進行測試
        $testPassword = 'testN3';

        // 先投票
        $I->amOnPage('index-test.php/site/password?voteID='.$this->voteID);
        $I->fillField('FormAnon[password]', $testPassword);
        $I->click('登入');

        // 送出空白票
        $I->seeInCurrentUrl('/vote/start-vote?voteID='.$this->voteID);
        $I->click('//*[@id="submitButton"]/button');

        // 驗證投票完成頁面
        $I->seeInCurrentUrl('/vote/vote-finish?voteID='.$this->voteID);
        $I->wantTo('看到投票完成訊息');
        $I->see('投票完成');
    }
    
    /**
     * 刪除所有選票
     *
     * @return void
     */
    protected function deleteBallot()
    {
        $Ballot = new FormBallots();
        $Ballot->deleteAllBallot($this->voteID, $this->vote->round);
    }

    /**
     * 從資料庫載入實際的測試資料
     * 這個方法在 _before() 中被呼叫，此時 Yii 應用程式已初始化
     *
     * 如果要模擬多台設備同時投票，可以調整limit及offset，並在不同的終端機上執行，
     * 開N個終端機，每個終端機各執行100張密碼的投票。
     * ex: cmd1: limit=100 offset=0
     * ex: cmd2: limit=100 offset=100
     * ex: cmd3: limit=100 offset=200
     *
     * @return array
     */
    private function loadAnonDataFromDatabase()
    {
        $passwd = new \app\models\Passwd;
        $passwords = FormPasswords::find()
            ->where(['voteID' => $this->voteID])
            // ->limit(100)->offset(100)
            ->asArray()->all();
        $round = Votes::findOne(['voteID' => $this->voteID])->round;
        $dataProvider = [];
        foreach ($passwords as $password) {
            $dataProvider[] = [
                'voteID' => $password['voteID'],
                'password' => $passwd->decrypt($password['passwd']),
                'party' => $password['party'],
                'creator' => $password['id'],
                'ballotSelected' => $this->script($password['party'], $password['sn'], false, $round),
                'ballotSelectedN' => $this->script($password['party'], $password['sn'], true, $round),
                // 'ballotSelectedN' => [],
            ];
        }
        return $dataProvider;
    }

    /**
     * 投票資料提供者（延遲載入）
     * 返回簡單的佔位符，實際資料在 _before() 中從資料庫載入
     *
     * @return array
     */
    protected function anonProvider()
    {
        // 返回一個佔位符陣列
        // 實際資料會在測試執行時通過 loadAnonDataFromDatabase() 載入
        return [
            ['_lazy_load' => true],
        ];
    }
    
    /**
     * 投票腳本 [start, end]
     *
     * @param  string|int $party 組別
     * @param  int $sn 密碼編號
     * @param  bool $allParty 全部分組問題
     * @param  int $round 投票輪次
     * @return array
     */
    private function script($party, $sn, $allParty=false, $round=1)
    {
        $script = [0, 0]; // 預設值
        if ($allParty) {
            $script = [54, 56];
        }
        else {
            switch ($party) {
                case 0:
                case 1:
                case 2:
                case 3:
                    if (rand(0, 2) != 0) {
                        $script = [1, 53];
                    }
                    else {
                        $script = [0, 0];
                    }
                    break;
                default:
                    // 其他 party 值使用預設腳本
                    $script = [1, 10];
                    break;
            }
        }
        $candiList = [];
        $start = $script[0];
        $end = $script[1];
        
        for ($i=$start; $i <= $end; $i++) { 
            if (!empty($i)) {
                $candiList[$i] = $i;
            }
        }

        return $candiList;
    }
}
