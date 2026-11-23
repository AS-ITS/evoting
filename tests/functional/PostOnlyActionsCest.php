<?php
/**
 * 會變更資料的 action 只接受 POST
 *
 * GET（例如連結、<img src>、預先載入）不可刪除或變更資料：Yii 只對 POST 驗證 CSRF token，
 * 而 SameSite=Strict 以網域判定「同站」，其他 *.sinica.edu.tw 網頁仍會帶 cookie。
 * 每個 action 測：GET 回 405 且資料不變；POST 仍可執行（測得到結果的才測）。
 */

use app\components\helper\FileLoader;
use app\models\Ballots;
use app\models\CandiData;
use app\models\Passwords;
use app\models\Round;
use app\models\Users;
use app\models\Votes;
use app\tests\fixtures\BallotsFixture;
use app\tests\fixtures\BallotsSelectedFixture;
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
use PHPUnit\Framework\Assert;

class PostOnlyActionsCest
{
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
            'ballots' => BallotsFixture::class,
            'ballotsSelected' => BallotsSelectedFixture::class,
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $I->amLoggedInAsAdmin('sa');
    }

    // ==================== 投票 ====================

    public function deleteVoteRejectsGet(FunctionalTester $I)
    {
        $I->amOnPage('/elect/delete-vote?voteID=AnonNoPartyTest');
        $I->seeResponseCodeIs(405);
        Assert::assertTrue(Votes::find()->where(['voteID' => 'AnonNoPartyTest'])->exists());
    }

    public function deleteFileRejectsGet(FunctionalTester $I)
    {
        $target = $this->makeAttachment('post_only_probe.txt');

        $I->amOnPage('/elect/delete-file?voteID=NoAuthVoteTest&file=post_only_probe.txt');
        $I->seeResponseCodeIs(405);
        Assert::assertFileExists($target);

        $I->sendAjaxPostRequest('/elect/delete-file?voteID=NoAuthVoteTest&file=post_only_probe.txt');
        Assert::assertFileDoesNotExist($target);
    }

    // ==================== 選票 ====================

    public function deleteBallotRejectsGet(FunctionalTester $I)
    {
        $ballotID = (int) Ballots::find()->where(['voteID' => 'AnonPartyTest'])->min('ballotID');
        Assert::assertGreaterThan(0, $ballotID, 'fixture 應有 AnonPartyTest 的選票');

        $I->amOnPage("/ballot/delete?voteID=AnonPartyTest&ballotID=$ballotID");
        $I->seeResponseCodeIs(405);
        Assert::assertTrue(Ballots::find()->where(['voteID' => 'AnonPartyTest', 'ballotID' => $ballotID])->exists());

        $I->sendAjaxPostRequest("/ballot/delete?voteID=AnonPartyTest&ballotID=$ballotID");
        Assert::assertFalse(Ballots::find()->where(['voteID' => 'AnonPartyTest', 'ballotID' => $ballotID])->exists());
    }

    public function deleteAllBallotsRejectsGet(FunctionalTester $I)
    {
        $before = Ballots::find()->where(['voteID' => 'AnonPartyTest'])->count();
        Assert::assertGreaterThan(0, $before);

        $I->amOnPage('/ballot/delete-all?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(405);
        Assert::assertEquals($before, Ballots::find()->where(['voteID' => 'AnonPartyTest'])->count());
    }

    // ==================== 候選人 ====================

    public function deleteCandidateRejectsGet(FunctionalTester $I)
    {
        $id = (int) CandiData::find()->where(['voteID' => 'AnonNoPartyTest'])->min('id');

        $I->amOnPage("/candi/delete?voteID=AnonNoPartyTest&id=$id");
        $I->seeResponseCodeIs(405);
        Assert::assertTrue(CandiData::find()->where(['id' => $id])->exists());

        $I->sendAjaxPostRequest("/candi/delete?voteID=AnonNoPartyTest&id=$id");
        Assert::assertFalse(CandiData::find()->where(['id' => $id])->exists());
    }

    public function deletePhotoRejectsGet(FunctionalTester $I)
    {
        $id = (int) CandiData::find()->where(['voteID' => 'AnonNoPartyTest'])->min('id');
        CandiData::updateAll(['photo' => 'post_only_probe.jpg'], ['id' => $id]);

        $I->amOnPage("/candi/delete-photo?voteID=AnonNoPartyTest&id=$id");
        $I->seeResponseCodeIs(405);
        Assert::assertSame('post_only_probe.jpg', CandiData::findOne($id)->photo);
    }

    // ==================== 投票密碼 ====================

    public function deleteAllPasswordsRejectsGet(FunctionalTester $I)
    {
        $before = Passwords::find()->where(['voteID' => 'AnonPartyTest'])->count();
        Assert::assertGreaterThan(0, $before);

        $I->amOnPage('/passwd/delete-all?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(405);
        Assert::assertEquals($before, Passwords::find()->where(['voteID' => 'AnonPartyTest'])->count());
    }

    // ==================== 輪次 ====================

    public function createRoundRejectsGet(FunctionalTester $I)
    {
        $before = Round::find()->where(['voteID' => 'AnonPartyTest'])->count();

        $I->amOnPage('/round/create?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(405);
        Assert::assertEquals($before, Round::find()->where(['voteID' => 'AnonPartyTest'])->count());
    }

    public function switchRoundRejectsGet(FunctionalTester $I)
    {
        Assert::assertSame(1, (int) Votes::findOne('AnonPartyTest')->round);

        $I->amOnPage('/round/switch?voteID=AnonPartyTest&round=2');
        $I->seeResponseCodeIs(405);
        Assert::assertSame(1, (int) Votes::findOne('AnonPartyTest')->round);

        $I->sendAjaxPostRequest('/round/switch?voteID=AnonPartyTest&round=2');
        Assert::assertSame(2, (int) Votes::findOne('AnonPartyTest')->round);
    }

    // ==================== 使用者與登出 ====================

    public function deleteUserRejectsGet(FunctionalTester $I)
    {
        $I->amOnPage('/users/delete?cn=test_va2');
        $I->seeResponseCodeIs(405);
        Assert::assertNotNull(Users::findOne('test_va2'));

        $I->sendAjaxPostRequest('/users/delete?cn=test_va2');
        Assert::assertNull(Users::findOne('test_va2'));
    }

    public function logoutRejectsGet(FunctionalTester $I)
    {
        $I->amOnPage('/auth/logout?type=user');
        $I->seeResponseCodeIs(405);
        $I->seeAmLoggedIn();

        $I->sendAjaxPostRequest('/auth/logout?type=user');
        $I->seeAmGuest();
    }

    // ==================== 輔助 ====================

    private function makeAttachment(string $name): string
    {
        $dir = Yii::getAlias('@filePool') . '/candidateFile/NoAuthVoteTest';
        \yii\helpers\FileHelper::createDirectory($dir);
        $target = $dir . '/' . $name;
        file_put_contents($target, 'POST-ONLY');
        Assert::assertNotNull(FileLoader::voteDir(FileLoader::DIR_FILE, 'NoAuthVoteTest'), '附件目錄應可解析');
        return $target;
    }
}
