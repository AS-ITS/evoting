<?php

namespace app\tests\unit\models;

use app\models\FormTotpSetup;
use app\models\Users;
use app\tests\fixtures\UsersFixture;
use Codeception\Test\Unit;

class FormTotpSetupTest extends Unit
{
    public function _fixtures()
    {
        return [
            'users' => UsersFixture::class,
        ];
    }

    public function testSupportsTotpColumnsDetectsSchema()
    {
        $this->assertIsBool(FormTotpSetup::supportsTotpColumns());
    }

    public function testGenerateRequiresValidPassword()
    {
        if (!FormTotpSetup::supportsTotpColumns()) {
            $this->markTestSkipped('totp columns not migrated');
        }

        $user = Users::findOne('admin');
        if ($user === null) {
            $this->markTestSkipped('admin user not in fixture');
        }

        $model = new FormTotpSetup(['step' => FormTotpSetup::STEP_GENERATE, 'password' => 'wrong-password']);
        $this->assertFalse($model->process($user));
        $this->assertTrue($model->hasErrors('password'));
    }
}
