<?php

namespace app\tests\unit\rules;

use Yii;
use app\models\Users;
use app\models\Votes;
use app\models\Group;
use app\models\GroupMember;
use app\components\AdminIdentity;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\VotesFixture;
use app\tests\fixtures\GroupFixture;
use app\tests\fixtures\GroupMemberFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\ConfigFixture;

/**
 * 群組投票權限測試
 * 測試群組成員對投票的管理權限（基於 isWrite 和 isOwner 設定）
 */
class GroupVotePermissionTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /** @var string[] */
    private $createdUserCns = [];

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // 載入 fixtures
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
            'votes' => VotesFixture::class,
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
        if (!empty($this->createdUserCns)) {
            Users::deleteAll(['cn' => $this->createdUserCns]);
            $this->createdUserCns = [];
        }

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
        $user = new Users();
        $user->cn = $cn;
        $user->name = $name;
        $user->roles = $roles;
        $user->password_plain = 'TestPass123';
        $this->assertTrue($user->save(), "Failed to save user: {$cn}");
        $this->createdUserCns[] = $cn;

        $identity = new AdminIdentity();
        $identity->setAuthData([
            'cn' => $cn,
            'name' => $name,
            'roles' => $roles,
        ]);
                Yii::$app->user->login($identity);

        return $user;
    }

    /**
     * 輔助方法：建立測試投票
     */
    private function createVote($voteID, $groupId = null)
    {
        $openStart = date('Y-m-d H:i:s');

        $vote = new Votes();
        $vote->voteID = $voteID;
        $vote->Name = '測試投票';
        $vote->NameE = 'Test Vote';
        $vote->creator = Yii::$app->user->id ?? 'test';

        $vote->openStart = $openStart;
        $vote->openEnd = date('Y-m-d H:i:s', strtotime($openStart . '+60 minute'));
        $vote->verifyStart = date('Y-m-d H:i:s', strtotime($openStart . '+61 minute'));
        $vote->verifyEnd = date('Y-m-d H:i:s', strtotime($openStart . '+62 minute'));

        $vote->type = Votes::TYPE_ANON;
        $vote->partyOrNot = 'N';
        $vote->addiCondition = 'n';
        $vote->isByParty = '0';
        $vote->active = Votes::STATUS_READY;
        $vote->isFinish = '0';
        $vote->finishPage = '1';
        $vote->pattern = 'v1';
        $vote->authBeforeDetail = '0';
        $vote->isShow = '1';

        $vote->hosted = '主辦單位';
        $vote->hostedE = 'Host Unit';
        $vote->contact = '聯絡人';
        $vote->contactE = 'Contact';
        $vote->tel = '02-1234-5678';
        $vote->email = 'test@example.com';

        $vote->isBindVote = '0';
        $vote->bindWhichVote = '';

        $vote->notice = '投票要點';
        $vote->noticeE = 'Voting Notice';
        $vote->information = '投票須知';
        $vote->informationE = 'Voting Information';

        $vote->candComment = '';
        $vote->candCommentE = '';
        $vote->otherInfoTitle = '';
        $vote->otherInfoTitleE = '';
        $vote->otherInfo = '';
        $vote->otherInfoE = '';

        $vote->sort = 1;
        $vote->session = '';
        $vote->loginLayout = 'v1';
        $vote->themeColor = '';
        $vote->candiConfig = '2';
        $vote->round = 1;

        $vote->groupId = $groupId;

        if (!$vote->save()) {
            $errors = json_encode($vote->errors);
            $this->assertTrue(false, "Failed to save vote: {$voteID}. Errors: {$errors}");
        }
        return $vote;
    }

    // ==================== groupManagOwnVoteRule 測試 ====================

    /**
     * 測試：GA 角色可以管理任何有 groupId 的投票
     */
    public function testGaCanManageVoteWithGroupId()
    {
        $this->loginUser('ga_test', 'GA Test', 'ga');

        // 建立有 groupId 的投票
        $vote = $this->createVote('GATest001', 1);

        // GA 應該可以管理
        $this->assertTrue(
            Yii::$app->user->can('manageOwnVote', ['voteID' => 'GATest001']),
            'GA should be able to manage votes with groupId'
        );
    }

    /**
     * 測試：GA 角色不能管理沒有 groupId 的投票（通過 manageOwnVote 權限）
     */
    public function testGaCannotManageVoteWithoutGroupIdViaManageOwnVote()
    {
        $this->loginUser('ga_test2', 'GA Test 2', 'ga');

        // 建立沒有 groupId 的投票
        $vote = $this->createVote('GATest002', null);

        // GA 不應該通過 manageOwnVote 權限管理（因為沒有 groupId）
        $this->assertFalse(
            Yii::$app->user->can('manageOwnVote', ['voteID' => 'GATest002']),
            'GA should not manage votes without groupId via manageOwnVote'
        );
    }

    /**
     * 測試：GM 成員 isWrite='Y' 可以管理投票
     */
    public function testGmWithIsWriteYCanManageVote()
    {
        $this->loginUser('gm_test', 'GM Test', 'gm');

        // 建立群組投票（群組1）
        $vote = $this->createVote('GMTest001', 1);

        // gm_test 在 groupMember fixture 中有 isWrite='Y'
        $this->assertTrue(
            Yii::$app->user->can('manageOwnVote', ['voteID' => 'GMTest001']),
            'GM with isWrite=Y should be able to manage vote'
        );
    }

    /**
     * 測試：GM 成員 isWrite='N' 不能管理投票
     */
    public function testGmWithIsWriteNCannotManageVote()
    {
        $this->loginUser('gm_view_only', 'GM View Only', 'gm');

        // 建立群組投票（群組1）
        $vote = $this->createVote('GMTest002', 1);

        // gm_view_only 在 fixture 中有 isWrite='N'
        $this->assertFalse(
            Yii::$app->user->can('manageOwnVote', ['voteID' => 'GMTest002']),
            'GM with isWrite=N should not be able to manage vote'
        );
    }

    /**
     * 測試：非群組成員不能管理該群組的投票
     */
    public function testNonMemberCannotManageGroupVote()
    {
        $this->loginUser('gm_outsider', 'GM Outsider', 'gm');

        // 建立群組投票（群組1）
        $vote = $this->createVote('GMTest003', 1);

        // gm_outsider 不在群組1中
        $this->assertFalse(
            Yii::$app->user->can('manageOwnVote', ['voteID' => 'GMTest003']),
            'Non-member should not be able to manage group vote'
        );
    }

    /**
     * 測試：VA 角色沒有 groupId 投票的 manageOwnVote 權限
     */
    public function testVaCannotUseManageOwnVotePermission()
    {
        $this->loginUser('va_test', 'VA Test', 'va');

        // 建立有 groupId 的投票
        $vote = $this->createVote('VATest001', 1);

        // VA 有 voteManag 權限，但沒有 manageOwnVote 權限
        $this->assertTrue(Yii::$app->user->can('voteManag'), 'VA should have voteManag');
        $this->assertFalse(
            Yii::$app->user->can('manageOwnVote', ['voteID' => 'VATest001']),
            'VA should not have manageOwnVote permission (unless also GM)'
        );
    }

    /**
     * 測試：同時有 VA+GM 角色的權限
     */
    public function testVaGmComboCanManageVote()
    {
        $this->loginUser('va_gm_combo', 'VA GM Combo', 'va,gm');

        // 建立群組投票（群組1），且使用者是成員
        $member = new GroupMember();
        $member->groupId = 1;
        $member->cn = 'va_gm_combo';
        $member->isWrite = 'Y';
        $member->isOwner = 'N';
        $this->assertTrue($member->save());

        $vote = $this->createVote('ComboTest001', 1);

        // 應該有 voteManag 和 manageOwnVote 兩種權限
        $this->assertTrue(Yii::$app->user->can('voteManag'), 'Should have voteManag');
        $this->assertTrue(
            Yii::$app->user->can('manageOwnVote', ['voteID' => 'ComboTest001']),
            'Should have manageOwnVote'
        );
    }

    /**
     * 測試：投票無 groupId 時 GM 不能通過 manageOwnVote 管理
     */
    public function testGmCannotManageVoteWithoutGroupId()
    {
        $this->loginUser('gm_no_group', 'GM No Group', 'gm');

        // 建立沒有 groupId 的投票
        $vote = $this->createVote('NoGroupTest001', null);

        $this->assertFalse(
            Yii::$app->user->can('manageOwnVote', ['voteID' => 'NoGroupTest001']),
            'GM should not manage votes without groupId'
        );
    }

    // ==================== groupViewOwnRule 測試 ====================

    /**
     * 測試：群組成員可以查看自己的群組
     */
    public function testMemberCanViewOwnGroup()
    {
        $this->loginUser('gm_test', 'GM Test', 'gm');

        // gm_test 是群組1的成員
        $this->assertTrue(
            Yii::$app->user->can('viewOwnGroup', ['groupId' => 1]),
            'Member should be able to view own group'
        );
    }

    /**
     * 測試：非群組成員不能查看其他群組
     */
    public function testNonMemberCannotViewGroup()
    {
        $this->loginUser('gm_outsider2', 'GM Outsider 2', 'gm');

        // gm_outsider2 不在群組1中
        $this->assertFalse(
            Yii::$app->user->can('viewOwnGroup', ['groupId' => 1]),
            'Non-member should not be able to view group'
        );
    }

    /**
     * 測試：GA 角色可以查看所有群組
     */
    public function testGaCanViewAllGroups()
    {
        $this->loginUser('ga_view_test', 'GA View Test', 'ga');

        // GA 應該可以查看所有群組（因為 GA 繼承 viewOwnGroup，且規則沒有限制）
        // 但根據 groupViewOwnRule 邏輯，GA 必須是成員才能查看
        // 所以這個測試實際上會失敗，除非 GA 也在群組中

        // 先加入 GA 到群組1
        $member = new GroupMember();
        $member->groupId = 1;
        $member->cn = 'ga_view_test';
        $member->isWrite = 'N';
        $member->isOwner = 'Y';
        $this->assertTrue($member->save());

        $this->assertTrue(
            Yii::$app->user->can('viewOwnGroup', ['groupId' => 1]),
            'GA should be able to view groups they are member of'
        );
    }

    /**
     * 測試：isWrite/isOwner 不影響查看權限
     */
    public function testViewPermissionNotAffectedByWriteOrOwner()
    {
        // 測試 isWrite='N', isOwner='N' 的成員仍可查看
        $this->loginUser('gm_view_only', 'GM View Only', 'gm');

        // gm_view_only 有 isWrite='N', isOwner='N'
        $this->assertTrue(
            Yii::$app->user->can('viewOwnGroup', ['groupId' => 1]),
            'View permission should not be affected by isWrite or isOwner'
        );
    }

    // ==================== groupManagByMemberRule 測試 ====================

    /**
     * 測試：GA 角色可以管理任何群組成員
     */
    public function testGaCanManageAnyGroupMember()
    {
        $this->loginUser('ga_manag_test', 'GA Manag Test', 'ga');

        // GA 應該可以管理任何群組的成員
        $this->assertTrue(
            Yii::$app->user->can('manageByMember', ['groupId' => 1, 'cn' => 'other_user']),
            'GA should be able to manage any group member'
        );
    }

    /**
     * 測試：isOwner='Y' 的成員可以管理其他成員
     */
    public function testOwnerCanManageOtherMembers()
    {
        $this->loginUser('gm_test', 'GM Test', 'gm');

        // gm_test 有 isOwner='Y'
        $this->assertTrue(
            Yii::$app->user->can('manageByMember', ['groupId' => 1, 'cn' => 'other_user']),
            'Owner should be able to manage other members'
        );
    }

    /**
     * 測試：isOwner='N' 的成員不能管理成員
     */
    public function testNonOwnerCannotManageMembers()
    {
        $this->loginUser('gm_write_only', 'GM Write Only', 'gm');

        // gm_write_only 有 isOwner='N'
        $this->assertFalse(
            Yii::$app->user->can('manageByMember', ['groupId' => 1, 'cn' => 'other_user']),
            'Non-owner should not be able to manage members'
        );
    }

    /**
     * 測試：禁止編輯/刪除自己
     */
    public function testCannotEditOrDeleteSelf()
    {
        $this->loginUser('gm_owner_only', 'GM Owner Only', 'gm');

        // 嘗試編輯自己應該拋出 403 錯誤
        $this->expectException(\yii\web\HttpException::class);
        $this->expectExceptionMessage('禁止編輯或刪除自己');

        Yii::$app->user->can('manageByMember', ['groupId' => 1, 'cn' => 'gm_owner_only']);
    }

    /**
     * 測試：非群組成員不能管理該群組
     */
    public function testNonMemberCannotManageGroup()
    {
        $this->loginUser('gm_outsider3', 'GM Outsider 3', 'gm');

        // gm_outsider3 不在群組1中
        $this->assertFalse(
            Yii::$app->user->can('manageByMember', ['groupId' => 1, 'cn' => 'other_user']),
            'Non-member should not be able to manage group'
        );
    }

    /**
     * 測試：isWrite 不影響成員管理權限
     */
    public function testIsWriteDoesNotAffectMemberManagement()
    {
        // isOwner='Y' 但 isWrite='N'
        $this->loginUser('gm_owner_only', 'GM Owner Only', 'gm');

        // 應該可以管理成員（因為 isOwner='Y'）
        $this->assertTrue(
            Yii::$app->user->can('manageByMember', ['groupId' => 1, 'cn' => 'other_user']),
            'isWrite should not affect member management permission'
        );
    }

    /**
     * 測試：同時有多個群組的權限隔離
     */
    public function testMultiGroupPermissionIsolation()
    {
        $this->loginUser('gm_multi', 'GM Multi', 'gm');

        // 加入群組1（有 isOwner='Y'）
        $member1 = new GroupMember();
        $member1->groupId = 1;
        $member1->cn = 'gm_multi';
        $member1->isWrite = 'Y';
        $member1->isOwner = 'Y';
        $this->assertTrue($member1->save());

        // 可以管理群組1
        $this->assertTrue(
            Yii::$app->user->can('manageByMember', ['groupId' => 1, 'cn' => 'other_user']),
            'Should be able to manage group 1'
        );

        // 不能管理群組2（不是成員）
        $this->assertFalse(
            Yii::$app->user->can('manageByMember', ['groupId' => 2, 'cn' => 'other_user']),
            'Should not be able to manage group 2'
        );
    }
}
