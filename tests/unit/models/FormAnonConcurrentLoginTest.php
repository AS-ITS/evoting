<?php

namespace app\tests\unit\models;

use Yii;
use app\models\FormAnon;
use app\models\Logins;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\ConfigFixture;
use Codeception\Test\Unit;

/**
 * 匿名登入併發 session 阻擋測試
 */
class FormAnonConcurrentLoginTest extends Unit
{
    protected function _before()
    {
        if (!Yii::$app->anon->isGuest) {
            Yii::$app->anon->logout();
        }
        Logins::deleteAll();
    }

    protected function _after()
    {
        if (!Yii::$app->anon->isGuest) {
            Yii::$app->anon->logout();
        }
        Logins::deleteAll();
    }

    public function _fixtures()
    {
        return [
            'votes' => VotesFixture::class,
            'passwords' => PasswordsFixture::class,
            'config' => ConfigFixture::class,
        ];
    }

    public function testRejectsLoginWhenPasswordHasActiveSessionElsewhere()
    {
        $passwordId = 1;
        $existingSessionId = 'existing_session_abc';

        $login = new Logins();
        $login->voteID = 'AnonPartyTest';
        $login->party = 'N';
        $login->creator = $passwordId;
        $login->session = $existingSessionId;
        $this->assertTrue($login->save(false));

        $sessionDir = Yii::getAlias('@runtime/sessions');
        if (!is_dir($sessionDir)) {
            mkdir($sessionDir, 0777, true);
        }
        touch($sessionDir . '/sess_' . $existingSessionId);

        $model = new FormAnon();
        $model->voteID = 'AnonPartyTest';
        $model->password = 'testN1';

        $this->assertFalse($model->login());
        $this->assertTrue($model->hasErrors('password'));
    }

    public function testAllowsLoginWhenOtherSessionIdleBeyondAuthTimeout()
    {
        $passwordId = 1;
        $existingSessionId = 'abandoned_session_def';

        $login = new Logins();
        $login->voteID = 'AnonPartyTest';
        $login->party = 'N';
        $login->creator = $passwordId;
        $login->session = $existingSessionId;
        $this->assertTrue($login->save(false));

        $sessionDir = Yii::getAlias('@runtime/sessions');
        if (!is_dir($sessionDir)) {
            mkdir($sessionDir, 0777, true);
        }
        $sessionFile = $sessionDir . '/sess_' . $existingSessionId;
        // 未完成投票即關閉瀏覽器：session 檔仍在，但已超過 anon 閒置逾時
        touch($sessionFile, time() - Yii::$app->anon->authTimeout - 60);

        try {
            $this->assertFalse(Logins::hasActiveSession($passwordId, 'another_device_session'));

            $model = new FormAnon();
            $model->voteID = 'AnonPartyTest';
            $model->password = 'testN1';

            $this->assertTrue($model->login());
            $this->assertFalse($model->hasErrors('password'));
        } finally {
            @unlink($sessionFile);
        }
    }

    public function testRecentlyActiveSessionElsewhereStillBlocks()
    {
        $passwordId = 1;
        $existingSessionId = 'active_session_ghi';

        $login = new Logins();
        $login->voteID = 'AnonPartyTest';
        $login->party = 'N';
        $login->creator = $passwordId;
        $login->session = $existingSessionId;
        $this->assertTrue($login->save(false));

        $sessionDir = Yii::getAlias('@runtime/sessions');
        if (!is_dir($sessionDir)) {
            mkdir($sessionDir, 0777, true);
        }
        $sessionFile = $sessionDir . '/sess_' . $existingSessionId;
        touch($sessionFile, time() - Yii::$app->anon->authTimeout + 120);

        try {
            $this->assertTrue(Logins::hasActiveSession($passwordId, 'another_device_session'));
        } finally {
            @unlink($sessionFile);
        }
    }

    public function testAllowsLoginWhenStaleSessionRecord()
    {
        $passwordId = 2;
        $existingSessionId = 'stale_session_xyz';

        $login = new Logins();
        $login->voteID = 'AnonPartyTest';
        $login->party = 'N';
        $login->creator = $passwordId;
        $login->session = $existingSessionId;
        $this->assertTrue($login->save(false));

        $model = new FormAnon();
        $model->voteID = 'AnonPartyTest';
        $model->password = 'testN2';

        $this->assertTrue($model->login());
    }
}
