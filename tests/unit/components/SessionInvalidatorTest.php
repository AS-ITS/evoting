<?php

namespace app\tests\unit\components;

use app\components\SessionInvalidator;
use Codeception\Test\Unit;
use Yii;

class SessionInvalidatorTest extends Unit
{
    public function testIsValidSessionIdAcceptsTypicalIds()
    {
        $this->assertTrue(SessionInvalidator::isValidSessionId('abcdefghijklmnopqrstuvwxyz12'));
    }

    public function testIsValidSessionIdRejectsTraversal()
    {
        $this->assertFalse(SessionInvalidator::isValidSessionId('../etc/passwd'));
        $this->assertFalse(SessionInvalidator::isValidSessionId('abc/def'));
    }

    public function testDestroyByIdRejectsInvalidSessionId()
    {
        $this->assertFalse(SessionInvalidator::destroyById('../../etc/passwd'));
    }

    public function testDestroyByIdRemovesSessionFile()
    {
        $session = Yii::$app->session;
        $savePath = $session->savePath;
        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }

        $sessionId = 'abcdefghijklmnopqrstuvwxyz12';
        $file = rtrim($savePath, '/\\') . DIRECTORY_SEPARATOR . 'sess_' . $sessionId;
        file_put_contents($file, 'Anon.authData|a:1:{s:2:"id";i:1;}');
        $this->assertFileExists($file);

        $this->assertTrue(SessionInvalidator::destroyById($sessionId));
        $this->assertFileDoesNotExist($file);
    }

    public function testDestroyByIdSucceedsWhenFileAlreadyAbsent()
    {
        $this->assertTrue(SessionInvalidator::destroyById('zzzzzzzzzzzzzzzzzzzzzzzzzz'));
    }

    public function testSessionContentHasAnonIdExactMatch()
    {
        $idParam = '__testAnonId';
        $content = $idParam . '|i:682;' . 'other|s:1:"x";';
        $this->assertTrue(SessionInvalidator::sessionContentHasAnonId($content, $idParam, 682));
        $this->assertFalse(SessionInvalidator::sessionContentHasAnonId($content, $idParam, 68));
        $this->assertFalse(SessionInvalidator::sessionContentHasAnonId($content, $idParam, 6820));
    }

    public function testDestroyByCreatorIdsRemovesMatchingSessionFile()
    {
        $savePath = Yii::$app->session->savePath;
        if (!is_dir($savePath)) {
            mkdir($savePath, 0777, true);
        }

        $idParam = (string) Yii::$app->anon->idParam;
        $sessionId = 'abcdefghijklmnopqrstuvwxyz99';
        $file = rtrim($savePath, '/\\') . DIRECTORY_SEPARATOR . 'sess_' . $sessionId;
        file_put_contents($file, $idParam . '|i:682;foo|s:3:"bar";');
        $this->assertFileExists($file);

        $count = SessionInvalidator::destroyByCreatorIds([682]);
        $this->assertSame(1, $count);
        $this->assertFileDoesNotExist($file);
    }
}
