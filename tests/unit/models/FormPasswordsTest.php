<?php

namespace app\tests\unit\models;

use Yii;
use app\models\FormPasswords;
use app\models\Passwords;
use app\models\Passwd;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\ConfigFixture;
use app\components\AdminIdentity;

/**
 * FormPasswords 模型測試
 * 測試密碼表單模型的各項功能
 */
class FormPasswordsTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // 載入 fixtures
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
            'round' => RoundFixture::class,
            'questions' => QuestionsFixture::class,
            'passwords' => PasswordsFixture::class,
        ]);

        // 確保登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }
        if (!Yii::$app->anon->isGuest) {
            Yii::$app->anon->logout();
        }
        Yii::$app->session->removeAll();
    }

    protected function _after()
    {
        // 清理：登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }
        if (!Yii::$app->anon->isGuest) {
            Yii::$app->anon->logout();
        }
        Yii::$app->session->removeAll();
    }

    /**
     * 輔助方法：建立並登入管理員
     */
    private function loginAdmin($cn, $name, $roles)
    {
        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => $cn,
            'name' => $name,
            'roles' => $roles,
        ]);
                Yii::$app->user->switchIdentity($identity);

        $auth = Yii::$app->authManager;
        $auth->revokeAll($cn);
        $role = $auth->getRole($roles);
        if ($role) {
            $auth->assign($role, $cn);
        }

        $this->assertNotNull(Yii::$app->user->identity, 'User identity should be set after login');
    }

    // ==================== 基本結構測試 ====================

    /**
     * 測試：FormPasswords 繼承 Passwords
     */
    public function testFormPasswordsExtendsPasswords()
    {
        $model = new FormPasswords();
        $this->assertInstanceOf(Passwords::class, $model, 'FormPasswords should extend Passwords');
    }

    /**
     * 測試：tableName 正確
     */
    public function testTableName()
    {
        $this->assertEquals('passwords', FormPasswords::tableName(), 'Table name should be "passwords"');
    }

    /**
     * 測試：voteInfo 屬性存在
     */
    public function testVoteInfoPropertyExists()
    {
        $model = new FormPasswords();
        $this->assertTrue(property_exists($model, 'voteInfo'), 'Should have voteInfo property');
    }

    // ==================== 驗證規則測試 ====================

    /**
     * 測試：search scenario 允許所有欄位
     */
    public function testSearchScenarioAllowsAllFields()
    {
        $model = new FormPasswords();
        $model->scenario = 'search';
        $model->party = 'def';
        $model->passwd = 'test123';
        $model->status = '1';
        $model->dtrack = '0';
        $model->voted = '0';
        $model->mark = 'test';

        $this->assertTrue($model->validate(), 'Search scenario should allow all fields');
    }

    /**
     * 測試：update scenario 允許狀態欄位
     */
    public function testUpdateScenarioAllowsStatusFields()
    {
        $model = new FormPasswords();
        $model->scenario = 'update';
        $model->status = '1';
        $model->dtrack = '0';
        $model->voted = '0';

        $this->assertTrue($model->validate(), 'Update scenario should allow status fields');
    }

    /**
     * 測試：create scenario 允許所有欄位
     */
    public function testCreateScenarioAllowsAllFields()
    {
        $model = new FormPasswords();
        $model->scenario = 'create';
        $model->voteID = 'TestVote';
        $model->party = 'def';
        $model->sn = 1;
        $model->passwd = 'encrypted_password';
        $model->status = '1';
        $model->dtrack = '0';
        $model->voted = '0';

        $this->assertTrue($model->validate(), 'Create scenario should allow all fields');
    }

    /**
     * 測試：sn 必須為整數
     */
    public function testSnMustBeInteger()
    {
        $model = new FormPasswords();
        $model->scenario = 'create';
        $model->sn = 'not_integer';

        $this->assertFalse($model->validate(['sn']), 'Should fail with non-integer sn');
        $this->assertArrayHasKey('sn', $model->errors);
    }

    /**
     * 測試：voteID 最大長度驗證
     */
    public function testVoteIdMaxLength()
    {
        $model = new FormPasswords();
        $model->scenario = 'create';
        $model->voteID = str_repeat('A', 21); // 超過 20 字元

        $this->assertFalse($model->validate(['voteID']), 'Should fail with voteID > 20 chars');
        $this->assertArrayHasKey('voteID', $model->errors);
    }

    /**
     * 測試：passwd 最大長度驗證
     */
    public function testPasswdMaxLength()
    {
        $model = new FormPasswords();
        $model->scenario = 'create';
        $model->passwd = str_repeat('A', 91); // 超過 90 字元

        $this->assertFalse($model->validate(['passwd']), 'Should fail with passwd > 90 chars');
        $this->assertArrayHasKey('passwd', $model->errors);
    }

    /**
     * 測試：party 最大長度驗證
     */
    public function testPartyMaxLength()
    {
        $model = new FormPasswords();
        $model->scenario = 'create';
        $model->party = str_repeat('A', 13); // 超過 12 字元

        $this->assertFalse($model->validate(['party']), 'Should fail with party > 12 chars');
        $this->assertArrayHasKey('party', $model->errors);
    }

    /**
     * 測試：status 最大長度驗證
     */
    public function testStatusMaxLength()
    {
        $model = new FormPasswords();
        $model->scenario = 'create';
        $model->status = 'AB'; // 超過 1 字元

        $this->assertFalse($model->validate(['status']), 'Should fail with status > 1 char');
        $this->assertArrayHasKey('status', $model->errors);
    }

    // ==================== search() 測試 ====================

    /**
     * 測試：search() 返回查詢物件
     */
    public function testSearchReturnsQuery()
    {
        $model = new FormPasswords();
        $query = $model->search('AnonPartyTest');

        $this->assertInstanceOf(\yii\db\ActiveQuery::class, $query, 'Should return ActiveQuery');
    }

    /**
     * 測試：search() 帶過濾參數
     */
    public function testSearchWithFilters()
    {
        $model = new FormPasswords();
        $params = [
            'FormPasswords' => [
                'party' => 'def',
                'status' => '1',
            ]
        ];
        $query = $model->search('AnonPartyTest', $params);

        $this->assertInstanceOf(\yii\db\ActiveQuery::class, $query);
    }

    // ==================== getPasswd() 測試 ====================

    /**
     * 測試：getPasswd() 查詢不存在的密碼
     */
    public function testGetPasswdNonExistent()
    {
        $model = new FormPasswords();
        $result = $model->getPasswd('AnonPartyTest', 'non_existent_password');

        $this->assertNull($result, 'Should return null for non-existent password');
    }

    /**
     * 測試：getPasswd() 查詢存在的密碼
     */
    public function testGetPasswdWithExistingPassword()
    {
        $model = new FormPasswords();
        // fixture 資料: testN1 是 AnonPartyTest 的第一筆密碼
        $result = $model->getPasswd('AnonPartyTest', 'testN1');

        $this->assertNotNull($result, 'Should return password info for existing password');
        $this->assertIsArray($result, 'Should return array');
        $this->assertEquals('AnonPartyTest', $result['voteID'], 'voteID should match');
        $this->assertEquals('N', $result['party'], 'party should match');
    }

    /**
     * 測試：getPasswd() 使用不同 status 參數
     */
    public function testGetPasswdWithDifferentStatus()
    {
        $model = new FormPasswords();

        // fixture 資料都是 status='1'，用 status='0' 應該找不到
        $result = $model->getPasswd('AnonPartyTest', 'testN1', '0');
        $this->assertNull($result, 'Should return null when status does not match');

        // 用 status='1' 應該找到
        $result = $model->getPasswd('AnonPartyTest', 'testN1', '1');
        $this->assertNotNull($result, 'Should return password when status matches');
    }

    // ==================== getPasswdFromId() 測試 ====================

    /**
     * 測試：getPasswdFromId() 以字串查詢不存在的 ID
     */
    public function testGetPasswdFromIdStringNonExistent()
    {
        $model = new FormPasswords();
        $result = $model->getPasswdFromId('99999');

        $this->assertNull($result, 'Should return null for non-existent ID');
    }

    /**
     * 測試：getPasswdFromId() 以陣列查詢
     */
    public function testGetPasswdFromIdArray()
    {
        $model = new FormPasswords();
        $result = $model->getPasswdFromId(['99998', '99999']);

        $this->assertIsArray($result, 'Should return array for ID array input');
    }

    // ==================== getPasswdInfo() 測試 ====================

    /**
     * 測試：getPasswdInfo() 以字串查詢不存在的 ID
     */
    public function testGetPasswdInfoStringNonExistent()
    {
        $model = new FormPasswords();
        $result = $model->getPasswdInfo('99999');

        $this->assertNull($result, 'Should return null for non-existent ID');
    }

    /**
     * 測試：getPasswdInfo() 以陣列查詢不存在的 ID
     */
    public function testGetPasswdInfoArrayNonExistent()
    {
        $model = new FormPasswords();
        $result = $model->getPasswdInfo(['99998', '99999']);

        $this->assertNull($result, 'Should return null for non-existent IDs');
    }

    /**
     * 測試：getPasswdInfo() 預設遮罩，不含明文
     */
    public function testGetPasswdInfoStringMasksPasswordByDefault()
    {
        $model = new FormPasswords();
        $result = $model->getPasswdInfo('1');

        $this->assertNotNull($result, 'Should return password info');
        $this->assertIsArray($result, 'Should return array');
        $this->assertEquals('密碼#1', $result['passwd'], 'Password should be masked');
        $this->assertEquals('AnonPartyTest', $result['voteID'], 'voteID should match');
    }

    /**
     * 測試：getPasswdInfo() 可選解密明文
     */
    public function testGetPasswdInfoStringDecryptsWhenRequested()
    {
        $model = new FormPasswords();
        $result = $model->getPasswdInfo('1', true);

        $this->assertNotNull($result, 'Should return password info');
        $this->assertEquals('testN1', $result['passwd'], 'Password should be decrypted');
    }

    /**
     * 測試：getPasswdInfo() 陣列預設遮罩
     */
    public function testGetPasswdInfoArrayMasksPasswordsByDefault()
    {
        $model = new FormPasswords();
        $result = $model->getPasswdInfo(['1', '2']);

        $this->assertNotNull($result, 'Should return password info array');
        $this->assertCount(2, $result, 'Should return 2 passwords');
        $this->assertEquals('密碼#1', $result[0]['passwd'], 'First password should be masked');
        $this->assertEquals('密碼#2', $result[1]['passwd'], 'Second password should be masked');
    }

    /**
     * 測試：getPasswdInfo() 陣列可選解密
     */
    public function testGetPasswdInfoArrayDecryptsWhenRequested()
    {
        $model = new FormPasswords();
        $result = $model->getPasswdInfo(['1', '2'], true);

        $this->assertNotNull($result, 'Should return password info array');
        $this->assertEquals('testN1', $result[0]['passwd'], 'First password should be decrypted');
        $this->assertEquals('testN2', $result[1]['passwd'], 'Second password should be decrypted');
    }

    /**
     * 測試：getPasswdFromId() 以字串查詢存在的 ID
     */
    public function testGetPasswdFromIdStringExistent()
    {
        $model = new FormPasswords();
        $result = $model->getPasswdFromId('1');

        $this->assertNotNull($result, 'Should return FormPasswords model');
        $this->assertInstanceOf(FormPasswords::class, $result, 'Should be FormPasswords instance');
        $this->assertEquals('AnonPartyTest', $result->voteID, 'voteID should match');
    }

    /**
     * 測試：getPasswdFromId() 以陣列查詢存在的 ID
     */
    public function testGetPasswdFromIdArrayExistent()
    {
        $model = new FormPasswords();
        $result = $model->getPasswdFromId(['1', '2']);

        $this->assertIsArray($result, 'Should return array');
        $this->assertCount(2, $result, 'Should return 2 passwords');
        $this->assertEquals('AnonPartyTest', $result[0]->voteID, 'First voteID should match');
    }

    // ==================== getVotePasswdList() 測試 ====================

    /**
     * 測試：getVotePasswdList() 返回陣列
     */
    public function testGetVotePasswdListReturnsArray()
    {
        $model = new FormPasswords();
        $result = $model->getVotePasswdList('AnonPartyTest');

        $this->assertIsArray($result, 'Should return array');
    }

    /**
     * 測試：getVotePasswdList() 查詢不存在的投票
     */
    public function testGetVotePasswdListNonExistent()
    {
        $model = new FormPasswords();
        $result = $model->getVotePasswdList('NonExistentVote');

        $this->assertIsArray($result, 'Should return empty array for non-existent vote');
        $this->assertEmpty($result, 'Array should be empty');
    }

    // ==================== getDataProvider() 測試 ====================

    /**
     * 測試：getDataProvider() 返回 DataProvider
     */
    public function testGetDataProviderReturnsDataProvider()
    {
        $model = new FormPasswords();
        $result = $model->getDataProvider('AnonPartyTest');

        $this->assertInstanceOf(\yii\data\ActiveDataProvider::class, $result, 'Should return ActiveDataProvider');
    }

    // ==================== toggleStatus() 測試 ====================

    /**
     * 測試：toggleStatus() 方法存在且可呼叫
     */
    public function testToggleStatusMethodExists()
    {
        $model = new FormPasswords();
        $this->assertTrue(method_exists($model, 'toggleStatus'), 'toggleStatus method should exist');
    }

    /**
     * 測試：toggleStatus() 狀態切換邏輯
     * 註：此測試依賴 fixture 資料，可能會跳過
     */
    public function testToggleStatusWithExistingPassword()
    {
        // 使用 model 方法取得密碼
        $model = new FormPasswords();
        $passwordsList = $model->getVotePasswdList('AnonPartyTest');

        if (empty($passwordsList)) {
            $this->markTestSkipped('No passwords found in fixtures for AnonPartyTest');
            return;
        }

        // 取得第一筆密碼的 ID，確保可以透過 findOne 找到
        $firstPassword = $passwordsList[0];
        // 將 ID 轉為字串，因為 getPasswdFromId 只處理字串類型的 ID
        $passwordId = strval($firstPassword->id);

        // 確認 findOne 可以找到
        $checkModel = FormPasswords::findOne($passwordId);
        if ($checkModel === null) {
            $this->markTestSkipped('Cannot find password by findOne with ID: ' . $passwordId);
            return;
        }

        $originalStatus = $checkModel->status;
        $newStatus = $model->toggleStatus($passwordId);

        // 狀態應該切換
        $expectedStatus = $originalStatus === '1' ? '0' : '1';
        $this->assertEquals($expectedStatus, $newStatus, 'Status should be toggled');
    }

    /**
     * 測試：toggleStatus() 雙向切換 (1→0→1)
     */
    public function testToggleStatusBidirectional()
    {
        $model = new FormPasswords();
        // 使用 fixture id=1，初始 status='1'
        $passwordId = '1';

        // 第一次切換: 1 → 0
        $newStatus = $model->toggleStatus($passwordId);
        $this->assertEquals('0', $newStatus, 'First toggle: status should be 0');

        // 驗證資料庫狀態
        $updated = FormPasswords::findOne($passwordId);
        $this->assertEquals('0', $updated->status, 'Database should reflect status 0');

        // 第二次切換: 0 → 1
        $newStatus = $model->toggleStatus($passwordId);
        $this->assertEquals('1', $newStatus, 'Second toggle: status should be 1');

        // 驗證資料庫狀態
        $updated->refresh();
        $this->assertEquals('1', $updated->status, 'Database should reflect status 1');
    }

    /**
     * 測試：toggleStatus() 無效 ID 回傳 false
     * 修正：PHP 8.x 相容，不存在時回傳 false 而非拋出 Warning
     */
    public function testToggleStatusWithInvalidIdThrowsError()
    {
        $model = new FormPasswords();

        $result = $model->toggleStatus('99999');
        $this->assertFalse($result);
    }

    // ==================== toggleDtrack() 測試 ====================

    /**
     * 測試：toggleDtrack() 方法存在且可呼叫
     */
    public function testToggleDtrackMethodExists()
    {
        $model = new FormPasswords();
        $this->assertTrue(method_exists($model, 'toggleDtrack'), 'toggleDtrack method should exist');
    }

    /**
     * 測試：toggleDtrack() 狀態切換邏輯
     * 註：此測試依賴 fixture 資料，可能會跳過
     */
    public function testToggleDtrackWithExistingPassword()
    {
        // 使用 model 方法取得密碼
        $model = new FormPasswords();
        $passwordsList = $model->getVotePasswdList('AnonPartyTest');

        if (empty($passwordsList)) {
            $this->markTestSkipped('No passwords found in fixtures for AnonPartyTest');
            return;
        }

        // 取得第一筆密碼的 ID，確保可以透過 findOne 找到
        $firstPassword = $passwordsList[0];
        // 將 ID 轉為字串，因為 getPasswdFromId 只處理字串類型的 ID
        $passwordId = strval($firstPassword->id);

        // 確認 findOne 可以找到
        $checkModel = FormPasswords::findOne($passwordId);
        if ($checkModel === null) {
            $this->markTestSkipped('Cannot find password by findOne with ID: ' . $passwordId);
            return;
        }

        $originalDtrack = $checkModel->dtrack;
        $newDtrack = $model->toggleDtrack($passwordId);

        // 雙軌狀態應該切換
        $expectedDtrack = $originalDtrack === '1' ? '0' : '1';
        $this->assertEquals($expectedDtrack, $newDtrack, 'Dtrack should be toggled');
    }

    /**
     * 測試：toggleDtrack() 雙向切換 (1→0→1)
     */
    public function testToggleDtrackBidirectional()
    {
        $model = new FormPasswords();
        // 使用 fixture id=2，初始 dtrack='1'
        $passwordId = '2';

        // 第一次切換: 1 → 0
        $newDtrack = $model->toggleDtrack($passwordId);
        $this->assertEquals('0', $newDtrack, 'First toggle: dtrack should be 0');

        // 驗證資料庫狀態
        $updated = FormPasswords::findOne($passwordId);
        $this->assertEquals('0', $updated->dtrack, 'Database should reflect dtrack 0');

        // 第二次切換: 0 → 1
        $newDtrack = $model->toggleDtrack($passwordId);
        $this->assertEquals('1', $newDtrack, 'Second toggle: dtrack should be 1');

        // 驗證資料庫狀態
        $updated->refresh();
        $this->assertEquals('1', $updated->dtrack, 'Database should reflect dtrack 1');
    }

    /**
     * 測試：toggleDtrack() 無效 ID 回傳 false
     * 修正：PHP 8.x 相容，不存在時回傳 false 而非拋出 Warning
     */
    public function testToggleDtrackWithInvalidIdThrowsError()
    {
        $model = new FormPasswords();

        $result = $model->toggleDtrack('99999');
        $this->assertFalse($result);
    }

    // ==================== DeletePasswd() 測試 ====================

    /**
     * 測試：DeletePasswd() 刪除不存在的密碼
     */
    public function testDeletePasswdNonExistent()
    {
        $model = new FormPasswords();
        $result = $model->DeletePasswd(99999);

        $this->assertFalse($result, 'Should return false for non-existent password');
    }

    /**
     * 測試：DeletePasswd() 成功刪除密碼
     */
    public function testDeletePasswdSuccess()
    {
        $model = new FormPasswords();
        $Passwd = new Passwd();

        // 先創建一筆測試密碼
        $testPasswd = $Passwd->encrypt('delete_test_pwd');
        $model->creationPasswd(
            'AnonPartyTest',
            [$testPasswd],
            'N',
            '1',
            '0',
            'delete_test'
        );

        // 找到剛創建的密碼
        $created = FormPasswords::find()
            ->where(['voteID' => 'AnonPartyTest', 'mark' => 'delete_test'])
            ->one();

        $this->assertNotNull($created, 'Test password should be created');
        $createdId = $created->id;

        // 執行刪除
        $result = $model->DeletePasswd($createdId);

        // 驗證刪除成功
        $this->assertNotFalse($result, 'Should return truthy value on successful deletion');

        // 驗證確實被刪除
        $deleted = FormPasswords::findOne($createdId);
        $this->assertNull($deleted, 'Password should be deleted from database');
    }

    // ==================== DeletePasswdAll() 測試 ====================

    /**
     * 測試：DeletePasswdAll() 刪除不存在的投票密碼
     */
    public function testDeletePasswdAllNonExistent()
    {
        $model = new FormPasswords();
        $result = $model->DeletePasswdAll('NonExistentVote');

        $this->assertEquals(0, $result, 'Should return 0 for non-existent vote');
    }

    /**
     * 測試：DeletePasswdAll() 帶分組參數
     */
    public function testDeletePasswdAllWithParty()
    {
        $model = new FormPasswords();
        $result = $model->DeletePasswdAll('NonExistentVote', 'def');

        $this->assertEquals(0, $result, 'Should return 0 with party parameter');
    }

    /**
     * 測試：DeletePasswdAll() 帶標記參數
     */
    public function testDeletePasswdAllWithMark()
    {
        $model = new FormPasswords();
        $result = $model->DeletePasswdAll('NonExistentVote', null, 'test_mark');

        $this->assertEquals(0, $result, 'Should return 0 with mark parameter');
    }

    /**
     * 測試：DeletePasswdAll() 成功刪除多筆密碼
     */
    public function testDeletePasswdAllSuccess()
    {
        $model = new FormPasswords();
        $Passwd = new Passwd();

        // 先創建多筆測試密碼
        $testPasswds = [
            $Passwd->encrypt('delete_all_test1'),
            $Passwd->encrypt('delete_all_test2'),
            $Passwd->encrypt('delete_all_test3'),
        ];
        $model->creationPasswd(
            'AnonPartyTest',
            $testPasswds,
            'N',
            '1',
            '0',
            'delete_all_test'
        );

        // 確認創建成功
        $createdCount = FormPasswords::find()
            ->where(['voteID' => 'AnonPartyTest', 'mark' => 'delete_all_test'])
            ->count();
        $this->assertEquals(3, $createdCount, 'Should have created 3 test passwords');

        // 執行刪除 (使用 mark)
        $result = $model->DeletePasswdAll('AnonPartyTest', null, 'delete_all_test');

        // 驗證刪除成功
        $this->assertEquals(3, $result, 'Should delete 3 passwords');

        // 驗證確實被刪除
        $remainingCount = FormPasswords::find()
            ->where(['voteID' => 'AnonPartyTest', 'mark' => 'delete_all_test'])
            ->count();
        $this->assertEquals(0, $remainingCount, 'All test passwords should be deleted');
    }

    /**
     * 測試：DeletePasswdAll() 帶分組參數實際刪除
     */
    public function testDeletePasswdAllWithPartySuccess()
    {
        $model = new FormPasswords();
        $Passwd = new Passwd();

        // 先創建測試密碼在特定分組
        $testPasswds = [
            $Passwd->encrypt('delete_party_test1'),
            $Passwd->encrypt('delete_party_test2'),
        ];
        $model->creationPasswd(
            'AnonPartyTest',
            $testPasswds,
            'TestParty',  // 使用特殊分組名稱
            '1',
            '0',
            'party_test'
        );

        // 確認創建成功
        $createdCount = FormPasswords::find()
            ->where(['voteID' => 'AnonPartyTest', 'party' => 'TestParty'])
            ->count();
        $this->assertEquals(2, $createdCount, 'Should have created 2 test passwords');

        // 執行刪除 (使用 party)
        $result = $model->DeletePasswdAll('AnonPartyTest', 'TestParty');

        // 驗證刪除成功
        $this->assertEquals(2, $result, 'Should delete 2 passwords');

        // 驗證確實被刪除
        $remainingCount = FormPasswords::find()
            ->where(['voteID' => 'AnonPartyTest', 'party' => 'TestParty'])
            ->count();
        $this->assertEquals(0, $remainingCount, 'All test passwords in party should be deleted');
    }

    // ==================== genPasswd() 測試 ====================

    /**
     * 測試：genPasswd() 生成單一密碼
     */
    public function testGenPasswdSingle()
    {
        $model = new FormPasswords();
        $result = $model->genPasswd('TestVote', 1, 6);

        $this->assertIsArray($result, 'Should return array');
        $this->assertCount(1, $result, 'Should generate 1 password');
    }

    /**
     * 測試：genPasswd() 生成多個密碼
     */
    public function testGenPasswdMultiple()
    {
        $model = new FormPasswords();
        $result = $model->genPasswd('TestVote', 5, 6);

        $this->assertIsArray($result, 'Should return array');
        $this->assertCount(5, $result, 'Should generate 5 passwords');
    }

    /**
     * 測試：genPasswd() 生成的密碼是唯一的
     */
    public function testGenPasswdUnique()
    {
        $model = new FormPasswords();
        $result = $model->genPasswd('TestVote', 10, 6);

        $uniqueResult = array_unique($result);
        $this->assertCount(count($result), $uniqueResult, 'All passwords should be unique');
    }

    /**
     * 測試：genPasswd() 使用不同類型
     */
    public function testGenPasswdWithDifferentTypes()
    {
        $model = new FormPasswords();

        // 測試數字類型 (TYPE_INT)
        $numericResult = $model->genPasswd('TestVote', 1, 6, Passwd::TYPE_INT);
        $this->assertCount(1, $numericResult);

        // 測試字母類型 (TYPE_EN)
        $alphaResult = $model->genPasswd('TestVote', 1, 6, Passwd::TYPE_EN);
        $this->assertCount(1, $alphaResult);
    }

    // ==================== getNewBallotChanger() 測試 ====================

    /**
     * 測試：getNewBallotChanger() 返回格式化陣列
     */
    public function testGetNewBallotChangerReturnsFormattedArray()
    {
        $this->loginAdmin('va_test', 'VA Test', 'va');

        $model = new FormPasswords();
        $result = $model->getNewBallotChanger(['va_test']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('', $result);
        $this->assertEquals('無', $result['']);
    }

    // ==================== checkBindVote() 測試 ====================

    /**
     * 測試：checkBindVote() 檢查非共用密碼投票
     */
    public function testCheckBindVoteNonBind()
    {
        $model = new FormPasswords();
        $result = $model->checkBindVote('AnonPartyTest');

        // 預設測試資料應該不是共用密碼，結果可能是 bool 或 '0'/'1' 字串
        $this->assertTrue($result === false || $result === true || $result === '0' || $result === '1', 'Should return boolean-like value');
    }

    /**
     * 測試：checkBindVote() 設定 voteInfo 屬性
     */
    public function testCheckBindVoteSetsVoteInfoProperty()
    {
        $model = new FormPasswords();

        // 確認初始值為 null
        $this->assertNull($model->voteInfo, 'voteInfo should be null initially');

        // 執行 checkBindVote
        $model->checkBindVote('AnonPartyTest');

        // 確認 voteInfo 被設定
        $this->assertNotNull($model->voteInfo, 'voteInfo should be set after checkBindVote');
        $this->assertEquals('AnonPartyTest', $model->voteInfo->voteID, 'voteInfo should contain correct vote');
    }

    /**
     * 測試：getBindWhichVote() 方法存在
     */
    public function testGetBindWhichVoteMethodExists()
    {
        $model = new FormPasswords();
        $this->assertTrue(method_exists($model, 'getBindWhichVote'), 'getBindWhichVote method should exist');
    }

    /**
     * 測試：getBindWhichVote() 需要先呼叫 checkBindVote
     */
    public function testGetBindWhichVoteAfterCheckBindVote()
    {
        $model = new FormPasswords();

        // 先呼叫 checkBindVote 以設定 voteInfo
        $model->checkBindVote('AnonPartyTest');

        // 呼叫 getBindWhichVote
        $result = $model->getBindWhichVote();

        // 非共用密碼的投票，bindWhichVote 應為空或 null
        $this->assertTrue(
            $result === null || $result === '' || is_string($result),
            'getBindWhichVote should return string or null'
        );
    }

    // ==================== setVote() 測試 ====================

    /**
     * 測試：setVote() 方法存在且可呼叫
     */
    public function testSetVoteMethodExists()
    {
        $model = new FormPasswords();
        $this->assertTrue(method_exists($model, 'setVote'), 'setVote method should exist');
    }

    /**
     * 測試：setVote() 設定投票狀態
     * 註：此測試依賴 fixture 資料，可能會跳過
     */
    public function testSetVoteWithExistingPassword()
    {
        // 使用 model 方法取得密碼
        $model = new FormPasswords();
        $passwordsList = $model->getVotePasswdList('AnonPartyTest');

        if (empty($passwordsList)) {
            $this->markTestSkipped('No passwords found in fixtures for AnonPartyTest');
            return;
        }

        // 取得第一筆密碼的 ID，確保可以透過 findOne 找到
        $firstPassword = $passwordsList[0];
        // 將 ID 轉為字串，因為 getPasswdFromId 只處理字串類型的 ID
        $passwordId = strval($firstPassword->id);

        // 確認 findOne 可以找到
        $checkModel = FormPasswords::findOne($passwordId);
        if ($checkModel === null) {
            $this->markTestSkipped('Cannot find password by findOne with ID: ' . $passwordId);
            return;
        }

        $result = $model->setVote($passwordId, '1');

        $this->assertTrue($result, 'Should return true on successful update');

        // 驗證更新
        $updated = Passwords::findOne($passwordId);
        $this->assertEquals('1', $updated->voted, 'Voted status should be updated');
    }

    // ==================== creationPasswd() 測試 ====================

    /**
     * 測試：creationPasswd() 創建密碼
     */
    public function testCreationPasswd()
    {
        $model = new FormPasswords();
        $Passwd = new Passwd();

        // 生成加密的密碼
        $passwdAry = [
            $Passwd->encrypt('testpwd1'),
            $Passwd->encrypt('testpwd2'),
        ];

        // 使用存在的 voteID 以避免外鍵約束錯誤
        $result = $model->creationPasswd(
            'AnonPartyTest', // 使用存在的 voteID
            $passwdAry,
            'def',           // party
            '1',             // status
            '0',             // dtrack
            'unit_test'      // mark - 用於識別測試資料
        );

        $this->assertTrue($result, 'Should return true on successful creation');

        // 清理測試資料
        $model->DeletePasswdAll('AnonPartyTest', null, 'unit_test');
    }

    /**
     * 測試：creationPasswd() 驗證 sn 連續性
     */
    public function testCreationPasswdSnSequence()
    {
        $model = new FormPasswords();
        $Passwd = new Passwd();

        // 創建密碼在新的分組，確保 sn 從 1 開始
        $passwdAry = [
            $Passwd->encrypt('seq_test1'),
            $Passwd->encrypt('seq_test2'),
            $Passwd->encrypt('seq_test3'),
        ];

        $result = $model->creationPasswd(
            'AnonPartyTest',
            $passwdAry,
            'SeqTest',       // 使用新分組
            '1',
            '0',
            'sn_seq_test'
        );

        $this->assertTrue($result, 'Should return true on successful creation');

        // 驗證 sn 是連續的
        $passwords = FormPasswords::find()
            ->where(['voteID' => 'AnonPartyTest', 'party' => 'SeqTest'])
            ->orderBy(['sn' => SORT_ASC])
            ->all();

        $this->assertCount(3, $passwords, 'Should have 3 passwords');
        $this->assertEquals(1, $passwords[0]->sn, 'First sn should be 1');
        $this->assertEquals(2, $passwords[1]->sn, 'Second sn should be 2');
        $this->assertEquals(3, $passwords[2]->sn, 'Third sn should be 3');

        // 清理測試資料
        $model->DeletePasswdAll('AnonPartyTest', 'SeqTest');
    }

    /**
     * 測試：creationPasswd() 填補缺失的 sn
     */
    public function testCreationPasswdFillsMissingSn()
    {
        $model = new FormPasswords();
        $Passwd = new Passwd();

        // 先創建 3 筆密碼
        $passwdAry1 = [
            $Passwd->encrypt('fill_test1'),
            $Passwd->encrypt('fill_test2'),
            $Passwd->encrypt('fill_test3'),
        ];

        $model->creationPasswd(
            'AnonPartyTest',
            $passwdAry1,
            'FillTest',
            '1',
            '0',
            'fill_sn_test'
        );

        // 刪除 sn=2 的密碼，製造缺口
        $toDelete = FormPasswords::find()
            ->where(['voteID' => 'AnonPartyTest', 'party' => 'FillTest', 'sn' => 2])
            ->one();
        if ($toDelete) {
            $toDelete->delete();
        }

        // 確認 sn=2 被刪除
        $remaining = FormPasswords::find()
            ->where(['voteID' => 'AnonPartyTest', 'party' => 'FillTest'])
            ->orderBy(['sn' => SORT_ASC])
            ->all();
        $snValues = array_map(function($p) { return $p->sn; }, $remaining);
        $this->assertEquals([1, 3], $snValues, 'Should have sn 1 and 3 remaining');

        // 創建新密碼，應該填補 sn=2 的缺口
        $passwdAry2 = [
            $Passwd->encrypt('fill_test4'),
        ];

        $model->creationPasswd(
            'AnonPartyTest',
            $passwdAry2,
            'FillTest',
            '1',
            '0',
            'fill_sn_test'
        );

        // 驗證 sn=2 被填補
        $afterFill = FormPasswords::find()
            ->where(['voteID' => 'AnonPartyTest', 'party' => 'FillTest'])
            ->orderBy(['sn' => SORT_ASC])
            ->all();
        $snValuesAfter = array_map(function($p) { return $p->sn; }, $afterFill);
        $this->assertEquals([1, 2, 3], $snValuesAfter, 'Should fill sn=2 gap');

        // 清理測試資料
        $model->DeletePasswdAll('AnonPartyTest', 'FillTest');
    }

    /**
     * 測試：creationPasswd() 驗證屬性正確設定
     */
    public function testCreationPasswdSetsAttributesCorrectly()
    {
        $model = new FormPasswords();
        $Passwd = new Passwd();

        $passwdAry = [
            $Passwd->encrypt('attr_test1'),
        ];

        $model->creationPasswd(
            'AnonPartyTest',
            $passwdAry,
            'AttrTest',
            '0',             // status = 0 (未啟用)
            '1',             // dtrack = 1 (雙軌)
            'attr_test_mark'
        );

        // 驗證屬性
        $created = FormPasswords::find()
            ->where(['voteID' => 'AnonPartyTest', 'party' => 'AttrTest'])
            ->one();

        $this->assertNotNull($created, 'Password should be created');
        $this->assertEquals('0', $created->status, 'status should be 0');
        $this->assertEquals('1', $created->dtrack, 'dtrack should be 1');
        $this->assertEquals('0', $created->voted, 'voted should be 0');
        $this->assertEquals('attr_test_mark', $created->mark, 'mark should match');

        // 清理測試資料
        $model->DeletePasswdAll('AnonPartyTest', 'AttrTest');
    }

    // ==================== setVote() 補充測試 ====================

    /**
     * 測試：setVote() 設定已投票狀態
     */
    public function testSetVoteToVoted()
    {
        $model = new FormPasswords();
        // fixture id=3 初始 voted='0'
        $passwordId = '3';

        // 設定為已投票
        $result = $model->setVote($passwordId, '1');

        $this->assertTrue($result, 'Should return true on successful update');

        // 驗證資料庫狀態
        $updated = FormPasswords::findOne($passwordId);
        $this->assertEquals('1', $updated->voted, 'voted should be 1');

        // 還原狀態
        $model->setVote($passwordId, '0');
    }

    /**
     * 測試：setVote() 設定未投票狀態
     */
    public function testSetVoteToNotVoted()
    {
        $model = new FormPasswords();
        // 先設定為已投票
        $passwordId = '4';
        $model->setVote($passwordId, '1');

        // 設定為未投票
        $result = $model->setVote($passwordId, '0');

        $this->assertTrue($result, 'Should return true on successful update');

        // 驗證資料庫狀態
        $updated = FormPasswords::findOne($passwordId);
        $this->assertEquals('0', $updated->voted, 'voted should be 0');
    }

    // ==================== search() 補充測試 ====================

    /**
     * 測試：search() 驗證密碼加密比對
     */
    public function testSearchWithPasswordFilter()
    {
        $model = new FormPasswords();
        // 搜尋 testN1 密碼
        $params = [
            'FormPasswords' => [
                'passwd' => 'testN1',
            ]
        ];
        $query = $model->search('AnonPartyTest', $params);
        $results = $query->all();

        $this->assertCount(1, $results, 'Should find exactly 1 password');
        $this->assertEquals('1', $results[0]->sn, 'Should find sn=1');
    }

    /**
     * 測試：search() 驗證無效時返回原始查詢
     */
    public function testSearchWithInvalidValidationReturnsQuery()
    {
        $model = new FormPasswords();
        // 強制設置無效值（超過最大長度）
        $params = [
            'FormPasswords' => [
                'status' => 'invalid_too_long_status',
            ]
        ];
        $query = $model->search('AnonPartyTest', $params);

        // 應該仍返回查詢物件
        $this->assertInstanceOf(\yii\db\ActiveQuery::class, $query, 'Should return ActiveQuery even with invalid params');
    }

    // ==================== genPasswd() 補充測試 ====================

    /**
     * 測試：genPasswd() 驗證長度參數
     */
    public function testGenPasswdWithDifferentLengths()
    {
        $model = new FormPasswords();
        $Passwd = new Passwd();

        // 測試 8 位數密碼
        $result = $model->genPasswd('TestVote', 1, 8);
        $this->assertCount(1, $result);

        // 解密並檢查長度（v1 加密後的密文長度不同於原始長度）
        $decrypted = $Passwd->decrypt($result[0], null, null, Passwd::CRYPTO_V1);
        $this->assertEquals(8, strlen($decrypted), 'Decrypted password should be 8 characters');
    }

    /**
     * 測試：getNewBallotChanger() 使用不存在的 ID
     */
    public function testGetNewBallotChangerWithNonExistentId()
    {
        $this->loginAdmin('va_test', 'VA Test', 'va');

        $model = new FormPasswords();
        // 傳入不存在的密碼 ID
        $result = $model->getNewBallotChanger(['99999']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('', $result);
        $this->assertEquals('無', $result['']);
    }
}
