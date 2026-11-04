<?php

namespace app\tests\unit\models;

use app\models\FormUploadFile;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/**
 * 附件上傳檔案類型驗證測試
 *
 * 以實際檔案內容驗證：合法的 csv、Office 檔可通過，偽造副檔名的檔案會被擋。
 */
class FormUploadFileTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * 暫存目錄
     *
     * @var string
     */
    private $dir;

    protected function _before()
    {
        $this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'upload_test_'.uniqid();
        FileHelper::createDirectory($this->dir);
    }

    protected function _after()
    {
        FileHelper::removeDirectory($this->dir);
    }

    /**
     * 建立模擬上傳檔案
     *
     * @param  string $name 上傳時的檔名
     * @param  string $tempFile 實際內容檔案
     * @return UploadedFile
     */
    private function uploaded($name, $tempFile)
    {
        return new UploadedFile([
            'name' => $name,
            'tempName' => $tempFile,
            'type' => 'application/octet-stream',
            'size' => filesize($tempFile),
            'error' => UPLOAD_ERR_OK,
        ]);
    }

    private function put($name, $content)
    {
        $path = $this->dir.DIRECTORY_SEPARATOR.$name;
        file_put_contents($path, $content);
        return $path;
    }

    private function validateFiles(array $files)
    {
        $model = new FormUploadFile();
        $model->files = $files;
        return $model;
    }

    // ==================== 合法檔案可通過 ====================

    public function testCsvIsAccepted()
    {
        $model = $this->validateFiles([$this->uploaded('名單.csv', $this->put('a', "name,age\n王小明,30\n"))]);
        $this->assertTrue($model->validate(), json_encode($model->errors, JSON_UNESCAPED_UNICODE));
    }

    public function testTxtIsAccepted()
    {
        $model = $this->validateFiles([$this->uploaded('說明.txt', $this->put('a', "hello 測試\n"))]);
        $this->assertTrue($model->validate(), json_encode($model->errors, JSON_UNESCAPED_UNICODE));
    }

    public function testOfficeFilesAreAccepted()
    {
        $word = new \PhpOffice\PhpWord\PhpWord();
        $word->addSection()->addText('test');
        \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($docx = $this->dir.'/w.docx');
        \PhpOffice\PhpWord\IOFactory::createWriter($word, 'ODText')->save($odt = $this->dir.'/w.odt');

        $sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet->getActiveSheet()->setCellValue('A1', 'test');
        \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($sheet, 'Xlsx')->save($xlsx = $this->dir.'/s.xlsx');
        \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($sheet, 'Xls')->save($xls = $this->dir.'/s.xls');
        \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($sheet, 'Ods')->save($ods = $this->dir.'/s.ods');

        $model = $this->validateFiles([
            $this->uploaded('文件.docx', $docx),
            $this->uploaded('文件.odt', $odt),
            $this->uploaded('試算表.xlsx', $xlsx),
            $this->uploaded('試算表.xls', $xls),
            $this->uploaded('試算表.ods', $ods),
        ]);
        $this->assertTrue($model->validate(), json_encode($model->errors, JSON_UNESCAPED_UNICODE));
    }

    public function testPngIsAccepted()
    {
        // 1x1 透明 PNG
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        $model = $this->validateFiles([$this->uploaded('圖.png', $this->put('a', $png))]);
        $this->assertTrue($model->validate(), json_encode($model->errors, JSON_UNESCAPED_UNICODE));
    }

    // ==================== 偽造副檔名會被擋 ====================

    public function testExecutableRenamedToPdfIsRejected()
    {
        $elf = "\x7FELF\x02\x01\x01\x00".str_repeat("\x00", 120);
        $model = $this->validateFiles([$this->uploaded('report.pdf', $this->put('a', $elf))]);
        $this->assertFalse($model->validate());
        $this->assertStringContainsString('report.pdf', implode(' ', $model->getErrorSummary(true)));
    }

    public function testTextRenamedToPngIsRejected()
    {
        $model = $this->validateFiles([$this->uploaded('photo.png', $this->put('a', "<?php echo 'x';"))]);
        $this->assertFalse($model->validate());
    }

    public function testZipRenamedToCsvIsRejected()
    {
        $zip = new \ZipArchive();
        $zip->open($path = $this->dir.'/z.zip', \ZipArchive::CREATE);
        $zip->addFromString('a.txt', 'x');
        $zip->close();
        $model = $this->validateFiles([$this->uploaded('data.csv', $path)]);
        $this->assertFalse($model->validate());
    }

    public function testDisallowedExtensionIsRejected()
    {
        $model = $this->validateFiles([$this->uploaded('run.sh', $this->put('a', "echo hi\n"))]);
        $this->assertFalse($model->validate());
    }

    // ==================== isAllowedMime() ====================

    public function testIsAllowedMime()
    {
        $this->assertTrue(FormUploadFile::isAllowedMime('CSV', 'text/plain; charset=utf-8'));
        $this->assertTrue(FormUploadFile::isAllowedMime('docx', 'application/zip'));
        $this->assertFalse(FormUploadFile::isAllowedMime('pdf', 'application/zip'));
        $this->assertFalse(FormUploadFile::isAllowedMime('exe', 'application/x-dosexec'));
        $this->assertFalse(FormUploadFile::isAllowedMime('png', null));
    }
}
