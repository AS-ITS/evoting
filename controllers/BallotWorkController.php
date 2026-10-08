<?php
namespace app\controllers;

use Yii;
use app\models\Logs;
use yii\helpers\Json;
use app\models\Ballots;
use app\models\FormVotes;
use app\models\Passwords;
use app\models\Questions;
use app\models\FormBallots;
use app\models\FormResults;
use app\models\FormManageVote;
use app\models\FormCandiConfig;
use app\models\FormManageCount;
use app\components\SensitiveReauth;
use app\models\Users;
use app\models\FormResultsConfig;

/**
 * 投票新修刪選票、選票相關設定
 */
class BallotWorkController extends \app\components\Controller
{
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
                        'actions' => ['status', 'count', 'result'],
                        'allow' => false,
                        'matchCallback' => function ($rule, $action) use (&$resultData) {
                            $voteID = Yii::$app->getRequest()->get('voteID');
                            $model = new \app\models\FormResults;
                            $voteInfo = (new FormVotes)->getVoteInfo($voteID);
                            $result = $model->isResults($voteID, $voteInfo->round);
                            
                            if(!$result)
                            {
                                return true;
                            }
                            return false;
                        },
                        'denyCallback' => function ($rule, $action)
                        {
                            if (!Yii::$app->user->isGuest)
                            {
                                $voteID = Yii::$app->getRequest()->get('voteID');
                                Yii::$app->session->setFlash('warning', Yii::t('app', '尚未開票！'));
                                return $this->redirect(['ballot-work/setting','voteID'=>$voteID]);
                            }
                            $action->controller->NotAllowedAccess();
                        }
                    ],
                    [
                        // 不設定actions表示全部
                        'actions' => ['index'],
                        'allow' => true,
                        'permissions' => ['ballotWorkIndex'],
                    ],
                    [
                        // 不設定actions表示全部
                        'actions' => ['status', 'ballots-detail'],
                        'allow' => true,
                        'permissions' => ['ballotWorkStatus'],
                        'roleParams' => ['voteID' => Yii::$app->request->get('voteID')],
                    ],
                    [
                        // 不設定actions表示全部
                        'actions' => ['setting'],
                        'allow' => true,
                        'permissions' => ['ballotWorkSetting'],
                        'roleParams' => ['voteID' => Yii::$app->request->get('voteID')],
                    ],
                    [
                        // 不設定actions表示全部
                        'actions' => ['count'],
                        'allow' => true,
                        'permissions' => ['ballotWorkCount'],
                        'roleParams' => ['voteID' => Yii::$app->request->get('voteID')],
                    ],
                    [
                        // 不設定actions表示全部
                        'actions' => ['result'],
                        'allow' => true,
                        'permissions' => ['ballotWorkResult'],
                        'roleParams' => ['voteID' => Yii::$app->request->get('voteID')],
                    ],
                ],
                'denyCallback' => self::denyCallback()
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'https' => ['post','get'],
                ],
            ],
        ];
    }
    
    /**
     * 開票作業首頁
     *
     * @return string
     */
    public function actionIndex()
    {
        $model = new FormVotes;
        $model->setScenario('search');
        $query = $model->getVoteList('ballotWork', Yii::$app->request->queryParams);
        $sysidList = $model->getCnByVote($query);
        return $this->render('index',[
            'model' => $model,
            'dataProvider' => (new \app\models\DataProvider)->getBasicDataProvider($query),
            'sysidList' => $sysidList,
        ]);
    }
    
    /**
     * 投票情形
     *
     * @param  string $voteID 投票編號
     * @return string
     */
    public function actionStatus($voteID)
    {
        $manageCount = new FormManageCount($voteID);
        $ballotCountSort = $manageCount->ballotCountSort;
        // 選票設定
        $FormBallots = new FormBallots;
        $ballotList = $FormBallots->getBallotList($voteID, $manageCount->voteInfo->round)->asArray()->all();
        // 取得選票資訊
        $FormManageVote = new FormManageVote($voteID);
        $ballotCountAry = $FormManageVote->getBallotCount();
        // 密碼
        $passwordListByParty = (new Passwords())->getPasswordListByParty($voteID, $manageCount->voteInfo);

        $contentQuestions = [];
        foreach ($manageCount->getVoteQuestions() as $questionID => $question) {
            $contentQuestions[$questionID] = $question->title;
        }

        return $this->render('status',[
            'model' => $manageCount,
            'ballotList' => $ballotList,
            'ballotCountSort' => $ballotCountSort,
            'ballotCountAry' => $ballotCountAry,
            'passwordList' => $passwordListByParty,
            'reauthRequired' => SensitiveReauth::isRequired(),
            'useTotp' => SensitiveReauth::isRequired() && \app\components\TotpService::isEnabledForUser(
                Users::findOne(Yii::$app->user->id) ?? new Users()
            ),
            'contentQuestions' => $contentQuestions,
        ]);
    }

    /**
     * 開票設定
     *
     * @param  string $voteID 投票編號
     * @return string
     */
    public function actionSetting($voteID)
    {
        $model = new \app\models\FormResultsConfig;
        $resultData = $model->getData($voteID);

        // 投票資訊
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);

        $request = Yii::$app->request;
        if($request->isPost)// 提交表單
        {
            $postData = $request->post();
            $action = $postData['action'];

            if ($resultData->updateConfig($resultData, $postData)) {
                switch($action)
                {
                    case 'make': // 開票
                        $model = new \app\models\FormResults;
                        if($model->isResults($voteID, $voteInfo->round))
                        {
                            Logs::add(Logs::VOTE_RESULT_MAKE_FAIL, Json::encode(compact('voteID')+['message' => '已開票，如需重新開票請選擇重新開票！']));
                            Yii::$app->session->setFlash('warning', '已開票，如需重新開票請選擇重新開票！');
                        }
                        else if($model->createResults($voteID))
                        {
                            Logs::add(Logs::VOTE_RESULT_MAKE, Json::encode(compact('voteID')));
                            return $this->redirect(['ballot-work/status', 'voteID' => $voteID]);
                        }
                        else
                        {
                            Logs::add(Logs::VOTE_RESULT_MAKE_FAIL, Json::encode(compact('voteID')));
                            Yii::$app->session->setFlash('error', '開票失敗！');
                        }
                        return $this->redirect(['ballot-work/setting', 'voteID' => $voteID]);
                        break;
                }
            }
        }

        // 候選名單配置
        $FormCandiConfig = new FormCandiConfig;
        $candiConfig = $FormCandiConfig->getConfigWithVoteID($voteID);

        return $this->render('setting',[
            'model' => is_null($resultData) ? $model : $resultData,
            'isConfig' => !is_null($resultData),
            'showFieldSort' => $model::$defFieldSort,
            'candiConfig' => $candiConfig,
            'voteInfo' => $voteInfo,
            'isResults' => (new \app\models\FormResults)->isResults($voteID, $voteInfo->round),
        ]);
    }
    
    /**
     * 計票顯示
     *
     * @param  string $voteID
     * @return string
     */
    public function actionCount($voteID)
    {
        $manageCount = new FormManageCount($voteID);
        $ballotCountSort = $manageCount->ballotCountSort;
        // 選票設定
        $FormBallots = new FormBallots;
        $ballotList = $FormBallots->getBallotList($voteID, $manageCount->voteInfo->round)->asArray()->all();
        // 取得選票資訊
        $FormManageVote = new FormManageVote($voteID);
        $ballotCountAry = $FormManageVote->getBallotCount();
        // 密碼
        $passwordListByParty = (new Passwords())->getPasswordListByParty($voteID, $manageCount->voteInfo);

        return $this->render('count', [
            'model' => $manageCount,
            'ballotList' => $ballotList,
            'ballotCountSort' => $ballotCountSort,
            'ballotCountAry' => $ballotCountAry,
            'passwordList' => $passwordListByParty,    // 密碼
            'showExportModal' => false // 組別得票匯出
        ]);
    }
    
    /**
     * 投票結果
     *
     * @param  mixed $voteID
     * @return void
     */
    public function actionResult($voteID, $type=null)
    {
        $model = new FormResults;

        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);

        $resultConfig = (new FormResultsConfig)->getData($voteID);

        if(is_null($resultConfig))
        {
            Yii::$app->session->setFlash('warning', '請先建立開票設定！');
            return $this->redirect(['count/result','voteID'=>$voteID]);
        }
        $parties = $FormVotes->getVoteParty($voteInfo->voteID, Yii::$app->language, $voteInfo->partyOrNot);
        $questions = $FormVotes->getVoteQuestion($voteInfo->voteID, $voteInfo->round, Yii::$app->language);
        if ($type == 'preview') {
            $view = '@app/views/vote/result';
            $party = false;
            $status = true;
        }
        else {
            $view = 'result';
            $party = false;
            $status = false;
        }
        return $this->render($view, [
            'model' => $model,
            'FormVotes' => $FormVotes,
            'voteInfo' => $voteInfo,
            'candiConfig' => (new FormCandiConfig)->getConfigWithVoteID($voteID),
            'resultConfig' => $resultConfig,
            'parties' => $parties,
            'questions' => $questions,
            'dataProvider' => $model->getDataProvider($voteID, $voteInfo->round, $resultConfig, $party, $status, Yii::$app->request->queryParams),
        ]);
    }
    
    /**
     * 選票詳細內容ajax
     *
     * @param  string $voteID
     * @param  string $party
     * @param  int $questionID
     * @return string
     */
    public function actionBallotsDetail($voteID, $party, $questionID, $valid)
    {
        if (Yii::$app->request->isAjax) {
            $FormVotes = new FormVotes;
            $voteInfo = $FormVotes->getVoteInfo($voteID);
            $formManageCount = new FormManageCount($voteID);
            // 投票者資訊
            $formBallots = new FormBallots();
            $sysidList = $formBallots->getSysidByBallots(
                $formBallots->getBallotList($voteID, $voteInfo->round),
                $voteInfo->type,
                SensitiveReauth::isPlaintextUnlocked()
            );
            // 問題
            $questions = $formManageCount->voteQuestions;
            $question = $questions[$questionID];
            $ballotCountAry = (new FormManageVote($voteID))->getBallotCount();
            // 選票資訊
            if ($party == Questions::ALL_PARTY_CODE) {
                $ballotsSelected = Ballots::find()
                ->where(['voteID' => $voteID, 'round' => $voteInfo->round])
                ->with([
                    'ballotsSelected' => function (\yii\db\ActiveQuery $query) use ($questionID, $party) {
                        $query->andWhere(['questionID' => $questionID, 'party' => $party]);
                    },
                ])->all();
            }
            else {
                $ballotsSelected = Ballots::find()
                ->where(['voteID' => $voteID, 'round' => $voteInfo->round, 'party' => $party])
                ->with([
                    'ballotsSelected' => function (\yii\db\ActiveQuery $query) use ($questionID) {
                        $query->andWhere(['questionID' => $questionID]);
                    },
                ])->all();
            }
            return $this->renderAjax('_ballots_detail', 
                compact('formManageCount', 'question', 'questions', 'ballotsSelected', 'ballotCountAry', 'valid', 'sysidList')
            );
        }
    }
}
