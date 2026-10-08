<?php
/**
 * CandiController 功能測試
 *
 * 使用方式：
 * 1. 確認已建立 tests/fixtures/VotesFixture.php、CandiDataFixture.php、QuestionsFixture.php 等，並有對應資料。
 * 2. 執行：vendor/bin/codecept run functional
 * 3. 若測試資料、欄位或顯示內容不同，請依專案實際情況調整。
 */

use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\UsersFixture;
use Yii;

class CandiControllerCest
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
     * 測試候選人管理首頁導向
     */
    public function testIndexRedirectsToData(FunctionalTester $I)
    {
        $I->amOnPage('/candi/index?voteID=AnonPartyTest');
        // 可能是 302 重導或 200 直接顯示
        $I->seeResponseCodeIsSuccessful();
    }

    /**
     * 測試候選人配置頁
     */
    public function testConfigPage(FunctionalTester $I)
    {
        $I->amOnPage('/candi/config?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('候選名單配置');
    }

    /**
     * 測試候選名單管理頁
     */
    public function testDataPage(FunctionalTester $I)
    {
        $I->amOnPage('/candi/data?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('候選名單');
    }

    /**
     * 測試手動建立候選人頁
     */
    public function testManualCreatePage(FunctionalTester $I)
    {
        $I->amOnPage('/candi/manual?voteID=AnonPartyTest&action=create');
        $I->seeResponseCodeIs(200);
        $I->see('新增候選人');
    }

    /**
     * 測試匯入範例下載
     */
    public function testCsvExampleDownload(FunctionalTester $I)
    {
        $I->amOnPage('/candi/csv-example?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試 CSV 匯入頁（P2-3）
     */
    public function testCsvImportPage(FunctionalTester $I)
    {
        $I->amOnPage('/candi/csvfile?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試輪次導入頁
     */
    public function testRoundPage(FunctionalTester $I)
    {
        $I->amOnPage('/candi/round?voteID=AnonPartyTest');
        $I->seeResponseCodeIs(200);
        $I->see('輪次導入');
    }

    /**
     * 測試候選人圖片檢視
     */
    public function testViewPhoto(FunctionalTester $I)
    {
        $dir = Yii::getAlias('@filePool') . DIRECTORY_SEPARATOR . 'candidatePic' . DIRECTORY_SEPARATOR . 'AnonPartyTest';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filePath = $dir . DIRECTORY_SEPARATOR . 'test.jpg';
        if (!is_file($filePath)) {
            // 最小有效 JPEG（1×1 px）
            file_put_contents($filePath, base64_decode(
                '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AJ+AD//Z'
            ));
        }

        $I->amOnPage('/candi/view-photo?voteID=AnonPartyTest&file=test.jpg');
        $I->seeResponseCodeIs(200);
    }

    /**
     * 測試候選人圖片不存在時回傳 404
     */
    public function testViewPhotoNotFound(FunctionalTester $I)
    {
        $I->amOnPage('/candi/view-photo?voteID=AnonPartyTest&file=nonexistent.jpg');
        $I->seeResponseCodeIs(404);
    }
}