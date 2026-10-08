<?php
/**
 * 多輪投票最小流程（P4 Functional 等效）
 *
 * 驗證：同一密碼可在不同 round 各投一次；管理員可切換輪次；補登狀態可後台補票。
 */

use app\models\CandiConfig;
use app\models\CandiData;
use app\models\FormAnon;
use app\models\FormBallots;
use app\models\FormPasswords;
use app\models\Passwd;
use app\models\Questions;
use app\models\Votes;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\VotesFixture;

class MultiRoundVoteCest
{
    private string $voteID = 'AnonNoPartyTest';

    public function _fixtures()
    {
        return [
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
            'votes' => VotesFixture::class,
            'round' => RoundFixture::class,
            'questions' => QuestionsFixture::class,
            'candiConfig' => CandiConfigFixture::class,
            'candiData' => CandiDataFixture::class,
            'passwords' => PasswordsFixture::class,
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->resetVoteState();
        $this->ensureRound2QuestionData();
    }

    /**
     * 同一密碼可在第 1、2 輪各建立一張選票
     */
    public function testSamePasswordVotesInRound1AndRound2(FunctionalTester $I)
    {
        $password = FormPasswords::find()
            ->where(['voteID' => $this->voteID, 'sn' => 1])
            ->asArray()
            ->one();
        \PHPUnit\Framework\Assert::assertNotNull($password);

        $this->createAnonBallot($password);
        $I->seeRecord(FormBallots::class, [
            'voteID' => $this->voteID,
            'round' => 1,
            'creator' => (string)$password['id'],
        ]);

        Votes::updateAll(['round' => 2], ['voteID' => $this->voteID]);
        $this->createAnonBallot($password);

        $I->seeRecord(FormBallots::class, [
            'voteID' => $this->voteID,
            'round' => 2,
            'creator' => (string)$password['id'],
        ]);
        \PHPUnit\Framework\Assert::assertEquals(2, FormBallots::find()
            ->where(['voteID' => $this->voteID, 'creator' => (string)$password['id']])
            ->count());
    }

    /**
     * 管理員切換輪次（RoundController smoke）
     */
    public function testAdminSwitchRound(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/round/switch?voteID=' . $this->voteID . '&round=2');
        $I->seeResponseCodeIsSuccessful();

        $vote = Votes::findOne(['voteID' => $this->voteID]);
        \PHPUnit\Framework\Assert::assertEquals(2, (int)$vote->round);
    }

    /**
     * 補登狀態下管理員可新增選票
     */
    public function testSupplementActiveAdminBallot(FunctionalTester $I)
    {
        Votes::updateAll([
            'active' => Votes::STATUS_BACKFILL,
            'round' => 1,
        ], ['voteID' => $this->voteID]);

        $I->amLoggedInAsAdmin('sa');
        $ballot = (new FormBallots())->getNewVoteBallot(
            $this->voteID,
            'def',
            'supp001',
            true
        );
        $ballot->scenario = 'creator';
        $ballot->voteID = $this->voteID;
        $ballot->round = 1;
        $ballot->modifier = 'sa';
        \PHPUnit\Framework\Assert::assertTrue($ballot->save());

        $I->seeRecord(FormBallots::class, [
            'voteID' => $this->voteID,
            'round' => 1,
            'creator' => 'supp001',
            'isAdminAdd' => '1',
        ]);
    }

    private function resetVoteState(): void
    {
        \Yii::$app->db->createCommand(
            'DELETE FROM ballotsSelected WHERE voteID = :voteID',
            [':voteID' => $this->voteID]
        )->execute();
        \Yii::$app->db->createCommand(
            'DELETE FROM ballots WHERE voteID = :voteID',
            [':voteID' => $this->voteID]
        )->execute();
        \Yii::$app->db->createCommand(
            'DELETE FROM logins WHERE voteID = :voteID',
            [':voteID' => $this->voteID]
        )->execute();
        FormPasswords::updateAll(['voted' => '0'], ['voteID' => $this->voteID]);

        $now = new \DateTime();
        Votes::updateAll([
            'round' => 1,
            'active' => Votes::STATUS_ACTIVE,
            'openStart' => (clone $now)->modify('-1 day')->format('Y-m-d H:i:s'),
            'openEnd' => (clone $now)->modify('+1 day')->format('Y-m-d H:i:s'),
        ], ['voteID' => $this->voteID]);

        if (!\Yii::$app->anon->isGuest) {
            \Yii::$app->anon->logout(false);
        }
    }

    private function ensureRound2QuestionData(): void
    {
        if (Questions::find()->where(['voteID' => $this->voteID, 'round' => 2])->exists()) {
            return;
        }

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

    private function createAnonBallot(array $passwordRow): FormBallots
    {
        $passwd = new Passwd();
        $login = new FormAnon();
        $login->voteID = $passwordRow['voteID'];
        $login->password = $passwd->decrypt($passwordRow['passwd']);
        if (!$login->login()) {
            throw new \RuntimeException('Anonymous login failed for multi-round test');
        }

        $formBallots = new FormBallots();
        $post = [
            'FormBallots' => [
                'party' => $passwordRow['party'],
            ],
        ];
        if (!$formBallots->creatorBallot($passwordRow['voteID'], $post, false)) {
            throw new \RuntimeException('creatorBallot failed for multi-round test');
        }

        \Yii::$app->anon->logout(false);
        return $formBallots;
    }
}
