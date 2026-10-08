<?php

namespace app\tests\unit\models;

use app\models\PasswordDisplay;

class PasswordDisplayTest extends \Codeception\Test\Unit
{
    public function testMaskLabelUsesSnWhenPresent()
    {
        $this->assertSame('密碼#12', PasswordDisplay::maskLabel(['id' => 5, 'sn' => 12]));
    }

    public function testMaskLabelFallsBackToId()
    {
        $this->assertSame('密碼ID 7', PasswordDisplay::maskLabel(['id' => 7]));
    }

    public function testDecryptForExportReturnsShortValueAsIs()
    {
        $this->assertSame('abc', PasswordDisplay::decryptForExport('abc'));
    }

    public function testFormatGridValueMasksWhenLocked()
    {
        $this->assertSame('密碼#3', PasswordDisplay::formatGridValue(['id' => 1, 'sn' => 3, 'passwd' => 'secret'], false));
    }

    public function testFormatGridValueDecryptsWhenUnlocked()
    {
        $this->assertSame('plain', PasswordDisplay::formatGridValue(['id' => 1, 'sn' => 3, 'passwd' => 'plain'], true));
    }
}
