<?php
namespace app\controllers;

use Yii;
use app\models\Logs;
use app\models\Users;
use yii\helpers\Json;
use app\components\helper\ArrayHelper;
use app\models\Votes;

/**
 * 實現一些共用方法
 * 
 * 例如：登入登出、角色切換、語言切換...等
 */
class SiteController extends \app\components\Controller
{
    /**
     * 行為
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'actions' => [
                            'index', 'login', 'logout', 'password', 'vote-status',
                            'error', 'change-language', 'set-page-size', 'csrf-token'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['data-table-modifier', 'yii-log-view', 'sync-role-per'],
                        'allow' => Yii::$app->user->can('sa'),
                    ],
                    [
                        'allow' => !Yii::$app->user->isGuest,
                    ],
                ],
                'denyCallback' => self::denyCallback()
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::class,
                'actions' => [
                    'https' => ['post','get'],
                    'logout' => ['post'],
                    'csrf-token' => ['get'],
                ],
            ],
        ];
    }

    /**
     * 共用 action
     */
    public function actions()
    {
        return [
            'error' => [ // 統一使用Yii2錯誤方法
                'class' => \yii\web\ErrorAction::class,
            ],
            'change-language' => [ // 切換語言
                'class' => \app\actions\SwitchLanguageAction::class,
                'handleEventClass' => \app\models\HandleSwitch::class,
            ],
            'switch-role' => [ // 切換角色
                'class' => \app\actions\SwitchRoleAction::class,
                'handleEventClass' => \app\models\HandleSwitch::class,
            ],
            'set-page-size' => [ // 切換頁數
                'class' => \app\actions\SwitchPagesAction::class,
                'handleEventClass' => \app\models\HandleSwitch::class,
            ],
            'data-table-modifier' => [ // 資料表編輯器
                'class' => \app\actions\DataTableModifierAction::class,
            ],
            'yii-log-view' => [ // Yii紀錄檢視
                'class' => \app\actions\YiiLogViewAction::class,
            ],
        ];
    }

    /**
     * 預設主頁
     */
    public function actionIndex()
    {
        return $this->goHome();
        // return $this->render('index');
    }

    /**
     * 匿名登入
     */
    public function actionPassword($voteID=null)
    {
        if (!Yii::$app->anon->isGuest) {
            return $this->redirect(['vote/vote-detail', 'voteID' => $voteID]);
        }

        $request = Yii::$app->request;
        $model = new \app\models\FormAnon;
        $config = \app\models\Config::find()->where(['id' => Yii::$app->id])->asArray()->one();
        $isLock = false;// 狀態鎖定
        $waitTime = $config['anonLoginWaiting'];// 等待秒數

        if($request->isPost)
        {
            if(!is_null(Logs::getPasswordFailWait($voteID, $config['anonLoginLockPeriod'], $config['anonPasswordErrorTimes'])))
            {
                // 密碼防護
                throw new \yii\web\HttpException(429, Yii::t('app','由於您密碼錯誤次數過多，請稍後再試！'));
            }
            if ($model->load($request->post()) && $model->login())
            {
                $authData = Yii::$app->anon->identity->getAuthData();
                Logs::add(
                    Logs::LOGIN_PASSWORD,
                    Json::encode($model->buildLoginLogContext($authData)),
                    ['voteID' => $model->voteID]
                );
                Yii::$app->session->set('System.config', ArrayHelper::forget($config, ['id', 'updated_at']));
                // login() 會換發 CSRF，通知其他分頁更新 meta
                Yii::$app->session->setFlash('csrfBroadcast', '1');
                return $this->redirect(['vote/vote-detail', 'voteID' => $model->voteID]);
            }
            else if(count($model->errors) > 0)
            {
                Logs::add(
                    Logs::LOGIN_PASSWORD_FAIL,
                    Json::encode($model->buildLoginLogContext()),
                    ['voteID' => $model->voteID]
                );
                if(isset($model->errors['password']))
                {
                    $model->password = '';
                }
            }
        }

        if(Yii::$app->userAgent->recordToReturnUrlBeforeLogin('anon', false)['useMethod'] == 'pathInfo')
        {
            $returnUrl = Yii::$app->userAgent->recordToReturnUrlBeforeLogin('anon')['returnUrl'];
            return $this->redirect(array_merge(
                Yii::$app->anon->loginUrl, ['voteID' => $returnUrl['voteID']]
            ));
        }

        
        if(is_null($voteID))
        {
            parse_str(parse_url(Yii::$app->anon->returnUrl, PHP_URL_QUERY), $query);

            if(\app\components\helper\ArrayHelper::keyExists('voteID', $query) === false) // 不存在的投票
            {
                throw new \yii\web\HttpException(403, 'Vote ID 無效或不存在！');
            }
            $voteID = $query['voteID'];
        }
        
        $voteInfo = (new \app\models\FormVotes)->getVoteInfo($voteID);
        if(is_null($voteInfo)) {
            throw new \yii\web\HttpException(404, '投票項目不存在！');
        }
        // 檢查投票是否開放
        $status = $this->getVoteStatus($voteInfo);
        $canVote = $status['canVote'];
        $hint = $status['hint'];

        $model->voteID = $voteID;
        // 密碼防護
        $wait = Logs::getPasswordFailWait($model->voteID, $config['anonLoginLockPeriod'], $config['anonPasswordErrorTimes']);
        if(!is_null($wait))
        {
            $isLock = true;
            $waitTime = $wait;
        }
        $this->layout = (empty($voteInfo->loginLayout) || $voteInfo->loginLayout == 'main') ? 'main' : 'login/'.$voteInfo->loginLayout;

        return $this->render('password', [
            'model' => $model,
            'voteInfo' => $voteInfo,
            'isLock' => $isLock,
            'waitTime' => $waitTime,
            'hint' => $hint,
            'canVote' => $canVote,
        ]);
    }
    
    /**
     * ajax取得投票狀態
     *
     * @param  string $voteID
     * @return string
     */
    public function actionVoteStatus($voteID)
    {
        $request = $this->request;

        if ($request->isAjax) {
            $voteInfo = (new \app\models\FormVotes)->getVoteInfo($voteID);
            if(is_null($voteInfo)) {
                throw new \yii\web\HttpException(404, '投票項目不存在！');
            }
            $voteTitle = $voteInfo->getVoteName();
            // 檢查投票是否開放
            $status = $this->getVoteStatus($voteInfo);
            $canVote = $status['canVote'];
            $hint = $status['hint'];

            echo Json::encode(compact('canVote', 'hint', 'voteTitle'));
        }
    }

    /**
     * 回傳目前 CSRF param / masked token（供登出前同步 meta，保留 CSRF 驗證）
     *
     * @return \yii\web\Response
     */
    public function actionCsrfToken()
    {
        $request = Yii::$app->request;
        return $this->asJson([
            'param' => $request->csrfParam,
            'token' => $request->getCsrfToken(),
        ]);
    }

    /**
     * 登出
     * 
     * @param string $type 配置登入的 User flag
     * 
     * @return \yii\web\Response
     */
    public function actionLogout($type = 'user')
    {
        if (!in_array($type, ['user', 'anon'], true)) {
            throw new \yii\web\BadRequestHttpException('Invalid logout type');
        }

        // user / anon 共用同一 PHP session，只清對應身分，互不影響
        $component = Yii::$app->$type;
        if (!$component->isGuest && $component->identity) {
            // AdminIdentity::getFlag() 為 Admin，需明確指定 user component
            $component->identity->logout(false, $type);
        } else {
            $component->logout(false);
        }

        return $this->goHome();
    }

    /**
     * 資料轉換
     */
    public function actionSyncRolePer()
    {
        $adminUser = Users::findOne(Yii::$app->user->id);

        if (!Yii::$app->request->isPost) {
            return $this->render('sync-role-per', [
                'reauthRequired' => \app\components\SensitiveReauth::isRequired(),
                'useTotp' => $adminUser ? \app\components\TotpService::isEnabledForUser($adminUser) : false,
            ]);
        }

        if (!\app\components\SensitiveReauth::verify(
            Yii::$app->request->post('adminPassword'),
            Yii::$app->request->post('totpCode')
        )) {
            Yii::$app->session->setFlash(
                'error',
                $adminUser
                    ? \app\components\SensitiveReauth::failureMessage($adminUser)
                    : Yii::t('app', '二次驗證失敗。')
            );
            return $this->render('sync-role-per', [
                'reauthRequired' => true,
                'useTotp' => $adminUser ? \app\components\TotpService::isEnabledForUser($adminUser) : false,
            ]);
        }

        Logs::add(Logs::SYSTEM_RBAC_SYNC, Json::encode(['user' => Yii::$app->user->id], 336));

        $auth = Yii::$app->authManager;
        $auth->removeAll();

        // Rule
        $voteManagRule = new \app\rules\voteManagRule; // new rule
        $auth->add($voteManagRule);

        // 只能對自己的群組做投票管理動作的規則
        $groupManagOwnVoteRule = new \app\rules\groupManagOwnVoteRule; 
        $auth->add($groupManagOwnVoteRule);

        // 只能對自己的群組做檢視動作的規則
        $groupViewOwnRule = new \app\rules\groupViewOwnRule; 
        $auth->add($groupViewOwnRule);

        // 是否可以對群組進行修改基本資料、成員新增刪除修改等動作的規則
        $groupManagByMemberRule = new \app\rules\groupManagByMemberRule; 
        $auth->add($groupManagByMemberRule);

        // 開票作業的規則
        $ballotWorkRule = new \app\rules\ballotWorkRule; 
        $auth->add($ballotWorkRule);

        // Permission

        /**
         * 開票作業
         */
        $ballotWorkIndex = $auth->createPermission('ballotWorkIndex');// 開票作業首頁
        $ballotWorkIndex->description = '開票作業首頁';
        $auth->add($ballotWorkIndex);

        $ballotWorkStatus = $auth->createPermission('ballotWorkStatus');// 投票情形
        $ballotWorkStatus->description = '投票情形';
        $ballotWorkStatus->ruleName = $ballotWorkRule->name;// add rule
        $auth->add($ballotWorkStatus);

        $ballotWorkSetting = $auth->createPermission('ballotWorkSetting');// 開票設定
        $ballotWorkSetting->description = '開票設定';
        $ballotWorkSetting->ruleName = $ballotWorkRule->name;// add rule
        $auth->add($ballotWorkSetting);

        $ballotWorkCount = $auth->createPermission('ballotWorkCount');// 計票顯示
        $ballotWorkCount->description = '計票顯示';
        $ballotWorkCount->ruleName = $ballotWorkRule->name;// add rule
        $auth->add($ballotWorkCount);

        $ballotWorkResult = $auth->createPermission('ballotWorkResult');// 投票結果
        $ballotWorkResult->description = '投票結果';
        $ballotWorkResult->ruleName = $ballotWorkRule->name;// add rule
        $auth->add($ballotWorkResult);

        $ballotWork = $auth->createPermission('ballotWork');// 開票作業
        $ballotWork->description = '開票作業';
        $ballotWork->ruleName = $ballotWorkRule->name;// add rule
        $auth->add($ballotWork);

        /**
         * 投票類
         */
        $voteCreate = $auth->createPermission('voteCreate');// 建立投票
        $voteCreate->description = '建立投票';
        $auth->add($voteCreate);

        $voteManag = $auth->createPermission('voteManag');// 投票管理
        $voteManag->description = '投票管理頁面';
        $auth->add($voteManag);

        $voteInfo = $auth->createPermission('voteInfo');// 投票基本設定
        $voteInfo->description = '投票基本設定編輯';
        $voteInfo->ruleName = $voteManagRule->name;// add rule
        $auth->add($voteInfo);
        
        $voteQuestion = $auth->createPermission('voteQuestion');// 投票基本設定
        $voteQuestion->description = '投票問題全功能';
        $voteQuestion->ruleName = $voteManagRule->name;// add rule
        $auth->add($voteQuestion);

        $voteCandi = $auth->createPermission('voteCandi');// 投票候選人管理
        $voteCandi->description = '投票候選人管理全功能';
        $voteCandi->ruleName = $voteManagRule->name;// add rule
        $auth->add($voteCandi);

        $votePasswd = $auth->createPermission('votePasswd');// 投票密碼管理
        $votePasswd->description = '投票密碼管理全功能';
        $votePasswd->ruleName = $voteManagRule->name;// add rule
        $auth->add($votePasswd);

        $voteBallot = $auth->createPermission('voteBallot');// 投票選票管理
        $voteBallot->description = '投票選票管理全功能';
        $voteBallot->ruleName = $voteManagRule->name;// add rule
        $auth->add($voteBallot);

        $voteCount = $auth->createPermission('voteCount');// 投票計票單、開票
        $voteCount->description = '投票計票單、開票全功能';
        $voteCount->ruleName = $voteManagRule->name;// add rule
        $auth->add($voteCount);

        $voteResult = $auth->createPermission('voteResult');// 投票結果
        $voteResult->description = '投票結果全功能';
        $voteResult->ruleName = $voteManagRule->name;// add rule
        $auth->add($voteResult);

        $voteReset = $auth->createPermission('voteReset');// 投票重啟
        $voteReset->description = '投票重啟全功能';
        $voteReset->ruleName = $voteManagRule->name;// add rule
        $auth->add($voteReset);

        $voteGenManag = $auth->createPermission('voteGenManag');// 投票總管理
        $voteGenManag->description = '投票總管理';
        $auth->add($voteGenManag);

        /**
         * 群組類
         */
        $groupView = $auth->createPermission('groupView');// 檢視群組
        $groupView->description = '檢視群組';
        $auth->add($groupView);

        $groupViewOwn = $auth->createPermission('groupViewOwn');// 檢視自己的群組
        $groupViewOwn->description = '僅能檢視自己的群組';
        $groupViewOwn->ruleName = $groupViewOwnRule->name;// add rule
        $auth->add($groupViewOwn);

        $groupCreate = $auth->createPermission('groupCreate');// 建立群組
        $groupCreate->description = '建立新群組';
        $auth->add($groupCreate);

        $groupViewBase = $auth->createPermission('groupViewBase');// 群組基本資料
        $groupViewBase->description = '檢視群組基本資料';
        $auth->add($groupViewBase);

        $groupEditBase = $auth->createPermission('groupEditBase');// 編輯群組基本資料
        $groupEditBase->description = '編輯群組基本資料';
        $auth->add($groupEditBase);

        $groupViewMember = $auth->createPermission('groupViewMember');// 群組成員管理
        $groupViewMember->description = '檢視群組成員資料';
        $auth->add($groupViewMember);

        $groupCreateMember = $auth->createPermission('groupCreateMember');// 群組成員建立
        $groupCreateMember->description = '新增群組成員資料';
        $auth->add($groupCreateMember);

        $groupEditMember = $auth->createPermission('groupEditMember');// 群組成員編輯
        $groupEditMember->description = '編輯群組成員資料';
        $auth->add($groupEditMember);

        $groupDeleteMember = $auth->createPermission('groupDeleteMember');// 群組成員刪除
        $groupDeleteMember->description = '刪除群組成員資料';
        $auth->add($groupDeleteMember);

        $groupVoteManage = $auth->createPermission('groupVoteManage');// 群組投票管理
        $groupVoteManage->description = '管理屬於自己群組的投票';
        $auth->add($groupVoteManage);

        $groupManagOwnVote = $auth->createPermission('groupManagOwnVote');// 群組投票管理
        $groupManagOwnVote->ruleName = $groupManagOwnVoteRule->name;// add rule
        $groupManagOwnVote->description = '只能管理屬於自己群組的投票';
        $auth->add($groupManagOwnVote);

        $groupManageByMember = $auth->createPermission('groupManageByMember');// 允許群組成員管理
        $groupManageByMember->description = '允許群組成員管理';
        $groupManageByMember->ruleName = $groupManagByMemberRule->name;// add rule
        $auth->add($groupManageByMember);

        $groupManag = $auth->createPermission('groupManag');// 群組管理
        $groupManag->description = '管理群組';
        $auth->add($groupManag);

        // 功能都只能在自己的群組中操作，給gm(group member)的設定
        $auth->addChild($groupManag, $groupViewOwn);
        $auth->addChild($groupViewOwn, $groupView);
        $auth->addChild($groupViewOwn, $groupViewBase);
        $auth->addChild($groupViewOwn, $groupViewMember);

        $auth->addChild($groupManag, $groupManageByMember);
        $auth->addChild($groupManageByMember, $groupEditBase);
        $auth->addChild($groupManageByMember, $groupCreateMember);
        $auth->addChild($groupManageByMember, $groupEditMember);
        $auth->addChild($groupManageByMember, $groupDeleteMember);
        
        // $auth->addChild($groupManagOwnVote, $groupVoteManage);
        $auth->addChild($groupManagOwnVote, $voteInfo);
        $auth->addChild($groupManagOwnVote, $voteQuestion);
        $auth->addChild($groupManagOwnVote, $voteCandi);
        $auth->addChild($groupManagOwnVote, $votePasswd);
        $auth->addChild($groupManagOwnVote, $voteBallot);
        $auth->addChild($groupManagOwnVote, $voteCount);
        $auth->addChild($groupManagOwnVote, $voteResult);
        $auth->addChild($groupManagOwnVote, $voteReset);

        // Role
        $va = $auth->createRole('va');// 投票管理者
        $auth->add($va);
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
        
        $auth->addChild($va, $groupView);
        $auth->addChild($va, $groupCreate);
        $auth->addChild($va, $groupViewOwn);
        $auth->addChild($va, $groupManagOwnVote);
        $auth->addChild($va, $groupManageByMember);

        // $auth->addChild($va, $ballotWork);
        $auth->addChild($va, $ballotWorkIndex);
        $auth->addChild($va, $ballotWorkStatus);
        $auth->addChild($va, $ballotWorkSetting);
        $auth->addChild($va, $ballotWorkCount);
        $auth->addChild($va, $ballotWorkResult);

        // Group
        $ga = $auth->createRole('ga'); // 群組管理者
        $auth->add($ga);
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

        $gm = $auth->createRole('gm'); // 群組成員
        $auth->add($gm);
        $auth->addChild($gm, $groupView);
        $auth->addChild($gm, $groupViewOwn);
        $auth->addChild($gm, $groupManagOwnVote);
        $auth->addChild($gm, $groupManageByMember);

        $auth->addChild($gm, $ballotWorkIndex);
        $auth->addChild($gm, $ballotWorkStatus);
        $auth->addChild($gm, $ballotWorkSetting);
        $auth->addChild($gm, $ballotWorkCount);
        $auth->addChild($gm, $ballotWorkResult);

        // System
        $sa = $auth->createRole('sa');// 系統管理員
        $auth->add($sa);
        $auth->addChild($sa, $va); // 投票管理者
        $auth->addChild($sa, $ga); // 群組管理者
        $auth->addChild($sa, $gm); // 群組成員

        $auth->addChild($sa, $voteGenManag);

        // Super admin assignments
        // if (!Yii::$app->user->isGuest)
        // {
        //     $sysid = Yii::$app->user->identity->getId();
        //     $auth->assign($va, $sysid);
        // }

        return $this->goHome();
    }

    /**
     * 取得投票狀態
     *
     * @param  mixed $voteInfo
     * @return void
     */
    protected function getVoteStatus($voteInfo)
    {
        $canVote = true;
        $hint = '';

        if ($voteInfo->skipDetail || !$voteInfo->authBeforeDetail) {
            $toDate = strtotime(date('Y-m-d H:i:s'));
            if ($voteInfo->active == Votes::STATUS_READY) {
                $hint = Yii::t('app', '目前投票尚未開始');
                $canVote = false;
            }
            elseif ($voteInfo->active == Votes::STATUS_ACTIVE && $toDate < strtotime($voteInfo->openStart)) {
                $hint = Yii::t('app', '目前投票尚未開始');
                $canVote = false;
            }
            elseif ($voteInfo->active == Votes::STATUS_ACTIVE && $toDate >= strtotime($voteInfo->openStart) && $toDate <= strtotime($voteInfo->openEnd)) {
                $hint = Yii::t('app', '開始投票，請輸入場次碼及線上投票密碼');
                $canVote = true;
            }
            // 已結束可以查結果
            elseif ($voteInfo->active == Votes::STATUS_TERMINATE && $toDate > strtotime($voteInfo->verifyEnd) && $voteInfo->resultsConfig->isShow == '1') {
                $hint = '';
                $canVote = true;
            }
            elseif ($voteInfo->active == Votes::STATUS_TERMINATE || $toDate > strtotime($voteInfo->openEnd)) {
                $hint = Yii::t('app', '目前投票已結束');
                $canVote = false;
            }
            elseif ($voteInfo->active == Votes::STATUS_BACKFILL && $toDate >= strtotime($voteInfo->openStart) && $toDate <= strtotime($voteInfo->openEnd)) {
                $hint = Yii::t('app', '開始進行紙本通信計票作業，請輸入場次碼及線上投票密碼');
                $canVote = true;
            }
        }

        return compact('canVote', 'hint');
    }
}
