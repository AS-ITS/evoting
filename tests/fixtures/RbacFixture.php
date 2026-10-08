<?php

namespace app\tests\fixtures;

use Yii;
use yii\test\Fixture;

/**
 * RBAC Fixture
 *
 * 初始化測試環境的 RBAC 角色和權限
 */
class RbacFixture extends Fixture
{
    /**
     * 載入 RBAC 資料
     */
    public function load()
    {
        $this->resetRbacState();

        $auth = Yii::$app->authManager;

        // ========================================
        // 創建 Rules
        // ========================================
        $voteManagRule = new \app\rules\voteManagRule();
        $auth->add($voteManagRule);

        $ballotWorkRule = new \app\rules\ballotWorkRule();
        $auth->add($ballotWorkRule);

        $manageOwnVoteRule = new \app\rules\groupManagOwnVoteRule();
        $auth->add($manageOwnVoteRule);

        $viewOwnGroupRule = new \app\rules\groupViewOwnRule();
        $auth->add($viewOwnGroupRule);

        $manageByMemberRule = new \app\rules\groupManagByMemberRule();
        $auth->add($manageByMemberRule);

        // ========================================
        // 創建四個角色：sa, va, ga, gm
        // ========================================
        $sa = $auth->createRole('sa');
        $sa->description = '系統管理員';
        $auth->add($sa);

        $va = $auth->createRole('va');
        $va->description = '投票活動管理者';
        $auth->add($va);

        $ga = $auth->createRole('ga');
        $ga->description = '群組管理者';
        $auth->add($ga);

        $gm = $auth->createRole('gm');
        $gm->description = '群組成員';
        $auth->add($gm);

        // ========================================
        // 創建開票作業權限
        // ========================================
        $ballotWorkIndex = $auth->createPermission('ballotWorkIndex');
        $ballotWorkIndex->description = '開票首頁';
        $auth->add($ballotWorkIndex);

        $ballotWorkStatus = $auth->createPermission('ballotWorkStatus');
        $ballotWorkStatus->description = '投票狀態';
        $ballotWorkStatus->ruleName = $ballotWorkRule->name;
        $auth->add($ballotWorkStatus);

        $ballotWorkSetting = $auth->createPermission('ballotWorkSetting');
        $ballotWorkSetting->description = '開票設定';
        $ballotWorkSetting->ruleName = $ballotWorkRule->name;
        $auth->add($ballotWorkSetting);

        $ballotWorkCount = $auth->createPermission('ballotWorkCount');
        $ballotWorkCount->description = '計票';
        $ballotWorkCount->ruleName = $ballotWorkRule->name;
        $auth->add($ballotWorkCount);

        $ballotWorkResult = $auth->createPermission('ballotWorkResult');
        $ballotWorkResult->description = '開票結果';
        $ballotWorkResult->ruleName = $ballotWorkRule->name;
        $auth->add($ballotWorkResult);

        // ========================================
        // 創建投票管理權限
        // ========================================
        $voteCreate = $auth->createPermission('voteCreate');
        $voteCreate->description = '建立投票';
        $auth->add($voteCreate);

        $voteManag = $auth->createPermission('voteManag');
        $voteManag->description = '投票管理頁面';
        $auth->add($voteManag);

        // 帶 voteManagRule 的權限 - sa 角色可以直接通過
        $voteInfo = $auth->createPermission('voteInfo');
        $voteInfo->description = '投票基本設定編輯';
        $voteInfo->ruleName = $voteManagRule->name;
        $auth->add($voteInfo);

        $voteQuestion = $auth->createPermission('voteQuestion');
        $voteQuestion->description = '投票問題全功能';
        $voteQuestion->ruleName = $voteManagRule->name;
        $auth->add($voteQuestion);

        $voteCandi = $auth->createPermission('voteCandi');
        $voteCandi->description = '投票候選人管理全功能';
        $voteCandi->ruleName = $voteManagRule->name;
        $auth->add($voteCandi);

        $votePasswd = $auth->createPermission('votePasswd');
        $votePasswd->description = '投票密碼全功能';
        $votePasswd->ruleName = $voteManagRule->name;
        $auth->add($votePasswd);

        $voteBallot = $auth->createPermission('voteBallot');
        $voteBallot->description = '投票選票全功能';
        $voteBallot->ruleName = $voteManagRule->name;
        $auth->add($voteBallot);

        $voteCount = $auth->createPermission('voteCount');
        $voteCount->description = '投票計票單、開票全功能';
        $voteCount->ruleName = $voteManagRule->name;
        $auth->add($voteCount);

        $voteResult = $auth->createPermission('voteResult');
        $voteResult->description = '投票結果全功能';
        $voteResult->ruleName = $voteManagRule->name;
        $auth->add($voteResult);

        $voteReset = $auth->createPermission('voteReset');
        $voteReset->description = '投票重啟全功能';
        $voteReset->ruleName = $voteManagRule->name;
        $auth->add($voteReset);

        $voteGenManag = $auth->createPermission('voteGenManag');
        $voteGenManag->description = '投票總管理';
        $auth->add($voteGenManag);

        // 其他權限
        $electManag = $auth->createPermission('electManag');
        $electManag->description = '管理選舉';
        $auth->add($electManag);

        // ========================================
        // 創建群組相關權限
        // ========================================
        // 基本群組權限（與 SiteController::actionInitRbac() 一致）
        $groupManag = $auth->createPermission('groupManag');
        $groupManag->description = '群組管理';
        $auth->add($groupManag);

        $groupView = $auth->createPermission('groupView');
        $groupView->description = '檢視群組';
        $auth->add($groupView);

        $groupViewOwn = $auth->createPermission('groupViewOwn');
        $groupViewOwn->description = '僅能檢視自己的群組';
        $groupViewOwn->ruleName = $viewOwnGroupRule->name;
        $auth->add($groupViewOwn);

        $groupCreate = $auth->createPermission('groupCreate');
        $groupCreate->description = '建立群組';
        $auth->add($groupCreate);

        $groupViewBase = $auth->createPermission('groupViewBase');
        $groupViewBase->description = '檢視群組基本資料';
        $auth->add($groupViewBase);

        $groupEditBase = $auth->createPermission('groupEditBase');
        $groupEditBase->description = '編輯群組基本資料';
        $auth->add($groupEditBase);

        $groupViewMember = $auth->createPermission('groupViewMember');
        $groupViewMember->description = '檢視群組成員資料';
        $auth->add($groupViewMember);

        $groupCreateMember = $auth->createPermission('groupCreateMember');
        $groupCreateMember->description = '新增群組成員';
        $auth->add($groupCreateMember);

        $groupEditMember = $auth->createPermission('groupEditMember');
        $groupEditMember->description = '編輯群組成員';
        $auth->add($groupEditMember);

        $groupDeleteMember = $auth->createPermission('groupDeleteMember');
        $groupDeleteMember->description = '刪除群組成員';
        $auth->add($groupDeleteMember);

        $groupManagOwnVote = $auth->createPermission('groupManagOwnVote');
        $groupManagOwnVote->description = '群組成員管理自己群組的投票';
        $groupManagOwnVote->ruleName = $manageOwnVoteRule->name;
        $auth->add($groupManagOwnVote);

        $groupManageByMember = $auth->createPermission('groupManageByMember');
        $groupManageByMember->description = '群組成員管理權限';
        $groupManageByMember->ruleName = $manageByMemberRule->name;
        $auth->add($groupManageByMember);

        // 舊的權限名稱（保持向後相容）
        $manageOwnVote = $auth->createPermission('manageOwnVote');
        $manageOwnVote->description = '管理自己群組的投票';
        $manageOwnVote->ruleName = 'manageOwnVote';
        $auth->add($manageOwnVote);

        $viewOwnGroup = $auth->createPermission('viewOwnGroup');
        $viewOwnGroup->description = '查看自己的群組';
        $viewOwnGroup->ruleName = 'viewOwnGroup';
        $auth->add($viewOwnGroup);

        $manageByMember = $auth->createPermission('manageByMember');
        $manageByMember->description = '群組成員管理權限';
        $manageByMember->ruleName = 'manageByMember';
        $auth->add($manageByMember);

        // ========================================
        // 設定 SA 角色權限（擁有所有權限）
        // ========================================
        $auth->addChild($sa, $ballotWorkIndex);
        $auth->addChild($sa, $ballotWorkStatus);
        $auth->addChild($sa, $ballotWorkSetting);
        $auth->addChild($sa, $ballotWorkCount);
        $auth->addChild($sa, $ballotWorkResult);
        $auth->addChild($sa, $voteCreate);
        $auth->addChild($sa, $voteManag);
        $auth->addChild($sa, $voteInfo);
        $auth->addChild($sa, $voteQuestion);
        $auth->addChild($sa, $voteCandi);
        $auth->addChild($sa, $votePasswd);
        $auth->addChild($sa, $voteBallot);
        $auth->addChild($sa, $voteCount);
        $auth->addChild($sa, $voteResult);
        $auth->addChild($sa, $voteReset);
        $auth->addChild($sa, $voteGenManag);
        $auth->addChild($sa, $electManag);
        $auth->addChild($sa, $manageOwnVote);
        $auth->addChild($sa, $viewOwnGroup);
        $auth->addChild($sa, $manageByMember);
        // SA 繼承其他角色
        $auth->addChild($sa, $va);
        $auth->addChild($sa, $ga);
        $auth->addChild($sa, $gm);
        // SA 群組權限（與 SiteController::actionInitRbac() 一致）
        $auth->addChild($sa, $groupManag);
        $auth->addChild($sa, $groupView);
        $auth->addChild($sa, $groupViewOwn);
        $auth->addChild($sa, $groupCreate);
        $auth->addChild($sa, $groupViewBase);
        $auth->addChild($sa, $groupEditBase);
        $auth->addChild($sa, $groupViewMember);
        $auth->addChild($sa, $groupCreateMember);
        $auth->addChild($sa, $groupEditMember);
        $auth->addChild($sa, $groupDeleteMember);
        $auth->addChild($sa, $groupManagOwnVote);
        $auth->addChild($sa, $groupManageByMember);

        // ========================================
        // 設定 VA 角色權限
        // ========================================
        $auth->addChild($va, $voteCreate);
        $auth->addChild($va, $voteManag);
        $auth->addChild($va, $voteInfo);
        $auth->addChild($va, $voteQuestion);
        $auth->addChild($va, $voteCandi);
        $auth->addChild($va, $votePasswd);
        $auth->addChild($va, $voteBallot);
        $auth->addChild($va, $voteCount);
        $auth->addChild($va, $voteResult);
        $auth->addChild($va, $voteReset);
        $auth->addChild($va, $ballotWorkIndex);
        $auth->addChild($va, $ballotWorkStatus);
        $auth->addChild($va, $ballotWorkSetting);
        $auth->addChild($va, $ballotWorkCount);
        $auth->addChild($va, $ballotWorkResult);
        // VA 群組權限
        $auth->addChild($va, $groupView);
        $auth->addChild($va, $groupCreate);
        $auth->addChild($va, $groupViewOwn);
        $auth->addChild($va, $groupViewMember);
        $auth->addChild($va, $groupManagOwnVote);
        $auth->addChild($va, $groupManageByMember);
        // VA 繼承 GM 角色
        $auth->addChild($va, $gm);

        // ========================================
        // 設定 GA 角色權限
        // ========================================
        $auth->addChild($ga, $manageByMember);
        $auth->addChild($ga, $viewOwnGroup);
        $auth->addChild($ga, $groupManag);
        $auth->addChild($ga, $groupView);
        $auth->addChild($ga, $groupCreate);
        $auth->addChild($ga, $groupViewBase);
        $auth->addChild($ga, $groupEditBase);
        $auth->addChild($ga, $groupViewMember);
        $auth->addChild($ga, $groupCreateMember);
        $auth->addChild($ga, $groupEditMember);
        $auth->addChild($ga, $groupDeleteMember);
        $auth->addChild($ga, $groupManagOwnVote);
        $auth->addChild($ga, $ballotWorkIndex);
        $auth->addChild($ga, $ballotWorkStatus);
        $auth->addChild($ga, $ballotWorkSetting);
        $auth->addChild($ga, $ballotWorkCount);
        $auth->addChild($ga, $ballotWorkResult);
        $auth->addChild($ga, $gm);

        // ========================================
        // 設定 GM 角色權限
        // ========================================
        $auth->addChild($gm, $manageOwnVote);
        $auth->addChild($gm, $viewOwnGroup);
        $auth->addChild($gm, $manageByMember);
        $auth->addChild($gm, $groupView);
        $auth->addChild($gm, $groupViewOwn);
        $auth->addChild($gm, $ballotWorkIndex);
        $auth->addChild($gm, $ballotWorkStatus);
        $auth->addChild($gm, $ballotWorkSetting);
        $auth->addChild($gm, $ballotWorkCount);
        $auth->addChild($gm, $ballotWorkResult);
    }

    /**
     * 卸載 RBAC 資料
     */
    public function unload()
    {
        $this->resetRbacState();
    }

    /**
     * 清除 RBAC 快取並重置 auth 表（避免殘留 rule 導致 getRule 異常）
     */
    private function resetRbacState(): void
    {
        $auth = Yii::$app->authManager;

        if (Yii::$app->has('cache')) {
            Yii::$app->cache->flush();
        }
        if ($auth instanceof \yii\rbac\DbManager) {
            $auth->invalidateCache();
        }

        // BaseManager 在 getRule 失敗時會 createObject(ruleName)，預先註冊別名
        Yii::$container->set('ballotWorkManage', \app\rules\ballotWorkRule::class);
        Yii::$container->set('voteManag', \app\rules\voteManagRule::class);
        Yii::$container->set('manageOwnVote', \app\rules\groupManagOwnVoteRule::class);
        Yii::$container->set('viewOwnGroup', \app\rules\groupViewOwnRule::class);
        Yii::$container->set('manageByMember', \app\rules\groupManagByMemberRule::class);

        $auth->removeAll();
    }
}
