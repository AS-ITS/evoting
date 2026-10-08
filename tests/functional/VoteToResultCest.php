<?php
/**
 * P4 路徑 1：匿名 + 分組 + 單輪 — 登入 → 圈選 → 計票 → 開票
 *
 * Functional 層完整鏈（投票以模型等效 HTTP；管理端走 HTTP）。
 */

use app\components\helper\ArrayHelper;
use app\models\CandiData;
use app\models\FormAnon;
use app\models\FormBallots;
use app\models\FormManageCount;
use app\models\FormPasswords;
use app\models\FormResults;
use app\models\Passwd;
use app\models\Results;
use app\models\Votes;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\VotesFixture;

class VoteToResultCest
{
    private string $voteID = 'AnonPartyTest';

    public function _fixtures()
    {
        return [
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
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
    }

    /**
     * 匿名投票 → 計票單 → 開票 → results.rank 與計票排序一致
     */
    public function testAnonymousVoteCountAndOpenResults(FunctionalTester $I)
    {
        $ballot = $this->createAnonBallotWithSelections();
        $I->seeRecord(FormBallots::class, [
            'voteID' => $this->voteID,
            'ballotID' => $ballot->ballotID,
            'round' => 1,
        ]);

        $I->amLoggedInAsAdmin('sa');
        $I->amOnPage('/count/index?voteID=' . $this->voteID);
        $I->seeResponseCodeIs(200);
        $I->see('計票單');

        $formResults = new FormResults();
        if ($formResults->isResults($this->voteID, 1)) {
            $formResults->deleteResults($this->voteID, 1);
        }

        $I->amOnPage('/count/result?voteID=' . $this->voteID);
        $I->seeResponseCodeIs(200);
        $I->see('開票設定');

        \PHPUnit\Framework\Assert::assertTrue(
            $formResults->createResults($this->voteID),
            'createResults should succeed after anonymous vote'
        );
        \PHPUnit\Framework\Assert::assertTrue(
            $formResults->isResults($this->voteID, 1),
            'Results should exist after opening'
        );

        $manageCount = new FormManageCount($this->voteID);
        $expectedSort = $manageCount->getBallotCountSort(true);
        foreach ($expectedSort as $party => $questions) {
            foreach ($questions as $questionID => $questionData) {
                foreach ($questionData['ranking'] as $rankRow) {
                    $record = Results::findOne([
                        'voteID' => $this->voteID,
                        'party' => $party,
                        'questionID' => (string)$questionID,
                        'candID' => $rankRow['candi'],
                    ]);
                    \PHPUnit\Framework\Assert::assertNotNull(
                        $record,
                        "Missing result row for candidate {$rankRow['candi']}"
                    );
                    \PHPUnit\Framework\Assert::assertEquals(
                        (int)$rankRow['rank'],
                        (int)$record->rank,
                        'results.rank should match getBallotCountSort ranking'
                    );
                }
            }
        }

        $I->amOnPage('/result/index?voteID=' . $this->voteID);
        $I->seeResponseCodeIsSuccessful();
    }

    private function resetVoteState(): void
    {
        \Yii::$app->db->createCommand(
            'DELETE FROM results WHERE voteID = :voteID',
            [':voteID' => $this->voteID]
        )->execute();
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

    private function createAnonBallotWithSelections(): FormBallots
    {
        $password = FormPasswords::find()
            ->where(['voteID' => $this->voteID, 'sn' => 50])
            ->asArray()
            ->one();
        \PHPUnit\Framework\Assert::assertNotNull($password, 'Fixture password sn=50 required');

        $candidates = CandiData::find()
            ->where(['voteID' => $this->voteID, 'questionID' => 5])
            ->asArray()
            ->all();
        \PHPUnit\Framework\Assert::assertGreaterThanOrEqual(
            3,
            count($candidates),
            'Need at least 3 candidates for questionID=5'
        );

        $passwd = new Passwd();
        $login = new FormAnon();
        $login->voteID = $password['voteID'];
        $login->password = $passwd->decrypt($password['passwd']);
        \PHPUnit\Framework\Assert::assertTrue($login->login(), 'Anonymous login failed');

        $selectedIds = array_slice(ArrayHelper::getColumn($candidates, 'id'), 0, 3);
        $formBallots = new FormBallots();
        $post = [
            'FormBallots' => [
                'party' => $password['party'],
            ],
            'selection' => $selectedIds,
        ];
        \PHPUnit\Framework\Assert::assertTrue(
            $formBallots->creatorBallot($password['voteID'], $post, false),
            'creatorBallot with selections failed'
        );

        \Yii::$app->anon->logout(false);
        return $formBallots;
    }
}
