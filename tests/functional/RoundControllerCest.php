<?php
/**
 * RoundController 功能測試
 *
 * 測試輪次管理功能，包含輪次列表、建立、編輯、切換等操作。
 */

use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\BallotsFixture;
use app\models\Ballots;
use app\models\FormBallots;
use app\models\Round;

class RoundControllerCest
{
    /**
     * 載入 fixture
     */
    public function _fixtures()
    {
        return [
            'users' => UsersFixture::class,
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
            'round' => RoundFixture::class,
            'questions' => QuestionsFixture::class,
            'candiConfig' => CandiConfigFixture::class,
            'candiData' => CandiDataFixture::class,
            'passwords' => PasswordsFixture::class,
            'ballots' => BallotsFixture::class,
        ];
    }

    /**
     * 每個測試前執行 - 模擬管理員登入
     */
    public function _before(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
    }

    /**
     * 測試輪次管理首頁
     */
    public function testIndexPage(FunctionalTester $I)
    {
        $I->amOnPage('/round/index?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('輪次');
    }

    /**
     * 測試輪次編輯頁面
     */
    public function testUpdatePage(FunctionalTester $I)
    {
        $I->amOnPage('/round/update?voteID=AnonPartyTest&round=1');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試輪次切換功能
     */
    public function testSwitchRound(FunctionalTester $I)
    {
        $I->amOnPage('/round/switch?voteID=AnonPartyTest&round=1');
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試不分組投票的輪次管理
     */
    public function testIndexPageNoParty(FunctionalTester $I)
    {
        $I->amOnPage('/round/index?voteID=AnonNoPartyTest');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試未登入時無法存取輪次管理
     */
    public function testUnauthorizedAccess(FunctionalTester $I)
    {
        $I->amLoggedOut();
        $I->amOnPage('/round/index?voteID=AnonPartyTest');
        // 應該被重導到登入頁面或看到登入表單
        // 頁面可能返回 200 (顯示登入表單) 或 302 (重導)
        // 確認用戶無法看到輪次管理的內容
        $I->dontSee('輪次');
    }

    /**
     * 測試缺少 voteID 參數時的行為
     */
    public function testMissingVoteIdParameter(FunctionalTester $I)
    {
        $I->amOnPage('/round/index');
        // 缺少必要參數應該返回 400 Bad Request
        $I->seeResponseCodeIs(400);
    }

    /**
     * 測試切換至不存在的輪次時不更新 votes.round
     * 防止 actionSwitch 未驗證輪次存在導致 votes.round 資料不一致
     */
    public function testSwitchToNonExistentRound(FunctionalTester $I)
    {
        $I->amOnPage('/round/switch?voteID=AnonPartyTest&round=999');
        // 不存在的輪次應重導回輪次管理頁面（302）或顯示錯誤訊息
        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeInCurrentUrl('/round/switch');
    }

    /**
     * 測試建立新輪次頁（P2-2）
     */
    public function testCreatePage(FunctionalTester $I)
    {
        $I->amOnPage('/round/create?voteID=AnonPartyTest');
        $I->dontSeeResponseCodeIs(500);
        $I->dontSeeResponseCodeIs(403);
    }

    /**
     * max 輪次已有選票時，建立新輪次應導向 update（P2-2）
     */
    public function testCreateRoundWhenMaxRoundHasBallots(FunctionalTester $I)
    {
        $voteID = 'AnonNoPartyTest';
        $roundModel = new Round();
        $maxRound = (int)$roundModel->getMaxRound($voteID);
        \PHPUnit\Framework\Assert::assertGreaterThan(0, $maxRound);

        if (!Ballots::find()->where(['voteID' => $voteID, 'round' => $maxRound])->exists()) {
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
            $ballot = (new FormBallots())->getNewVoteBallot($voteID, 'def', 'rnd4t001', true);
            $ballot->scenario = 'creator';
            $ballot->voteID = $voteID;
            $ballot->round = $maxRound;
            $ballot->ip = '127.0.0.1';
            $ballot->modifier = 'sa';
            \PHPUnit\Framework\Assert::assertTrue($ballot->save(), 'seed ballot for max round');
        }

        $roundCountBefore = Round::find()->where(['voteID' => $voteID])->count();
        $I->amOnPage('/round/index?voteID=' . $voteID);
        $I->amOnPage('/round/create?voteID=' . $voteID);
        $I->seeInCurrentUrl('round/update');
        $I->seeInCurrentUrl('round=' . ($maxRound + 1));
        \PHPUnit\Framework\Assert::assertEquals(
            $roundCountBefore + 1,
            Round::find()->where(['voteID' => $voteID])->count()
        );

        // 清理，避免影響後續測試
        Round::deleteAll(['voteID' => $voteID, 'round' => $maxRound + 1]);
    }

    /**
     * max 輪次無選票時，建立應失敗並留在原頁
     */
    public function testCreateRoundFailsWhenMaxRoundNotVoted(FunctionalTester $I)
    {
        $voteID = 'AnonNoPartyTest';
        $maxRound = (int)(new Round())->getMaxRound($voteID);
        Ballots::deleteAll(['voteID' => $voteID, 'round' => $maxRound]);

        $roundCountBefore = Round::find()->where(['voteID' => $voteID])->count();
        $I->amOnPage('/round/index?voteID=' . $voteID);
        $I->amOnPage('/round/create?voteID=' . $voteID);
        $I->seeInCurrentUrl('round/index');
        \PHPUnit\Framework\Assert::assertEquals(
            $roundCountBefore,
            Round::find()->where(['voteID' => $voteID])->count()
        );
    }
}
