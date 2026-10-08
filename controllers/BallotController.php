<?php
namespace app\controllers;

use Yii;
use app\models\Logs;
use app\models\Votes;

use yii\helpers\Json;
use app\models\Ballots;
use app\models\FormVotes;
use app\models\Questions;
use app\models\FormBallots;
use app\components\helper\ArrayHelper;
use app\models\FormManageVote;
use app\models\FormCandiConfig;
use app\models\FormPasswords;
use app\components\SensitiveReauth;
use app\traits\PlaintextUnlockTrait;
use app\models\Users;

/**
 * 投票新修刪選票、選票相關設定
 */
class BallotController extends \app\components\Controller
{
    use PlaintextUnlockTrait;
    /**
     * 行為
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => false,
                        'matchCallback' => function ($rule, $action) use (&$candiConfig) 
                        {
                            $voteID = Yii::$app->getRequest()->get('voteID');
                            $voteInfo = Votes::findOne($voteID);
                            $FormCandiConfig = new FormCandiConfig;
                            // 依問題配置
                            if ($voteInfo->candiConfig == FormVotes::CANDI_CONFIG_BY_Q) {
                                $questions = $voteInfo->getVoteQuestion($voteID, $voteInfo->round);
                                foreach ($questions as $question => $name) {
                                    $candiConfig = $FormCandiConfig->getConfigWithVoteID($voteID, $question);
                                    if(is_null($candiConfig)) {
                                        return true;
                                    }
                                }
                                return false;
                            }
                            // 統一配置
                            else {
                                $candiConfig  = $FormCandiConfig->getConfigWithVoteID($voteID);
                                if(is_null($candiConfig)) {
                                    return true;
                                }
                                return false;
                            }
                        },
                        'denyCallback' => function ($rule, $action)
                        {
                            if (!Yii::$app->user->isGuest)
                            {
                                $voteID = Yii::$app->getRequest()->get('voteID');
                                Yii::$app->session->setFlash('warning', '請先建立候選名單配置！');
                                return $this->redirect(['candi/config','voteID'=>$voteID]);
                            }
                            $action->controller->NotAllowedAccess();
                        }
                    ],
                    [
                        'actions' => ['export'],
                        'allow' => true,
                        'matchCallback' => function () {
                            $voteID = Yii::$app->request->get('voteID');
                            $params = ['voteID' => $voteID];

                            return Yii::$app->user->can('voteBallot', $params)
                                || Yii::$app->user->can('ballotWorkStatus', $params);
                        },
                    ],
                    [
                        // 不設定actions表示全部
                        'allow' => true,
                        'permissions' => ['voteBallot'],
                        'roleParams' => ['voteID' => Yii::$app->request->get('voteID')],
                    ],
                ],
                'denyCallback' => self::denyCallback()
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'export' => ['post'],
                    'https' => ['post','get'],
                ],
            ],
        ];
    }

    /**
     * 選票列表
     */
    public function actionIndex($voteID)
    {
        $formManageCount = new \app\models\FormManageCount($voteID);
        // 基本資料
        $voteInfo = $formManageCount->getVoteInfo();
        $parties = $formManageCount->getPartyAry();

        // 選票設定
        $FormBallots = new FormBallots;
        $FormBallots->setScenario('search');
        $query = $FormBallots->getBallotList($voteID, $voteInfo->round, Yii::$app->request->queryParams);
        
        // 取得選票資訊
        $FormManageVote = new FormManageVote($voteID);

        // 問題
        $voteQuestions = $formManageCount->getVoteQuestions();

        if(trim($FormBallots->selectNum) == '') {
            $ballotCountAry = $FormManageVote->getBallotCount();
        }
        else {
            $ballotCountAry = $FormManageVote->getBallotCount($FormBallots->selectNum == '1' ? true : false);
        }
        // 取得投票者、修改者
        $plaintextUnlocked = SensitiveReauth::isPlaintextUnlocked();
        $sysidList = $FormBallots->getSysidByBallots($query, $voteInfo->type, $plaintextUnlocked);
        
        return $this->render('index', [
            'model' => $FormBallots,
            'dataProvider' => (new \app\models\DataProvider)->getBasicDataProvider($query),
            'ballotCountAry' => $ballotCountAry,
            'voteInfo' => $voteInfo,
            'sysidList' => $sysidList,
            'parties' => $parties,
            'questions' => $voteQuestions,
            'formManageCount' => $formManageCount,
            'plaintextUnlocked' => $plaintextUnlocked,
            'plaintextUnlockRemaining' => SensitiveReauth::plaintextUnlockRemainingSeconds(),
            'reauthRequired' => SensitiveReauth::isRequired(),
            'useTotp' => SensitiveReauth::isRequired() && \app\components\TotpService::isEnabledForUser(
                Users::findOne(Yii::$app->user->id) ?? new Users()
            ),
        ]);
    }

    /**
     * 建立選票 - 選擇選項建立者
     */
    public function actionSelectPasswd($voteID)
    {
        $model = new FormPasswords;

        // 基本資料
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);
        $ctx = FormPasswords::resolvePasswordContext($voteInfo);
        $passwordVoteID = $ctx['passwordVoteID'];
        $dataProvider = $model->getDataProvider(
            $passwordVoteID,
            Yii::$app->request->queryParams,
            $ctx['ballotVoteID'],
            $ctx['round']
        );
        $parties = $FormVotes->getVoteParty($passwordVoteID, Yii::$app->language, $voteInfo->partyOrNot);
        $marks = $model->getMarks($passwordVoteID);
        $votedBallot = FormPasswords::getVotedPasswordIds($ctx['ballotVoteID'], $ctx['round']);

        return $this->render('select-passwd', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'passwd' => new \app\models\Passwd,
            'voteInfo' => $voteInfo,
            'parties'  => $parties,
            'votedBallot'  => $votedBallot,
            'marks' => $marks,
            'plaintextUnlocked' => SensitiveReauth::isPlaintextUnlocked(),
            'plaintextUnlockRemaining' => SensitiveReauth::plaintextUnlockRemainingSeconds(),
            'reauthRequired' => SensitiveReauth::isRequired(),
            'useTotp' => SensitiveReauth::isRequired() && \app\components\TotpService::isEnabledForUser(
                \app\models\Users::findOne(Yii::$app->user->id) ?? new \app\models\Users()
            ),
        ]);
    }

    public function actionUnlockPlaintext($voteID)
    {
        return $this->performUnlockPlaintext($voteID, 'ballot/select-passwd');
    }

    public function actionLockPlaintext($voteID)
    {
        return $this->performLockPlaintext($voteID, 'ballot/select-passwd');
    }

    /**
     * 建立選票 - 選擇記名選票建立者
     */
    public function actionSelectCreator($voteID)
    {
        $request = Yii::$app->request;

        $model = new \app\models\FormBallotsCreator;

        // 保存新查詢
        if ($request->isPost && empty($request->post()[Yii::$app->tablePag->setPageGetName])) {
            $postData = Yii::$app->request->post();
            $model->saveInquire($postData);
            return $this->redirect(['select-creator','voteID'=>$voteID]);
        }

        // 查詢結果
        $personUser = Yii::$app->session->get($model::$sessionKey);
        if (!is_null($personUser)) {

            // 建立 Ary
            $instAry = Yii::$app->session->get('Share.instAry');
            $payTitle = Yii::$app->session->get('Share.payTitle');

            // 基本資料
            $FormVotes = new FormVotes;
            $voteInfo = $FormVotes->getVoteInfo($voteID);
            $parties = $FormVotes->getVoteParty($voteID);

            return $this->render('select-creator', [
                'voteID' => $voteID,
                'dataProvider' => $model->getDataProvider($personUser),
                'searchModel'  => $model->getSearchModel($personUser),
                'filterItem'   => $model::$filterItem,
                'showTable'    => true,
                'voteInfo'      => $voteInfo,
                'parties'      => array_flip($parties),
                // Ary
                'instSelectAry' => ArrayHelper::map($instAry ?: [],'instCode','instName'),
                'instAry'     => $instAry,
                'payTitleAry' => ArrayHelper::map($payTitle ?: [],'tCode','title'),
                'payTitle'  => $payTitle,
                'onJobAry'  => Yii::$app->params['ct.ballots.onJobAry'],
            ]);
        }
        
        //開始建立查詢
        return $this->render('select-creator', [
            'voteID' => $voteID,
            'filterItem' => $model::$filterItem,
            'showTable'  => false,
            // Ary
            'instSelectAry' => ArrayHelper::map(Yii::$app->session->get('Share.instAry'),'instCode','instName'),
            'payTitleAry' => ArrayHelper::map(Yii::$app->session->get('Share.payTitle'),'tCode','title'),
            'onJobAry'      => Yii::$app->params['ct.ballots.onJobAry'],
        ]);
    }

    /**
     * 清除選票
     */
    public function actionSelectClear($voteID)
    {
        Yii::$app->session->remove(\app\models\FormBallotsCreator::$sessionKey);
        return $this->redirect(['select-creator','voteID'=>$voteID]);
    }

    /**
     * 建立選票 - 設定當前選票
     */
    public function actionCreator($voteID, $party, $sysId)
    {
        $model = new FormManageVote($voteID, $party);
        $voteInfo = $model->VoteInfo;

        if(!is_null($ballotId = $model->getBallotId($sysId)))
        {
            return $this->redirect(['edit', 'voteID' => $voteID, 'party' => $party, 'ballotID' => $ballotId]);
        }

        $request = Yii::$app->request;
        if($request->isPost)
        {
            $postData = $request->post();
            $FormBallots = new FormBallots;
            if($FormBallots->creatorBallot($voteID, $postData, true))
            {
                $formData = ArrayHelper::getValue($postData, $FormBallots->formName());
                Logs::add(Logs::VOTE_BALLOT_CREATE, Json::encode($formData, 336));
                Yii::$app->session->setFlash('success', '選票新增成功！');
            }
            else {
                Logs::add(Logs::VOTE_BALLOT_CREATE_FAIL, Json::encode($FormBallots->errors, 336));
            }
            return $this->redirect(['edit', 'voteID' => $voteID, 'party' => $FormBallots->party, 'ballotID' => $FormBallots->ballotID]);
        }

        // 問題
        $partyQuestions = $model->getPartyQuestions($model->voteID, $model->party, true);
        $questions = ArrayHelper::index($partyQuestions, 'questionID');
        $parties = $model->getVoteParty($voteInfo->voteID, Yii::$app->language, $voteInfo->partyOrNot);
        foreach ($questions as $id => $question) {
            if(!$model->isCandidates($model->voteID, $model->party, $id)) // 無候選人
            {
                throw new \yii\web\HttpException( 400, "問題:{$question['title']} 尚未建立候選人");
            }
        }

        $changer = [$sysId, Yii::$app->user->identity->getId()];
        return $this->render('ballot', [
            'model' => $model,
            'creator' => true,
            'questions'  => $questions,  // 取得問題資訊
            'parties'  => $parties,  // 取得組別資訊
            'candiConfig' => $model->getCandidateConfig(),  // 候選人配置
            'voteBallot'  => $model->getNewVoteBallot($sysId, true),  // 取得投票者資訊
            'ballotChanger'  => $model->getNewBallotChanger($changer, null, SensitiveReauth::isPlaintextUnlocked()),    // 取得新選票建立者列表
            'ballotSelected' => $model->getBallotSelected(),    // 取得選票
        ]);
    }

    /**
     * 編輯選票
     */
    public function actionEdit($voteID,$party,$ballotID)
    {
        $model = new FormManageVote($voteID,$party);
        $voteInfo = $model->VoteInfo;

        $request = Yii::$app->request;
        if($request->isPost)
        {
            $postData = $request->post();
            $FormBallots = new \app\models\FormBallots;
            $status = $FormBallots->updateBallot($voteID,$ballotID,$postData);
            if($status)
            {
                $formData = ArrayHelper::only($postData, [$FormBallots->formName(), 'selection']);
                Logs::add(Logs::VOTE_BALLOT_EDIT, Json::encode(compact('voteID', 'ballotID')+$formData, 336));
                Yii::$app->session->setFlash('success', '選票修改成功！');
            }
            else {
                Logs::add(Logs::VOTE_BALLOT_EDIT_FAIL, Json::encode(compact('voteID')+$FormBallots->errors, 336));
            }
            return $this->redirect(['edit', 'voteID'=>$voteID, 'party'=>$party, 'ballotID'=>$ballotID]);
        }

        // 問題
        $partyQuestions = $model->getPartyQuestions($model->voteID, $model->party, true);
        $questions = ArrayHelper::index($partyQuestions, 'questionID');
        $parties = $model->getVoteParty($voteInfo->voteID, Yii::$app->language, $voteInfo->partyOrNot);

        foreach ($questions as $id => $question) {
            if(!$model->isCandidates($model->voteID, $model->party, $id)) // 無候選人
            {
                throw new \yii\web\HttpException(400, "問題:{$question['title']} 尚未建立候選人");
            }
        }

        $voteBallot = $model->getVoteBallot($ballotID);
        $changer = [$voteBallot->creator, $voteBallot->modifier];
        return $this->render('ballot', [
            'model' => $model,
            'creator' => false,
            'candiConfig' => $model->getCandidateConfig(),  // 候選人配置
            'voteBallot'  => $voteBallot,  // 取得投票者資訊
            'questions'  => $questions,  // 取得問題資訊
            'parties'  => $parties,  // 取得組別資訊
            'ballotChanger'  => $model->getNewBallotChanger($changer, null, SensitiveReauth::isPlaintextUnlocked()),    // 取得新選票建立者列表
            'ballotSelected' => $model->getBallotSelected($ballotID),    // 取得選票
        ]);
    }
    
    /**
     * 選票列印
     *
     * @param  mixed $voteID
     * @return void
     */
    public function actionPrint($voteID, $questionID)
    {
        $model = new \app\models\FormManageVote($voteID);
        $voteInfo = $model->votesInfo;

        // 是否建立候選人
        $question = (new Questions)->getQuestionInfo($questionID);
        if ($voteInfo->candiConfig == FormVotes::CANDI_CONFIG_BY_Q) {
            $candiConfig = $model->getCandidateConfig($voteID, $questionID);
        }
        else {
            $candiConfig = $model->getCandidateConfig($voteID);
        }

        $candidateLists = $model->getDataProvider($model->voteID, $question->party, $questionID, false, $candiConfig->columnNum);

        $this->layout = 'ballot/print';

        return $this->render('print', [
            'model' => $model,
            'candiConfig' => $candiConfig,
            'candidateLists' => $candidateLists,
            'question' => $question,
            'voteInfo' => $voteInfo,
        ]);
    }

    /**
     * 刪除選票
     */
    public function actionDelete($voteID, $ballotID)
    {
        $FormBallots = new FormBallots;
        if($FormBallots->deleteBallot($voteID, $ballotID))
        {
            Logs::add(Logs::VOTE_BALLOT_DELETE, Json::encode(compact('voteID', 'ballotID'), 336));
            Yii::$app->session->setFlash('success', '選票刪除成功！');
        }
        return $this->redirect(['index', 'voteID' => $voteID]);
    }

    /**
     * 刪除投票所有選票
     */
    public function actionDeleteAll($voteID, $party=null)
    {
        // 基本資料
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);
        $parties = $FormVotes->getVoteParty($voteInfo->voteID, Yii::$app->language);

        $FormBallots = new FormBallots;
        if((is_null($party) || isset($parties[$party])) && $FormBallots->deleteAllBallot($voteID, $voteInfo->round, $party))
        {
            Logs::add(Logs::VOTE_BALLOT_DELETE, Json::encode(compact('voteID', 'party'), 336));
            if(is_null($party))
                Yii::$app->session->setFlash('success', '成功刪除【全部】選票！');
            else
                Yii::$app->session->setFlash('success', '成功刪除【'.$parties[$party].'】選票！');
        }
        else
        {
            Logs::add(Logs::VOTE_BALLOT_DELETE_FAIL, Json::encode(compact('voteID', 'party'), 336));
            Yii::$app->session->setFlash('success', '選票刪除失敗！請確認組別存在！');
        }
        return $this->redirect(['index', 'voteID'=>$voteID]);
    }
    
    /**
     * 選票匯出（含明文密碼時需二次驗證）
     *
     * @param  string $voteID
     * @param  int|null $type 匯出類型（POST type 優先）
     * @return \yii\web\Response
     */
    public function actionExport($voteID, $type = null)
    {
        $request = Yii::$app->request;
        $redirect = fn () => $this->redirect($this->resolveExportReturnUrl($request, $voteID));

        if (!$request->isPost) {
            Yii::$app->session->setFlash('warning', '請由選票管理頁面使用「選票統計匯出」表單操作。');
            return $redirect();
        }

        if ($request->post('exportAcknowledged') !== '1') {
            Yii::$app->session->setFlash('error', '請確認選票統計匯出操作後再送出。');
            return $redirect();
        }

        $adminUser = Users::findOne(Yii::$app->user->id);
        if (!SensitiveReauth::verify(
            $request->post('adminPassword'),
            $request->post('totpCode')
        )) {
            Yii::$app->session->setFlash(
                'error',
                $adminUser
                    ? SensitiveReauth::failureMessage($adminUser)
                    : Yii::t('app', '二次驗證失敗。')
            );
            return $redirect();
        }

        $exportType = $request->post('type', $type);
        if ($exportType === '') {
            $exportType = null;
        }

        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);
        $ballotCount = (int) Ballots::find()
            ->where(['voteID' => $voteID, 'round' => $voteInfo->round])
            ->count('DISTINCT ballotID');

        if ($exportType === 'content') {
            $questionID = $request->post('questionID');
            if ($questionID === null || $questionID === '') {
                Yii::$app->session->setFlash('error', '請選擇封存單問題。');
                return $redirect();
            }

            Logs::add(Logs::VOTE_BALLOT_EXPORT, Json::encode([
                'voteID' => $voteID,
                'round' => (int) $voteInfo->round,
                'type' => 'content',
                'questionID' => $questionID,
                'ballotCount' => $ballotCount,
                'format' => 'content_archive',
            ], 336), ['voteID' => $voteID]);

            return $this->renderContentArchive($voteID, $questionID);
        }

        Logs::add(Logs::VOTE_BALLOT_EXPORT, Json::encode([
            'voteID' => $voteID,
            'round' => (int) $voteInfo->round,
            'type' => $exportType,
            'ballotCount' => $ballotCount,
            'format' => 'csv',
        ], 336), ['voteID' => $voteID]);

        $model = new FormBallots();

        return $model->export($voteID, $exportType);
    }

    /**
     * 匯出失敗時返回來源頁（檢票 status 或選票管理 index）
     */
    protected function resolveExportReturnUrl($request, $voteID): array|string
    {
        $returnUrl = $request->post('returnUrl');
        if (is_string($returnUrl) && $returnUrl !== '') {
            $returnUrl = trim($returnUrl);
            if (str_starts_with($returnUrl, '/') && !str_starts_with($returnUrl, '//')) {
                return $returnUrl;
            }
        }

        return ['index', 'voteID' => $voteID];
    }

    /**
     * 封存單列印（已併入選票統計匯出；直接 GET 不允許）
     */
    public function actionContent($voteID, $questionID = null)
    {
        Yii::$app->session->setFlash('warning', '請由「選票統計匯出」完成二次驗證後開啟投票結果封存單。');

        return $this->redirect(['index', 'voteID' => $voteID]);
    }

    /**
     * 投票結果封存單（二次驗證後由 actionExport 呼叫）
     */
    protected function renderContentArchive(string $voteID, $questionID)
    {
        $model = new FormBallots();
        $votes = new FormVotes();
        $voteInfo = $votes->getVoteInfo($voteID);
        $questions = Questions::find()
            ->where(['voteID' => $voteID, 'round' => $voteInfo->round])
            ->indexBy('questionID')->asArray()->all();

        if (!isset($questions[$questionID])) {
            throw new \yii\web\NotFoundHttpException('問題不存在。');
        }

        if ($questions[$questionID]['party'] != Questions::ALL_PARTY_CODE) {
            $model->party = $questions[$questionID]['party'];
        }

        $decryptPlaintext = $voteInfo->type == Votes::TYPE_ANON;
        $sysidList = $model->getSysidByBallots(
            $model->getBallotList($voteID, $voteInfo->round),
            $voteInfo->type,
            $decryptPlaintext
        );
        $allBallots = $model->getBallotList($voteID, $voteInfo->round)->asArray()->all();
        $ballots = $model->getBallotContent($voteID, $voteInfo->round, $questionID);
        $ballots = ArrayHelper::index($ballots, null, 'ballotID');
        $dataProvider = (new \app\models\DataProvider)->getBasicArrayProvider($allBallots, false);

        $this->layout = 'ballot/content';

        return $this->render('content', [
            'model' => $model,
            'ballots' => $ballots,
            'voteInfo' => $voteInfo,
            'questions' => $questions,
            'sysidList' => $sysidList,
            'dataProvider' => $dataProvider,
        ]);
    }
    
    /**
     * 選票匯入
     *
     * @param  string $voteID
     * @return void
     */
    public function actionImport($voteID)
    {
        $model = new \app\models\FormBallots;
        $vote = new FormVotes;
        $voteInfo = $vote->getVoteInfo($voteID);
        $bindVotes = $vote->getBindVotes($voteID);
        $questions = ArrayHelper::map(
            Questions::find()->where(['voteID' => $voteID, 'round' => $voteInfo->round])->all(),
            'questionID',
            'title'
        );
        $questionIDs = array_keys($questions);

        $request = Yii::$app->request;
        if($request->isPost)
        {
            $postData = $request->post();
            $import = $model->importBallots($voteInfo, $questionIDs, $postData);
            if($import)
            {
                Logs::add(Logs::VOTE_BALLOT_IMPORT, Json::encode($postData, 336));
                Yii::$app->session->setFlash('success', "選票匯入成功，共匯入{$import}張！");
                return $this->redirect(['index', 'voteID' => $voteID]);
            }
            else {
                Logs::add(Logs::VOTE_BALLOT_IMPORT_FAIL, Json::encode($postData, 336));
                Yii::$app->session->setFlash('danger', '選票匯入失敗或所有選票皆已匯入！');
            }
        }

        return $this->render('import', [
            'model' => $model,
            'voteInfo' => $voteInfo,
            'bindVotes' => $bindVotes,
            'questions' => $questions,
        ]);
    }
    
    /**
     * 匯入選票 - 取得問題
     *
     * @param  string $voteID 投票辨識碼
     * @param  mixed $round 輪次
     * @return void
     */
    public function actionGetImportQuestions($voteID, $round)
    {
        $importVoteID = Yii::$app->request->post('voteID');
        $questions = (new Questions)->getQuestionsInfo($importVoteID, $round);;

        return Json::encode($questions);
    }
}
