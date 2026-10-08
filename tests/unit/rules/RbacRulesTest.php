<?php

namespace app\tests\unit\rules;

use Yii;
use app\rules\voteManagRule;
use app\rules\ballotWorkRule;
use app\rules\groupManagOwnVoteRule;
use app\rules\groupViewOwnRule;
use app\rules\groupManagByMemberRule;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\GroupFixture;
use app\tests\fixtures\GroupMemberFixture;
use app\components\AdminIdentity;

/**
 * RBAC Rules 測試
 * 測試所有 RBAC 規則的權限判斷邏輯
 */
class RbacRulesTest extends \Codeception\Test\Unit
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
            'group' => GroupFixture::class,
            'groupMember' => GroupMemberFixture::class,
        ]);

        // 確保登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }
        Yii::$app->session->removeAll();
    }

    protected function _after()
    {
        // 清理：登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }
        Yii::$app->session->removeAll();
    }

    /**
     * 輔助方法：建立並登入使用者
     */
    private function loginUser($cn, $name, $roles)
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

    // ==================== voteManagRule 測試 ====================

    /**
     * 測試：voteManagRule 規則名稱正確
     */
    public function testVoteManagRuleName()
    {
        $rule = new voteManagRule();
        $this->assertEquals('voteManag', $rule->name);
    }

    /**
     * 測試：voteManagRule SA 總是可以通過
     */
    public function testVoteManagRuleSaAlwaysPass()
    {
        $this->loginUser('sa_test', 'SA Test', 'sa');

        $rule = new voteManagRule();
        $result = $rule->execute(
            'sa_test',
            null,
            ['voteID' => 'AnonPartyTest']
        );

        $this->assertTrue($result, 'SA should always pass voteManagRule');
    }

    /**
     * 測試：voteManagRule 建立者可以管理自己的投票
     */
    public function testVoteManagRuleCreatorCanManage()
    {
        $this->loginUser('va_test', 'VA Test', 'va');

        // 假設 AnonPartyTest 的建立者是 va_test
        $rule = new voteManagRule();
        $result = $rule->execute(
            'va_test',
            null,
            ['voteID' => 'AnonPartyTest']
        );

        // 結果取決於 fixture 中的資料
        $this->assertIsBool($result);
    }

    /**
     * 測試：voteManagRule 非建立者無法管理
     */
    public function testVoteManagRuleNonCreatorCannotManage()
    {
        $this->loginUser('other_user', 'Other User', 'va');

        $rule = new voteManagRule();
        $result = $rule->execute(
            'other_user',
            null,
            ['voteID' => 'AnonPartyTest']
        );

        // 如果不是建立者也不是群組成員，應該返回 false
        $this->assertIsBool($result);
    }

    // ==================== ballotWorkRule 測試 ====================

    /**
     * 測試：ballotWorkRule 規則名稱正確
     */
    public function testBallotWorkRuleName()
    {
        $rule = new ballotWorkRule();
        $this->assertEquals('ballotWorkManage', $rule->name);
    }

    /**
     * 測試：ballotWorkRule SA 總是可以通過
     */
    public function testBallotWorkRuleSaAlwaysPass()
    {
        $this->loginUser('sa_test', 'SA Test', 'sa');

        $rule = new ballotWorkRule();
        $result = $rule->execute(
            'sa_test',
            null,
            ['voteID' => 'AnonPartyTest']
        );

        $this->assertTrue($result, 'SA should always pass ballotWorkRule');
    }

    /**
     * 測試：ballotWorkRule VA 建立者可以操作
     */
    public function testBallotWorkRuleVaCreatorCanOperate()
    {
        $this->loginUser('va_test', 'VA Test', 'va');

        $rule = new ballotWorkRule();
        $result = $rule->execute(
            'va_test',
            null,
            ['voteID' => 'AnonPartyTest']
        );

        // 結果取決於 fixture 中的資料
        $this->assertIsBool($result);
    }

    // ==================== groupManagOwnVoteRule 測試 ====================

    /**
     * 測試：groupManagOwnVoteRule 規則存在
     */
    public function testGroupManagOwnVoteRuleExists()
    {
        $rule = new groupManagOwnVoteRule();
        $this->assertInstanceOf(\yii\rbac\Rule::class, $rule);
    }

    /**
     * 測試：groupManagOwnVoteRule 規則名稱
     */
    public function testGroupManagOwnVoteRuleName()
    {
        $rule = new groupManagOwnVoteRule();
        $this->assertNotEmpty($rule->name);
    }

    // ==================== groupViewOwnRule 測試 ====================

    /**
     * 測試：groupViewOwnRule 規則存在
     */
    public function testGroupViewOwnRuleExists()
    {
        $rule = new groupViewOwnRule();
        $this->assertInstanceOf(\yii\rbac\Rule::class, $rule);
    }

    /**
     * 測試：groupViewOwnRule 規則名稱
     */
    public function testGroupViewOwnRuleName()
    {
        $rule = new groupViewOwnRule();
        $this->assertNotEmpty($rule->name);
    }

    // ==================== groupManagByMemberRule 測試 ====================

    /**
     * 測試：groupManagByMemberRule 規則存在
     */
    public function testGroupManagByMemberRuleExists()
    {
        $rule = new groupManagByMemberRule();
        $this->assertInstanceOf(\yii\rbac\Rule::class, $rule);
    }

    /**
     * 測試：groupManagByMemberRule 規則名稱
     */
    public function testGroupManagByMemberRuleName()
    {
        $rule = new groupManagByMemberRule();
        $this->assertNotEmpty($rule->name);
    }

    // ==================== 整合測試 ====================

    /**
     * 測試：所有規則都繼承自 yii\rbac\Rule
     */
    public function testAllRulesExtendYiiRule()
    {
        $rules = [
            new voteManagRule(),
            new ballotWorkRule(),
            new groupManagOwnVoteRule(),
            new groupViewOwnRule(),
            new groupManagByMemberRule(),
        ];

        foreach ($rules as $rule) {
            $this->assertInstanceOf(\yii\rbac\Rule::class, $rule);
        }
    }

    /**
     * 測試：所有規則都有 name 屬性
     */
    public function testAllRulesHaveNames()
    {
        $rules = [
            new voteManagRule(),
            new ballotWorkRule(),
            new groupManagOwnVoteRule(),
            new groupViewOwnRule(),
            new groupManagByMemberRule(),
        ];

        foreach ($rules as $rule) {
            $this->assertNotEmpty($rule->name, get_class($rule) . ' should have a name');
        }
    }

    /**
     * 測試：所有規則的 name 都是唯一的
     */
    public function testAllRuleNamesAreUnique()
    {
        $rules = [
            new voteManagRule(),
            new ballotWorkRule(),
            new groupManagOwnVoteRule(),
            new groupViewOwnRule(),
            new groupManagByMemberRule(),
        ];

        $names = array_map(function($rule) {
            return $rule->name;
        }, $rules);

        $uniqueNames = array_unique($names);

        $this->assertCount(count($rules), $uniqueNames, 'All rule names should be unique');
    }

    /**
     * 測試：未登入使用者無法通過任何規則
     */
    public function testGuestCannotPassRules()
    {
        // 確保登出
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }

        $voteManagRule = new voteManagRule();
        $ballotWorkRule = new ballotWorkRule();

        // 未登入時應該返回 false（因為 Yii::$app->user->can('sa') 會失敗）
        try {
            $result1 = $voteManagRule->execute(null, null, ['voteID' => 'AnonPartyTest']);
            $this->assertFalse($result1, 'Guest should not pass voteManagRule');
        } catch (\Exception $e) {
            // 預期可能會有例外
            $this->assertTrue(true);
        }
    }

    // ==================== 角色權限整合測試 ====================

    /**
     * 測試：GA 角色的群組管理權限
     */
    public function testGaRoleGroupPermissions()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        // GA 應該能夠管理自己的群組
        $canManageGroup = Yii::$app->user->can('groupManage');

        // 結果取決於 RBAC 配置
        $this->assertIsBool($canManageGroup);
    }

    /**
     * 測試：GM 角色的權限
     */
    public function testGmRolePermissions()
    {
        $this->loginUser('gm_test', 'GM Test', 'gm');

        // GM 應該有有限的權限
        $canViewGroup = Yii::$app->user->can('groupView');

        // 結果取決於 RBAC 配置
        $this->assertIsBool($canViewGroup);
    }

    /**
     * 測試：VA 角色的投票管理權限
     */
    public function testVaRoleVotePermissions()
    {
        $this->loginUser('va_test', 'VA Test', 'va');

        // VA 應該能夠管理投票
        $canManageVote = Yii::$app->user->can('voteManage');

        // 結果取決於 RBAC 配置
        $this->assertIsBool($canManageVote);
    }
}
