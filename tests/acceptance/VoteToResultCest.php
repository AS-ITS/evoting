<?php
/**
 * P4 路徑 1（Acceptance）：匿名 + 分組 + 單輪 — 瀏覽器登入圈選 + 開票驗證
 *
 * 前置：
 * php -S localhost:8080 -t web web/router-test.php
 * echo yes | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac" --appconfig=config/console-test.php
 */

use app\models\FormBallots;
use app\models\FormPasswords;
use app\models\FormResults;
use app\models\Results;
use app\models\Votes;

class VoteToResultCest
{
    private string $voteID = 'AnonPartyTest';
    private string $password = 'testN50';

    public function _before(AcceptanceTester $I)
    {
        \Yii::$app->db->createCommand('DELETE FROM logs WHERE voteID = :voteID', [
            ':voteID' => $this->voteID,
        ])->execute();
        \Yii::$app->db->createCommand(
            'DELETE FROM results WHERE voteID = :voteID',
            [':voteID' => $this->voteID]
        )->execute();
        \Yii::$app->db->createCommand('DELETE FROM ballotsSelected WHERE voteID = :voteID', [
            ':voteID' => $this->voteID,
        ])->execute();
        \Yii::$app->db->createCommand('DELETE FROM ballots WHERE voteID = :voteID', [
            ':voteID' => $this->voteID,
        ])->execute();
        \Yii::$app->db->createCommand('DELETE FROM logins WHERE voteID = :voteID', [
            ':voteID' => $this->voteID,
        ])->execute();

        FormPasswords::updateAll(['voted' => '0'], ['voteID' => $this->voteID]);

        $now = new \DateTime();
        \Yii::$app->db->createCommand()->update('votes', [
            'round' => 1,
            'active' => '1',
            'openStart' => (clone $now)->modify('-1 day')->format('Y-m-d H:i:s'),
            'openEnd' => (clone $now)->modify('+1 day')->format('Y-m-d H:i:s'),
        ], ['voteID' => $this->voteID])->execute();
    }

    /**
     * 瀏覽器：登入 → 圈選送出；後端：開票並驗證 results.rank
     */
    public function testBrowserVoteThenOpenResults(AcceptanceTester $I)
    {
        $I->amOnPage('index-test.php/site/password?voteID=' . $this->voteID);
        $I->fillField('FormAnon[password]', $this->password);
        $I->click('登入');
        $I->seeInCurrentUrl('/vote/start-vote?voteID=' . $this->voteID);
        AcceptanceVoteHelper::submitBallotFromStartVotePage($I, $this->voteID);
        $I->seeInCurrentUrl('/vote/vote-finish?voteID=' . $this->voteID);
        $I->see('投票完成');

        $I->assertEquals(1, FormBallots::find()
            ->where(['voteID' => $this->voteID, 'round' => 1])
            ->count());

        $formResults = new FormResults();
        if ($formResults->isResults($this->voteID, 1)) {
            $formResults->deleteResults($this->voteID, 1);
        }
        $I->assertTrue($formResults->createResults($this->voteID));
        $I->assertTrue($formResults->isResults($this->voteID, 1));

        $topRank = Results::find()
            ->where(['voteID' => $this->voteID])
            ->orderBy(['rank' => SORT_ASC])
            ->one();
        $I->assertNotNull($topRank);
        $I->assertEquals(1, (int)$topRank->rank);
    }
}
