<?php
/**
 * 多輪匿名投票 E2E（P4）
 *
 * 前置：
 * 1. php -S localhost:8080 -t web web/router-test.php
 * 2. echo yes | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac" --appconfig=config/console-test.php
 */

use app\models\CandiConfig;
use app\models\CandiData;
use app\models\FormBallots;
use app\models\FormPasswords;
use app\models\Questions;
use app\models\Votes;

class MultiRoundVoteCest
{
    private string $voteID = 'AnonNoPartyTest';
    private string $password = 'testdef1';

    public function _before(AcceptanceTester $I)
    {
        \Yii::$app->db->createCommand('DELETE FROM logs WHERE voteID = :voteID', [
            ':voteID' => $this->voteID,
        ])->execute();

        $now = new \DateTime();
        \Yii::$app->db->createCommand()->update('votes', [
            'round' => 1,
            'active' => '1',
            'openStart' => (clone $now)->modify('-1 day')->format('Y-m-d H:i:s'),
            'openEnd' => (clone $now)->modify('+1 day')->format('Y-m-d H:i:s'),
        ], ['voteID' => $this->voteID])->execute();

        FormPasswords::updateAll(['voted' => '0'], ['voteID' => $this->voteID]);
        \Yii::$app->db->createCommand('DELETE FROM ballotsSelected WHERE voteID = :voteID', [
            ':voteID' => $this->voteID,
        ])->execute();
        \Yii::$app->db->createCommand('DELETE FROM ballots WHERE voteID = :voteID', [
            ':voteID' => $this->voteID,
        ])->execute();
        \Yii::$app->db->createCommand('DELETE FROM logins WHERE voteID = :voteID', [
            ':voteID' => $this->voteID,
        ])->execute();

        if (!Questions::find()->where(['voteID' => $this->voteID, 'round' => 2])->exists()) {
            $this->ensureRound2QuestionData();
        }
    }

    private function ensureRound2QuestionData(): void
    {
        $source = Questions::findOne(['voteID' => $this->voteID, 'round' => 1]);
        if ($source === null) {
            return;
        }

        $q2 = new Questions();
        $attrs = $source->attributes;
        unset($attrs['questionID']);
        $q2->setAttributes($attrs, false);
        $q2->round = 2;
        $q2->save(false);

        $srcQid = $source->questionID;
        $newQid = $q2->questionID;

        foreach (CandiData::find()->where(['voteID' => $this->voteID, 'questionID' => $srcQid])->all() as $candi) {
            $copy = new CandiData();
            $attrs = $candi->attributes;
            unset($attrs['id']);
            $copy->setAttributes($attrs, false);
            $copy->questionID = $newQid;
            $copy->save(false);
        }

        $config = CandiConfig::findOne(['voteID' => $this->voteID, 'questionID' => $srcQid]);
        if ($config !== null) {
            $cfgCopy = new CandiConfig();
            $attrs = $config->attributes;
            unset($attrs['candiConfig']);
            $cfgCopy->setAttributes($attrs, false);
            $cfgCopy->questionID = $newQid;
            $cfgCopy->save(false);
        }
    }

    private function submitBallot(AcceptanceTester $I): void
    {
        AcceptanceVoteHelper::submitBallotFromStartVotePage($I, $this->voteID);
    }

    /**
     * 第 1 輪：登入 → 圈選頁 → 完成
     */
    public function testRound1Vote(AcceptanceTester $I)
    {
        $I->amOnPage('index-test.php/site/password?voteID=' . $this->voteID);
        $I->fillField('FormAnon[password]', $this->password);
        $I->click('登入');
        $I->seeInCurrentUrl('/vote/start-vote?voteID=' . $this->voteID);
        $this->submitBallot($I);
        $I->seeInCurrentUrl('/vote/vote-finish?voteID=' . $this->voteID);
        $I->see('投票完成');

        $I->assertEquals(1, FormBallots::find()
            ->where(['voteID' => $this->voteID, 'round' => 1])
            ->count());
    }

    /**
     * 切換第 2 輪後同一密碼可再投一次
     */
    public function testRound2VoteAfterSwitch(AcceptanceTester $I)
    {
        Votes::updateAll(['round' => 2], ['voteID' => $this->voteID]);

        $I->amOnPage('index-test.php/site/password?voteID=' . $this->voteID);
        $I->fillField('FormAnon[password]', $this->password);
        $I->click('登入');
        $I->seeInCurrentUrl('/vote/start-vote?voteID=' . $this->voteID);
        $this->submitBallot($I);
        $I->seeInCurrentUrl('/vote/vote-finish?voteID=' . $this->voteID);
        $I->see('投票完成');

        $I->assertEquals(1, FormBallots::find()
            ->where(['voteID' => $this->voteID, 'round' => 2])
            ->count());
    }

    /**
     * 補登狀態（active=3）下匿名投票者可完成圈選
     */
    public function testBackfillRound1Vote(AcceptanceTester $I)
    {
        Votes::updateAll([
            'active' => Votes::STATUS_BACKFILL,
            'round' => 1,
        ], ['voteID' => $this->voteID]);

        $I->amOnPage('index-test.php/site/password?voteID=' . $this->voteID);
        $I->fillField('FormAnon[password]', $this->password);
        $I->click('登入');
        $I->seeInCurrentUrl('/vote/start-vote?voteID=' . $this->voteID);
        $this->submitBallot($I);
        $I->seeInCurrentUrl('/vote/vote-finish?voteID=' . $this->voteID);
        $I->see('投票完成');

        $I->assertEquals(1, FormBallots::find()
            ->where(['voteID' => $this->voteID, 'round' => 1])
            ->count());
    }
}
