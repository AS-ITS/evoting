<?php
/**
 * 檔案存取安全性接受測試
 *
 * 驗證未登入可存取的兩個檔案端點不受路徑穿越攻擊：
 * - candi/view-photo：候選人照片（file 為檔名）
 * - vote/download-file：投票附件（file 為 scandir 序號）
 *
 * 測試前會在 @filePool 建立暫存檔，結束後移除。
 */

use app\models\Votes;
use app\tests\fixtures\VotesFixture;
use yii\helpers\FileHelper;

class FileAccessSecurityCest
{
    const VOTE_ID = 'NoAuthVoteTest';
    const PHOTO_NAME = 'probe_photo.txt';
    const PHOTO_CONTENT = 'PROBE-PHOTO-CONTENT';
    const ATTACH_NAME = 'probe_attach.txt';
    const ATTACH_CONTENT = 'PROBE-ATTACH-CONTENT';
    const SECRET_NAME = 'probe_secret.txt';
    const SECRET_CONTENT = 'PROBE-SECRET-CONTENT';

    /**
     * 本測試建立的檔案與目錄（僅移除自己建立的）
     *
     * @var array
     */
    private $createdFiles = [];
    private $createdDirs = [];

    public function _fixtures()
    {
        return [
            'votes' => VotesFixture::class,
        ];
    }

    public function _before(AcceptanceTester $I)
    {
        $pool = Yii::getAlias('@filePool');

        $picDir = $pool.'/candidatePic/'.self::VOTE_ID;
        $fileDir = $pool.'/candidateFile/'.self::VOTE_ID;
        $this->makeDir($picDir);
        $this->makeDir($fileDir);

        $this->makeFile($picDir.'/'.self::PHOTO_NAME, self::PHOTO_CONTENT);
        $this->makeFile($fileDir.'/'.self::ATTACH_NAME, self::ATTACH_CONTENT);
        // 位於投票目錄外的機敏檔，穿越成功才讀得到
        $this->makeFile($pool.'/candidatePic/'.self::SECRET_NAME, self::SECRET_CONTENT);
        $this->makeFile($pool.'/candidateFile/'.self::SECRET_NAME, self::SECRET_CONTENT);
    }

    public function _after(AcceptanceTester $I)
    {
        foreach ($this->createdFiles as $file) {
            @unlink($file);
        }
        foreach (array_reverse($this->createdDirs) as $dir) {
            @rmdir($dir);
        }
        $this->createdFiles = [];
        $this->createdDirs = [];
    }

    // ---- candi/view-photo ----

    /**
     * 正常檔名可讀取
     */
    public function testViewPhotoNormal(AcceptanceTester $I)
    {
        $I->amOnPage('/candi/view-photo?'.http_build_query(['voteID' => self::VOTE_ID, 'file' => self::PHOTO_NAME]));
        $I->seeResponseCodeIs(200);
        $I->see(self::PHOTO_CONTENT);
    }

    /**
     * file 參數以 ../ 穿越目錄應回傳 404
     */
    public function testViewPhotoFileTraversal(AcceptanceTester $I)
    {
        $payloads = [
            '../'.self::SECRET_NAME,
            '..\\'.self::SECRET_NAME,
            '../../candidatePic/'.self::SECRET_NAME,
            '../../../../../../etc/passwd',
            '..\\..\\..\\..\\..\\..\\Windows\\win.ini',
            '/etc/passwd',
        ];
        foreach ($payloads as $file) {
            $I->amOnPage('/candi/view-photo?'.http_build_query(['voteID' => self::VOTE_ID, 'file' => $file]));
            $I->seeResponseCodeIs(404);
            $I->dontSee(self::SECRET_CONTENT);
            $I->dontSee('root:');
        }
    }

    /**
     * voteID 參數以 ../ 穿越目錄應回傳 404
     */
    public function testViewPhotoVoteIdTraversal(AcceptanceTester $I)
    {
        $cases = [
            ['voteID' => '..', 'file' => self::SECRET_NAME],
            ['voteID' => self::VOTE_ID.'/..', 'file' => self::SECRET_NAME],
            ['voteID' => '../../../../../../etc', 'file' => 'passwd'],
        ];
        foreach ($cases as $params) {
            $I->amOnPage('/candi/view-photo?'.http_build_query($params));
            $I->seeResponseCodeIs(404);
            $I->dontSee(self::SECRET_CONTENT);
            $I->dontSee('root:');
        }
    }

    // ---- vote/download-file ----

    /**
     * 正常序號可下載
     */
    public function testDownloadFileNormal(AcceptanceTester $I)
    {
        $I->amOnPage('/vote/download-file?'.http_build_query(['voteID' => self::VOTE_ID, 'file' => $this->attachIndex()]));
        $I->seeResponseCodeIs(200);
        $I->see(self::ATTACH_CONTENT);
    }

    /**
     * file 指向 . / .. / 不存在的序號 / 路徑字串時應回傳 404
     */
    public function testDownloadFileInvalidIndex(AcceptanceTester $I)
    {
        $payloads = ['0', '1', '9999', '-1', '../'.self::SECRET_NAME, self::ATTACH_NAME];
        foreach ($payloads as $file) {
            $I->amOnPage('/vote/download-file?'.http_build_query(['voteID' => self::VOTE_ID, 'file' => $file]));
            $I->seeResponseCodeIs(404);
            $I->dontSee(self::SECRET_CONTENT);
        }
    }

    /**
     * voteID 參數以 ../ 穿越目錄應回傳 404（不可列出其他目錄的檔案）
     */
    public function testDownloadFileVoteIdTraversal(AcceptanceTester $I)
    {
        $voteIDs = ['..', self::VOTE_ID.'/..', '../../../../../../etc', '../candidatePic'];
        foreach ($voteIDs as $voteID) {
            // 逐一嘗試前幾個序號，任何一個都不應下載成功
            for ($i = 0; $i < 6; $i++) {
                $I->amOnPage('/vote/download-file?'.http_build_query(['voteID' => $voteID, 'file' => $i]));
                $I->seeResponseCodeIs(404);
                $I->dontSee(self::SECRET_CONTENT);
                $I->dontSee('root:');
            }
        }
    }

    // ---- elect/delete-file ----

    /**
     * 管理者由附件列表刪除檔案：連結帶檔名，可正確刪除
     */
    public function testDeleteFileByName(AcceptanceTester $I)
    {
        $pool = Yii::getAlias('@filePool');
        $target = $pool.'/candidateFile/'.self::VOTE_ID.'/probe_delete.txt';
        $this->makeFile($target, 'DELETE-ME');

        $I->amOnPage('/index-test.php/test/login?user=test_admin');
        // 刪除後會導回 referrer，此處只驗證檔案結果
        $I->amOnPage('/elect/delete-file?voteID='.self::VOTE_ID.'&file=probe_delete.txt');
        $I->assertFileDoesNotExist($target);
        $I->assertFileExists($pool.'/candidateFile/'.self::VOTE_ID.'/'.self::ATTACH_NAME);
    }

    /**
     * 刪除檔案不可穿越到場次附件目錄之外
     */
    public function testDeleteFileTraversal(AcceptanceTester $I)
    {
        $pool = Yii::getAlias('@filePool');
        $I->amOnPage('/index-test.php/test/login?user=test_admin');
        foreach ([
            ['voteID' => self::VOTE_ID, 'file' => '../'.self::SECRET_NAME],
            ['voteID' => self::VOTE_ID, 'file' => '..\\'.self::SECRET_NAME],
            ['voteID' => '..', 'file' => self::SECRET_NAME],
            ['voteID' => self::VOTE_ID, 'file' => '..'],
        ] as $params) {
            $I->amOnPage('/elect/delete-file?'.http_build_query($params));
            $I->seeResponseCodeIs(404);
        }
        $I->assertFileExists($pool.'/candidateFile/'.self::SECRET_NAME);
        $I->assertFileExists($pool.'/candidateFile/'.self::VOTE_ID.'/'.self::ATTACH_NAME);
    }

    /**
     * 匿名投票且需驗證後才顯示資訊時，未驗證不可下載附件
     */
    public function testDownloadFileRequiresAnonAuth(AcceptanceTester $I)
    {
        $vote = Votes::findOne(self::VOTE_ID);
        $original = ['type' => $vote->type, 'authBeforeDetail' => $vote->authBeforeDetail];
        Votes::updateAll(['type' => Votes::TYPE_ANON, 'authBeforeDetail' => 1], ['voteID' => self::VOTE_ID]);

        try {
            $I->amOnPage('/vote/download-file?'.http_build_query(['voteID' => self::VOTE_ID, 'file' => $this->attachIndex()]));
            $I->seeResponseCodeIs(403);
            $I->dontSee(self::ATTACH_CONTENT);
        } finally {
            Votes::updateAll($original, ['voteID' => self::VOTE_ID]);
        }
    }

    /**
     * 測試附件在 scandir 結果中的序號
     *
     * @return int
     */
    private function attachIndex()
    {
        $files = scandir(Yii::getAlias('@filePool').'/candidateFile/'.self::VOTE_ID);
        return array_search(self::ATTACH_NAME, $files, true);
    }

    private function makeDir($dir)
    {
        if (is_dir($dir)) {
            return;
        }
        $this->makeDir(dirname($dir));
        FileHelper::createDirectory($dir);
        $this->createdDirs[] = $dir;
    }

    private function makeFile($path, $content)
    {
        if (file_exists($path)) {
            return;
        }
        file_put_contents($path, $content);
        $this->createdFiles[] = $path;
    }
}
