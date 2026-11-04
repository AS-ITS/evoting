<?php

namespace app\tests\unit\components;

use Yii;
use app\components\helper\FileLoader;
use app\tests\fixtures\VotesFixture;
use yii\helpers\FileHelper;

/**
 * FileLoader 路徑防護測試
 *
 * 以暫存目錄模擬 FilePool，驗證 safePath()、voteDir() 與
 * imageData()/removeImage() 不會存取到允許目錄以外的檔案。
 */
class FileLoaderTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    const VOTE_ID = 'NoAuthVoteTest';

    /**
     * 暫存 FilePool 根目錄
     *
     * @var string
     */
    private $pool;

    /**
     * 原本的 @filePool
     *
     * @var string
     */
    private $originalPool;

    public function _fixtures()
    {
        return [
            'votes' => VotesFixture::class,
        ];
    }

    protected function _before()
    {
        $this->pool = sys_get_temp_dir().DIRECTORY_SEPARATOR.'fileloader_test_'.uniqid();
        FileHelper::createDirectory($this->pool.'/candidatePic/'.self::VOTE_ID);
        FileHelper::createDirectory($this->pool.'/candidateFile/'.self::VOTE_ID.'/sub');
        file_put_contents($this->pool.'/candidatePic/'.self::VOTE_ID.'/a.png', 'PNG');
        file_put_contents($this->pool.'/candidateFile/'.self::VOTE_ID.'/doc.txt', 'DOC');
        file_put_contents($this->pool.'/candidatePic/secret.txt', 'SECRET');
        file_put_contents($this->pool.'/secret.txt', 'SECRET');
        file_put_contents(dirname($this->pool).'/fileloader_outside_'.basename($this->pool).'.txt', 'OUTSIDE');

        $this->originalPool = Yii::getAlias('@filePool', false);
        Yii::setAlias('@filePool', $this->pool);
    }

    protected function _after()
    {
        Yii::setAlias('@filePool', $this->originalPool ?: null);
        @unlink(dirname($this->pool).'/fileloader_outside_'.basename($this->pool).'.txt');
        FileHelper::removeDirectory($this->pool);
    }

    private function picDir()
    {
        return realpath($this->pool.'/candidatePic/'.self::VOTE_ID);
    }

    // ==================== safePath() ====================

    public function testSafePathReturnsFileInsideBase()
    {
        $this->assertSame($this->picDir().DIRECTORY_SEPARATOR.'a.png', FileLoader::safePath($this->picDir(), 'a.png'));
    }

    public function testSafePathStripsDirectoryComponents()
    {
        // 去除目錄成分後只剩 a.png，仍在允許目錄內
        $expected = $this->picDir().DIRECTORY_SEPARATOR.'a.png';
        $this->assertSame($expected, FileLoader::safePath($this->picDir(), '../'.self::VOTE_ID.'/a.png'));
        $this->assertSame($expected, FileLoader::safePath($this->picDir(), '..\\x\\a.png'));
        $this->assertSame($expected, FileLoader::safePath($this->picDir(), '/a.png'));
    }

    /**
     * @dataProvider traversalProvider
     */
    public function testSafePathRejectsTraversal($name)
    {
        $this->assertNull(FileLoader::safePath($this->picDir(), $name));
    }

    public function traversalProvider()
    {
        return [
            'parent'           => ['../secret.txt'],
            'parent twice'     => ['../../secret.txt'],
            'backslash'        => ['..\\secret.txt'],
            'absolute unix'    => ['/etc/passwd'],
            'deep unix'        => ['../../../../../../../../etc/passwd'],
            'windows'          => ['..\\..\\..\\..\\Windows\\win.ini'],
            'dot'              => ['.'],
            'dotdot'           => ['..'],
            'empty'            => [''],
            'trailing slash'   => ['../'],
            'null byte'        => ["a.png\0.txt"],
            'not exist'        => ['nope.png'],
            'array'            => [['a.png']],
            'null'             => [null],
        ];
    }

    public function testSafePathRejectsDirectory()
    {
        $base = realpath($this->pool.'/candidateFile/'.self::VOTE_ID);
        $this->assertNull(FileLoader::safePath($base, 'sub'));
    }

    public function testSafePathRejectsMissingBase()
    {
        $this->assertNull(FileLoader::safePath($this->pool.'/no-such-dir', 'a.png'));
        $this->assertNull(FileLoader::safePath("{$this->picDir()}\0", 'a.png'));
    }

    public function testSafePathRejectsSymlinkOutsideBase()
    {
        $link = $this->picDir().DIRECTORY_SEPARATOR.'link.png';
        if (!@symlink($this->pool.DIRECTORY_SEPARATOR.'secret.txt', $link)) {
            $this->markTestSkipped('此環境無法建立 symlink');
        }
        $this->assertNull(FileLoader::safePath($this->picDir(), 'link.png'));
    }

    public function testSafePathRejectsSiblingPrefix()
    {
        // candidatePic/NoAuthVoteTestX 與 NoAuthVoteTest 前綴相同，但不在允許目錄內
        FileHelper::createDirectory($this->pool.'/candidatePic/'.self::VOTE_ID.'X');
        file_put_contents($this->pool.'/candidatePic/'.self::VOTE_ID.'X/b.png', 'X');
        $this->assertNull(FileLoader::safePath($this->picDir(), '../'.self::VOTE_ID.'X/b.png'));
        $this->assertFalse(FileLoader::isInside(
            realpath($this->pool.'/candidatePic/'.self::VOTE_ID.'X/b.png'),
            $this->picDir()
        ));
    }

    // ==================== isSafeSegment() ====================

    public function testIsSafeSegment()
    {
        $this->assertTrue(FileLoader::isSafeSegment('NoAuthVoteTest'));
        $this->assertTrue(FileLoader::isSafeSegment('報告.pdf'));
        foreach (['', '.', '..', 'a/b', 'a\\b', '../x', "a\0b", "a\nb"] as $bad) {
            $this->assertFalse(FileLoader::isSafeSegment($bad), json_encode($bad));
        }
    }

    // ==================== voteDir() ====================

    public function testVoteDirExistingVote()
    {
        $this->assertSame($this->picDir(), FileLoader::voteDir(FileLoader::DIR_PIC, self::VOTE_ID));
    }

    public function testVoteDirRejectsUnknownOrTraversalVoteId()
    {
        foreach (['NoSuchVote', '..', '../candidatePic', self::VOTE_ID.'/..', '', null, ['x']] as $voteID) {
            $this->assertNull(FileLoader::voteDir(FileLoader::DIR_PIC, $voteID), json_encode($voteID));
        }
    }

    public function testVoteDirRejectsUnknownSubDir()
    {
        $this->assertNull(FileLoader::voteDir('..', self::VOTE_ID));
        $this->assertNull(FileLoader::voteDir('candidatePic/..', self::VOTE_ID));
    }

    public function testVoteDirCreate()
    {
        FileHelper::removeDirectory($this->pool.'/candidateFile/'.self::VOTE_ID);
        $this->assertNull(FileLoader::voteDir(FileLoader::DIR_FILE, self::VOTE_ID));

        $dir = FileLoader::voteDir(FileLoader::DIR_FILE, self::VOTE_ID, true);
        $this->assertNotNull($dir);
        $this->assertDirectoryExists($dir);
        $this->assertSame(realpath($this->pool.'/candidateFile'), dirname($dir));
    }

    public function testVoteDirCreateReturnsNullWhenNotWritable()
    {
        if (DIRECTORY_SEPARATOR === '\\' || (function_exists('posix_geteuid') && posix_geteuid() === 0)) {
            $this->markTestSkipped('需在非 root 的 Linux 環境驗證目錄權限');
        }
        FileHelper::removeDirectory($this->pool.'/candidateFile/'.self::VOTE_ID);
        chmod($this->pool.'/candidateFile', 0555);
        try {
            $this->assertNull(FileLoader::voteDir(FileLoader::DIR_FILE, self::VOTE_ID, true));
            $this->assertDirectoryDoesNotExist($this->pool.'/candidateFile/'.self::VOTE_ID);
        } finally {
            chmod($this->pool.'/candidateFile', 0755);
        }
    }

    public function testVoteDirCreateDoesNotCreateForUnknownVote()
    {
        $this->assertNull(FileLoader::voteDir(FileLoader::DIR_FILE, '../escape', true));
        $this->assertDirectoryDoesNotExist($this->pool.'/escape');
    }

    // ==================== FileLoader 實例方法 ====================

    public function testImageDataInsideSubDir()
    {
        $loader = new FileLoader($this->pool);
        $this->assertSame('data:image/png;base64,'.base64_encode('PNG'), $loader->imageData('/'.self::VOTE_ID.'/a.png'));
    }

    public function testImageDataRejectsTraversal()
    {
        $loader = new FileLoader($this->pool);
        $outside = 'fileloader_outside_'.basename($this->pool).'.txt';
        $this->assertFalse($loader->imageData('/../secret.txt'));
        $this->assertFalse($loader->imageData('/../../'.$outside));
        $this->assertFalse($loader->imageData('secret.txt', '..'));
        $this->assertFalse($loader->imageData($outside, '/..'));
    }

    public function testRemoveImageRejectsTraversal()
    {
        $loader = new FileLoader($this->pool);
        $this->assertFalse($loader->removeImage('/../secret.txt'));
        $this->assertFalse($loader->removeImage('/'.self::VOTE_ID.'/../../secret.txt'));
        $this->assertFileExists($this->pool.'/secret.txt');
    }

    public function testRemoveImageInsideSubDir()
    {
        $loader = new FileLoader($this->pool);
        $this->assertTrue($loader->removeImage('/'.self::VOTE_ID.'/a.png'));
        $this->assertFileDoesNotExist($this->pool.'/candidatePic/'.self::VOTE_ID.'/a.png');
    }
}
