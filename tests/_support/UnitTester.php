<?php

use app\tests\interfaces\VoteInterfaces;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class UnitTester extends \Codeception\Actor implements VoteInterfaces
{
    use _generated\UnitTesterActions;

    /**
     * 後臺使用者登入
     *
     * @param  string $role 角色
     * @return void
     */
    public function userLogin($role='va')
    {
        $user = new \app\components\AdminIdentity;
        $user->setAuthData([
            'UID' => 10001,
            'name' => 'test_va',
            'family_name' => 'Test',
            'given_name' => 'VA',
            'user_name' => 'test_va',
            'domain' => '',
            'email' => 'testuser@example.com',
            'phone' => '',
            'group' => [
                0 => 'testers',
                1 => 'AllUser'
            ],
            'member_of' => [
                0 => 'testers',
                1 => 'AllUser'
            ],
            'nonce' => 'test_nonce_placeholder',
            'aud' => 'test-client-id',
            'exp' => 1732761337,
            'iat' => 1732754137,
            'iss' => 'https://idp.example.com/oauth',
            'sub' => '10001',
            'cn' => 'test_va',
        ]);
        $user->setRole($role);
        Yii::$app->user->login($user);
        $this->assertFalse(Yii::$app->user->isGuest);
    }

    /**
     * 匿名投票者登入
     *
     * @param  object|array $password 密碼資訊
     * @return void
     */
    public function anonLogin($password)
    {
        // $anon = new app\components\Anon();
        // $anon->setAuthNData($password);
        // $anon->setAuthZData(['voting' => $password]);
        // Yii::$app->anon->login($anon);

        $passwd = new app\models\Passwd();
        $model = new app\models\FormAnon();
        $model->voteID = $password['voteID'];
        $model->password = $passwd->decrypt($password['passwd']);
        $model->login();
        $this->assertFalse(Yii::$app->anon->isGuest);
    }
    

    /**
     * 以既有 AdminIdentity 登入（勿再 round-trip getIdentity()）
     */
    public function loginAdminIdentity(\app\components\AdminIdentity $identity, int $duration = 0): void
    {
        \Yii::$app->user->login($identity, $duration);
        $this->assertFalse(\Yii::$app->user->isGuest);
    }

    /**
     * 以既有 AdminIdentity switch（略過 afterLogin 事件時用）
     */
    public function switchAdminIdentity(\app\components\AdminIdentity $identity): void
    {
        \Yii::$app->user->switchIdentity($identity);
        $this->assertNotNull(\Yii::$app->user->identity);
    }

    /**
     * 以既有 Anon identity 登入
     */
    public function loginAnonIdentity(\app\components\Anon $identity, ?int $duration = null): void
    {
        \Yii::$app->anon->login($identity, $duration ?? \Yii::$app->anon->authTimeout);
        $this->assertFalse(\Yii::$app->anon->isGuest);
    }

    /**
     * 檢查是否錯誤
     *
     * @param  int $num 錯誤數量
     * @param  array $messages 預期錯誤訊息
     * @return void
     */
    public function assertErrors($num, $messages)
    {
        // 確認 Yii session 中是否有 'error' flash message
        $this->assertTrue(Yii::$app->session->hasFlash('error'));
        // 從 Yii session 中取得 'error' flash message，並檢查其錯誤數量是否為 $num
        $errors = Yii::$app->session->getFlash('error');
        // $this->assertEquals([], $errors);
        $this->assertEquals($num, count($errors));
        // 使用迴圈檢查每個錯誤是否都包含在預期的錯誤陣列中
        foreach ($errors as $error) {
            $this->assertContains($error, $messages);
        }
        // 清空 Yii session 中的 'error' flash message
        Yii::$app->session->removeFlash('error');
    }
}
