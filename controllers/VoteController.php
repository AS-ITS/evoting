<?php
namespace app\controllers;

use Yii;

use app\models\Votes;
use app\models\Config;
use app\models\FormVotes;
use app\models\Passwords;
use app\models\Questions;
use app\models\FormBallots;
use yii\helpers\FileHelper;
use app\components\helper\ArrayHelper;
use app\models\FormManageVote;
use app\models\FormCountExport;
use app\models\FormManageCount;
use app\components\helper\FileLoader;
use yii\helpers\Url;

class VoteController extends \app\components\Controller
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
                    /**
                     * 投票資訊頁面
                     */
                    // 新增一個專門檢查匿名使用者若已登入需先登出的處理
                    [
                        'actions' => ['vote-detail', 'start-vote', 'result'],
                        'allow' => !Yii::$app->anon->isGuest,
                        'matchCallback' => function ($rule, $action) use (&$voteID, &$voteInfo) {
                            // 首先確保 voteInfo 已經取得
                            if (is_null($voteInfo)) {
                                $voteID = Yii::$app->getRequest()->get('voteID');
                                $model  = new \app\models\FormVotes;
                                $voteInfo = $model->getVoteInfo($voteID);
                            }
                            // 表決投票不需要登入驗證
                            if($voteInfo->type == Votes::TYPE_NO_AUTH)
                                return false;
                            // 只對匿名投票執行此規則，記名投票判斷下一規則
                            if ($voteInfo->type != Votes::TYPE_ANON) {
                                return false;
                            }
                            // 投票明細及匿名密碼 或 查看投票結果需登入驗證
                            $toDate = strtotime(date('Y-m-d H:i:s'));
                            if ($voteInfo->active == Votes::STATUS_TERMINATE && $toDate > strtotime($voteInfo->verifyEnd) && $voteInfo->resultsConfig->isShow == 1 && $voteInfo->resultsConfig->isLogin == 0){
                                // 不須登入
                            	if ($action->id == 'result')
                       	            return false;
                            	elseif ($action->id == 'vote-detail'){
                       	            // 若用投票結果短網址，會先開vote-detail後需輸入密碼，但isLogin是否，故直接轉到結果頁
                       	            $url = Url::to(['result', 'voteID' => $voteID], true); // true 代表產生絕對網址
                       	            Yii::$app->getResponse()->redirect($url)->send();
                       	            Yii::$app->end();
                       	            return false;
                                }
                            }
                            // 是否略過投票資訊頁面 & 查看投票資訊是否驗證，及若"投票結果顯示=否"則依投票資訊頁規則顯示
                            if ((($action->id == 'vote-detail' || $action->id == 'start-vote') && $voteInfo->type == Votes::TYPE_ANON) || ($action->id =='result' && $voteInfo->resultsConfig->isShow == 1)){
                                if ($voteInfo->skipDetail == 0){
                                    if ($action->id == 'vote-detail' && $voteInfo->authBeforeDetail == 0){
                                        // 不須登入
                                        return false;
                                    }
                                }
                            }
                            // 若非該投票已登入
                            $authData = Yii::$app->anon->identity ? Yii::$app->anon->identity->getAuthData() : [];
                            if (!isset($authData['voteID']) || ($authData['voteID'] != $voteID && $authData['voteID'] != $voteInfo->bindWhichVote)) {
                                // 自動登出匿名使用者並重導到匿名登入頁
                                Yii::$app->anon->logout(false);
                                Yii::$app->anon->loginUrl = ['site/password', 'voteID' => $voteID];
                                Yii::$app->getResponse()->redirect(Yii::$app->anon->loginUrl)->send();
                                Yii::$app->end();
                                return false;
                            } else return true;	// 需登入
                            return false; // 下一規則
                        },
                        'denyCallback' => self::denyCallback('anon'),
                    ],
                    [   // 需驗證，匿名投票
                        'actions' => ['vote-detail'],
                        'allow' => !Yii::$app->anon->isGuest,
                        'matchCallback' => function ($rule, $action) use (&$voteID,&$voteInfo) {
                            // 如果 voteInfo 還沒取得
                            if(is_null($voteInfo))
                            {
                                $voteID = Yii::$app->getRequest()->get('voteID');
                                $model  = new FormVotes;
                                $voteInfo = $model->getVoteInfo($voteID);
                            }
                            // 已有正確登入狀態，則依據 voteInfo 的設定檢查是否允許存取詳細頁面
                            if($voteInfo->type == Votes::TYPE_ANON && $voteInfo->authBeforeDetail == '1')
                            {
                                switch(true)
                                {
                                    case Yii::$app->anon->isGuest:
                                        return true;
                                        break;

                                    case !(ArrayHelper::keyExists('voteID',Yii::$app->anon->identity->getAuthData())):
                                    case Yii::$app->anon->identity->getAuthData()['voteID'] != $voteID && 
                                        Yii::$app->anon->identity->getAuthData()['voteID'] != $voteInfo->bindWhichVote:
                                        return false;
                                        break;
                                    
                                    default:
                                        return true;
                                        break;
                                }
                            }
                            return false;
                        },
                        'denyCallback' => self::denyCallback('anon')
                    ],
                    [   // 無須驗證，匿名投票
                        'actions' => ['vote-detail'],
                        'allow' => true,
                        'matchCallback' => function ($rule, $action) use (&$voteID,&$voteInfo) {
                            
                            if(is_null($voteInfo))
                            {
                                $voteID = Yii::$app->getRequest()->get('voteID');
                                $model  = new FormVotes;
                                $voteInfo = $model->getVoteInfo($voteID, true);
                            }
                            
                            if($voteInfo->authBeforeDetail == '1')
                            {
                                return false;
                            }
                            return true;
                        },
                        'denyCallback' => self::denyCallback()
                    ],
                    /**
                     * 投票頁面
                     */
                    [   // 需驗證，匿名投票
                        'actions' => ['start-vote', 'save-vote', 'check-ballot', 'record-ballot', 'record-step'],
                        'allow' => !Yii::$app->anon->isGuest,
                        'matchCallback' => function ($rule, $action) use (&$voteID, &$voteInfo) {
                            if(is_null($voteInfo))
                            {
                                $voteID = Yii::$app->getRequest()->get('voteID');
                                $model  = new FormVotes;
                                $voteInfo = $model->getVoteInfo($voteID);
                            }
                            if($voteInfo->type == Votes::TYPE_ANON)
                            {
                                // 是否超過投票時間
                                if ($voteInfo->checkVoteExpired() || $voteInfo->checkVoteReady()) {
                                    return $this->redirect((new FormManageVote($voteID))->finishPage);
                                }

                                $model  = new FormVotes;
                                switch(true)
                                {
                                    case Yii::$app->anon->isGuest:
                                        return true;
                                        break;

                                    case !$model->isVoteOpen($voteID):
                                    case !(ArrayHelper::keyExists('voteID',Yii::$app->anon->identity->getAuthData())):
                                    case Yii::$app->anon->identity->getAuthData()['voteID'] != $voteID && 
                                        Yii::$app->anon->identity->getAuthData()['voteID'] != $voteInfo->bindWhichVote:
                                        return false;
                                        break;
                                    
                                    default:
                                        return true;
                                        break;
                                }
                            }
                            return false;
                        },
                        'denyCallback' => self::denyCallback('anon')
                    ],
                    [   // 無須驗證，匿名投票
                        'actions' => ['start-vote', 'save-vote', 'check-ballot', 'record-ballot', 'record-step'],
                        'allow' => true,
                        'matchCallback' => function ($rule, $action) use (&$voteID,&$voteInfo) {
                            if(is_null($voteInfo))
                            {
                                $voteID = Yii::$app->getRequest()->get('voteID');
                                $model  = new FormVotes;
                                $voteInfo = $model->getVoteInfo($voteID);
                            }
                            if($voteInfo->type == Votes::TYPE_NO_AUTH)
                                return true;
                            return false;
                        },
                        'denyCallback' => self::denyCallback()
                    ],
                    [
                        'actions' => ['vote-detail', 'start-vote', 'save-vote', 'check-ballot', 'record-ballot', 'record-step'],
                        'allow' => false
                    ],
                    /**
                     * 投票結果
                     */
                    [   // 需驗證，匿名投票
                        'actions' => ['result', 'count', 'count-export'],
                        'allow' => !Yii::$app->anon->isGuest,
                        'matchCallback' => function ($rule, $action) use (&$voteID,&$voteInfo) {
                            if(is_null($voteInfo))
                            {
                                $voteID = Yii::$app->getRequest()->get('voteID');
                                $model  = new FormVotes;
                                $voteInfo = $model->getVoteInfo($voteID, true);
                            }
                            if($voteInfo->resultsConfig->isLogin == '0' && $action->id == 'result')
                            {
                                return false;
                            }

                            if($voteInfo->type == Votes::TYPE_ANON)
                            {
                                switch(true)
                                {
                                    case Yii::$app->anon->isGuest:
                                        return true;
                                        break;

                                    case !(ArrayHelper::keyExists('voteID',Yii::$app->anon->identity->getAuthData())):
                                    case Yii::$app->anon->identity->getAuthData()['voteID'] != $voteID && 
                                        Yii::$app->anon->identity->getAuthData()['voteID'] != $voteInfo->bindWhichVote:
                                        return false;
                                        break;
                                    
                                    default:
                                        return true;
                                        break;
                                }
                            }
                            return false;
                        },
                        'denyCallback' => self::denyCallback('anon')
                    ],
                    [   // 無須驗證，匿名投票
                        'actions' => ['result', 'count', 'count-export'],
                        'allow' => true,
                        'matchCallback' => function ($rule, $action) use (&$voteID,&$voteInfo) {
                            if(is_null($voteInfo))
                            {
                                $voteID = Yii::$app->getRequest()->get('voteID');
                                $model  = new FormVotes;
                                $voteInfo = $model->getVoteInfo($voteID, true);
                            }
                            if($voteInfo->resultsConfig->isLogin == '0')
                            {
                                return true;
                            }
                            if($voteInfo->type == Votes::TYPE_NO_AUTH)
                                return true;
                            return false;
                        },
                        'denyCallback' => self::denyCallback()
                    ],
                    [   // 投票狀態不為進行或時間截止，將無法訪問
                        'actions' => ['vote-detail', 'start-vote', 'save-vote', 'record-ballot', 'record-step', 'check-ballot', 'result', 'count', 'count-export'],
                        'allow' => false,
                        'matchCallback' => function ($rule, $action) use (&$voteID,&$voteInfo) {
                            $model  = new FormVotes;
                            if(is_null($voteInfo))
                            {
                                $voteID = Yii::$app->getRequest()->get('voteID');
                                $voteInfo = $model->getVoteInfo($voteID, true);
                            }
                            if(!$model->isVoteOpen($voteID))
                                return true;
                            return false;
                        },
                        'denyCallback' => self::denyCallback()
                    ],
                    [
                        'actions' => ['result'],
                        'allow' => false
                    ],
                    /**
                     * 登入及未登入處理，其餘拒絕訪問
                     */
                    [
                        'actions' => ['index', 'result-list', 'download-file', 'short-url', 'vote-finish', 'vote-voted'],
                        'allow' => true,
                        'roles' => ['?'],// 未登入
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],// 已登入
                    ],
                    [
                        'allow' => false,//拒絕訪問，未填 actions 或 allow 表示全部
                    ]
                ],
                'denyCallback' => self::denyCallback()
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'https' => ['post','get'],
                    'logout' => ['post'],
                ],
            ],
            // 'corsFilter' => [
            //     'class' => \yii\filters\Cors::className(),
            //     'cors' => [
            //         'Origin' => ['*'], // restrict access to
            //         'Access-Control-Request-Method' => ['POST'],
            //         'Access-Control-Request-Headers' => ['X-Wsse'], 
            //         'Access-Control-Allow-Credentials' => true,
            //         'Access-Control-Max-Age' => 3600,
            //         'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page'],
            //     ],
            // ],
        ];
    }

    /**
     * 於首頁"開放中之投票項目"
     */
    public function actionIndex()
    {
        // 如果有設定首頁導向網址，則導向該網址
        $config = Config::findOne(Yii::$app->id);
        if (!empty($config->indexUrl)) {
            // 設定為首頁網址會出現多次轉址的錯誤
            $currentUrl = Yii::$app->request->getUrl(); // 取得完整路徑與查詢字串
            // 使用 parse_url 僅取得 path 部分
            $parsedCurrentUrl = parse_url($currentUrl, PHP_URL_PATH);
            $configUrlPath = parse_url($config->indexUrl, PHP_URL_PATH);

            // 標準化斜線：例如移除尾端多餘的斜線
            $normalizedCurrentPath = rtrim($parsedCurrentUrl, '/');
            $normalizedConfigPath = rtrim($configUrlPath, '/');

            // 比較路徑是否相同
            if ($normalizedCurrentPath !== $normalizedConfigPath) {
                    return $this->redirect($config->indexUrl);
            }
        }
        // 樣板
        $this->layout = empty($config->homeLayout) ? 'main' : $config->homeLayout;
        $view = (empty($config->homeLayout) || $config->homeLayout == 'main')  ? 'index' : 'index_'.$config->homeLayout;
        $model = new FormVotes;
        return $this->render($view, [
            'model' => $model,
            'dataProvider' => (new \app\models\DataProvider)->getBasicDataProvider(
                $model->getVoteList('open')
            ),
            'config' => $config
        ]);
    }
    
    /**
     * 短網址，若投票存在導向投票資訊頁
     *
     * @param  string $shortUrl
     * @return void
     */
    public function actionShortUrl($shortUrl)
    {
        $voteInfo = FormVotes::find()->where(['shortUrl' => $shortUrl])->one();
        if (is_null($voteInfo)) {
            throw new \yii\web\HttpException(400, '投票項目不存在！');
        }
        else {
            $this->redirect(['vote/vote-detail', 'voteID' => $voteInfo->voteID]);
        }
    }

    /**
     * 投票詳細資料
    */
    public function actionVoteDetail($voteID, $questionID=null)
    {
        $model = new FormVotes;
        $voteInfo = $model->getVoteInfo($voteID);
        if (is_null($voteInfo)) {
            throw new \yii\web\HttpException(400, '投票項目不存在！');
        }
        // 判斷若已結束則顯示投票結果
        $toDate = strtotime(date('Y-m-d H:i:s'));
        if ($voteInfo->active == Votes::STATUS_TERMINATE && $toDate > strtotime($voteInfo->verifyEnd) && $voteInfo->resultsConfig->isShow == '1') {
            return $this->redirect(['result', 'voteID' => $voteID]);
        }
        // 若"是否略過投票資訊頁面=否"及"查看投票資訊是否驗證=否"則於vote-detail點我要投票登入後，還是會顯示vote-detail，故要改顯示投票頁面
        $referer = parse_url(Yii::$app->request->referrer);
        if (($voteInfo->active == Votes::STATUS_ACTIVE || $voteInfo->active == Votes::STATUS_BACKFILL) && $toDate >= strtotime($voteInfo->openStart) && $toDate <= strtotime($voteInfo->openEnd) 
            && $voteInfo->skipDetail == 0 && $voteInfo->authBeforeDetail == 0 && isset($referer['path']) && strpos($referer['path'], 'site/password') !== false) {
            $authData = Yii::$app->anon->identity ? Yii::$app->anon->identity->getAuthData() : [];
            if ($authData['voteID'] === $voteID) {
                return $this->redirect(['start-vote', 'voteID' => $voteID]);
            }
        }

        // 是否略過投票資訊頁面
        if ($voteInfo->skipDetail) {
            // 驗證時間
            if ($voteInfo->active == Votes::STATUS_ACTIVE && $toDate >= strtotime($voteInfo->verifyStart) && $toDate < strtotime($voteInfo->verifyEnd)) {
                return $this->redirect(['count', 'voteID' => $voteID]);
            }
            // 等待驗證時間
            elseif ($voteInfo->active == Votes::STATUS_ACTIVE && $toDate >= strtotime($voteInfo->openEnd) && $toDate < strtotime($voteInfo->verifyStart)) {
                Yii::$app->session->addFlash('warning', '等待驗證時間，無法進行投票！');
                return $this->redirect(['index']);
            }
            // 投票時間
            else {
                return $this->redirect(['start-vote', 'voteID' => $voteID]);
            }
        }

        // 候選人配置
        $FormCandiConfig = new \app\models\FormCandiConfig;
        if (empty($questionID)) {
            $candiConfig = $FormCandiConfig->getConfigWithVoteID($voteID);
        }
        else {
            $candiConfig = $FormCandiConfig->getConfigWithVoteID($voteID, $questionID);
        }

        // 候選人資料
        $searchModel = new \app\models\FormCandiData;
        $searchModel->questionID = $questionID;
        $query = $searchModel->search($voteID);

        if(!$query->exists()) {
            $candidateList = null;
        }
        else {
            $candidateList = (new \app\models\DataProvider)->getBasicDataProvider($query);
        }
        // 問題
        $questions = $model->getVoteQuestion($voteInfo->voteID, $voteInfo->round, Yii::$app->language);

        return $this->render('vote-detail',[
            'model'     => $model,
            'voteID'    => $voteID,
            'voteInfo'  => $voteInfo,
            'isVote'  => $model->isVoteOpen($voteID),
            'candiConfig'  => $candiConfig,
            'candidateList' => $candidateList,
            'questions' => $questions,
        ]);
    }

    /**
     * 投票頁面
     */
    public function actionStartVote($voteID)
    {
        $model = new \app\models\FormManageVote($voteID);

        // 是否超過投票時間
        if ($model->voteInfo->checkVoteExpired() || $model->voteInfo->checkVoteReady()) {
            return $this->redirect($model->finishPage);
        }

        // 是否已投票
        if($model->isVoteBallot()) 
        {
            return $this->redirect(['vote-voted', 'voteID' => $model->voteID]);
        }

        // 問題
        $questions = Questions::find()
            ->where(['questions.voteID' => $model->voteID, 'questions.round' => $model->voteInfo->round])
            ->andWhere(['in', 'party', [$model->party, Questions::ALL_PARTY_CODE]])
            ->asArray()->indexBy('questionID')->all();
        // 是否建立問題
        if(empty($questions)) {
            throw new \yii\web\HttpException(400, "尚未建立問題");
        }
        // 是否建立候選人
        foreach ($questions as $id => $question) {
            if(!$model->isCandidates($model->voteID, $model->party, $id)) // 無候選人
            {
                throw new \yii\web\HttpException(400, "問題:{$question['title']} 尚未建立候選人");
            }
        }
        
        $ballotSelected = Yii::$app->session->get($voteID.'record-ballot', []);
        $recordStep = Yii::$app->session->get($voteID.'record-step', 0);
        $recordStepMax = Yii::$app->session->get($voteID.'record-step-max', 0);

        $voteInfo = $model->votesInfo;
        $this->layout = ($voteInfo->pattern == 'main') ? 'main' : 'vote/'.$voteInfo->pattern;

        return $this->render('start-vote', [
            'model' => $model,
            'candiConfig' => $model->getCandidateConfig(),  // 候選人配置
            'questions' => $questions,                      // 問題資訊
            'recordStep' => $recordStep,                    // 使用者的流程狀態
            'recordStepMax' => $recordStepMax,              // 使用者的流程狀態(最遠)
            'ballotSelected' => $ballotSelected,            // 使用者已圈選的投票
            'voteInfo' => $voteInfo                         // 投票資訊
        ]);
    }
    
    /**
     * 儲存選票
     *
     * @param  string $voteID
     * @return void
     */
    public function actionSaveVote($voteID)
    {
        $model = new \app\models\FormManageVote($voteID);
        
        $request = Yii::$app->request;
        if ($request->isPost) {
            // 是否超過投票時間
            if ($model->voteInfo->checkVoteExpired() || $model->voteInfo->checkVoteReady()) {
                return $this->redirect($model->finishPage);
            }
            elseif($model->saveVoteBallot($request->post())) {
                Yii::$app->session->remove($voteID.'record-ballot');
                Yii::$app->session->remove($voteID.'record-step');
                Yii::$app->session->remove($voteID.'record-step-max');
                return $this->redirect(['vote-finish', 'voteID' => $model->voteID]);
            }
            else {
                return $this->redirect(['start-vote', 'voteID' => $model->voteID]);
            }
        }
    }
        
    /**
     * 投票完成頁面
     *
     * @param  string $voteID
     * @return string
     */
    public function actionVoteFinish($voteID)
    {
        $model = new FormManageVote($voteID);
        $voteInfo = $model->voteInfo;
        $text = Yii::t('app', '投票完成');
        $this->layout = 'vote/vote-finish';
        return $this->render('vote-finish', compact('model', 'voteInfo', 'text'));
    }

    /**
     * 此密碼已完成投票頁面
     *
     * @param  string $voteID
     * @return string
     */
    public function actionVoteVoted($voteID)
    {
        $model = new FormManageVote($voteID);
        $voteInfo = $model->voteInfo;
        $text = Yii::t('app', '此密碼已完成投票');
        $this->layout = 'vote/vote-finish';
        return $this->render('vote-finish', compact('model', 'voteInfo', 'text'));
    }

    /**
     * 紀錄使用者投票時的圈選項目
     */
    public function actionRecordBallot($voteID, $id, $check)
    {
        if($check == 'add')
        {
            $recordBallot = Yii::$app->session->get($voteID.'record-ballot', []);
            $key = array_search($id, $recordBallot);
            if($key === false)
            {
                $recordBallot[] = $id;
                Yii::$app->session->set($voteID.'record-ballot', $recordBallot);
            }
        }
        if($check == 'rem')
        {
            $recordBallot = Yii::$app->session->get($voteID.'record-ballot', []);
            $key = array_search($id, $recordBallot);
            if($key !== false)
            {
                unset($recordBallot[$key]);
                Yii::$app->session->set($voteID.'record-ballot', $recordBallot);
            }
        }
        return $this->asJson(Yii::$app->session->get($voteID.'record-ballot', []));
    }

    /**
     * 紀錄使用者投票時的流程狀態
     */
    public function actionRecordStep($voteID, $step)
    {
        Yii::$app->session->set($voteID.'record-step', $step);
        $recordStepMax = Yii::$app->session->get($voteID.'record-step-max', 0);
        if($recordStepMax < $step)
        {
            Yii::$app->session->set($voteID.'record-step-max', $step);
        }
        return $this->asJson(Yii::$app->session->get($voteID.'record-step', 0));
    }

    /**
     * 已完成投票頁面
     */
    public function actionCheckBallot($voteID)
    {
        $model = new \app\models\FormManageVote($voteID);

        // 是否建立候選名單及配置
        $questions = $model->getVotePartyQuestion($model->voteID, $model->round, $model->party, Yii::$app->language);
        foreach ($questions as $id => $question) {
            if(!$model->isCandidates($model->voteID, $model->party, $id)) // 無候選名單
            {
                throw new \yii\web\HttpException(400, "問題:{$question} 尚未建立候選名單");
            }
            $candiConfig = $model->getCandidateConfig($model->voteID, $id);
            if ($model->voteInfo->candiConfig == Votes::CANDI_CONFIG_BY_Q && empty($candiConfig)) {
                throw new \yii\web\HttpException(400, "尚未建立候選名單配置");
            }
        }
        if ($model->voteInfo->candiConfig != Votes::CANDI_CONFIG_BY_Q) {
            $candiConfig = $model->getCandidateConfig();
            if (empty($candiConfig)) {
                throw new \yii\web\HttpException(400, "尚未建立候選名單配置");
            }
        }

        return $this->render('check-ballot', [
            'model' => $model,
            'questions' => $questions,    // 問題
        ]);
    }

    /**
     * 投票結果頁面
     */
    public function actionResultList()
    {
        $model = new FormVotes;
        $DataProvider = new \app\models\DataProvider;
        $query = $model->getVoteList('result');
        return $this->render('result-list',[
            'model' => $model,
            'dataCount' => $query->count(),
            'dataProvider' => $DataProvider->getBasicDataProvider($query),
        ]);
    }

    /**
     * 投票結果
     */
    public function actionResult($voteID)
    {
        $model = new \app\models\FormResults;
        $configModel = new \app\models\FormResultsConfig;
        $resultConfig = $configModel->getData($voteID);
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);
        $questions = $FormVotes->getVoteQuestion($voteInfo->voteID, $voteInfo->round, Yii::$app->language);
        $parties = $FormVotes->getVoteParty($voteInfo->voteID, Yii::$app->language, $voteInfo->partyOrNot);
        return $this->render('result',[
            'model' => $model,
            'FormVotes' => $FormVotes,
            'voteInfo' => $voteInfo,
            'resultConfig' => $resultConfig,
            'questions' => $questions,
            'parties' => $parties,
            'candiConfig' => (new \app\models\FormCandiConfig)->getConfigWithVoteID($voteID),
            'dataProvider' => $model->getDataProvider($voteID, $voteInfo->round, $resultConfig),
        ]);
    }
    
    /**
     * 計票單
     *
     * @param  string $voteID
     * @return void
     */
    public function actionCount($voteID)
    {
        $manageCount = new FormManageCount($voteID);
        $ballotCountSort = $manageCount->ballotCountSort;
        
        // 取得選票資訊
        $FormManageVote = new FormManageVote($voteID);
        // 選票設定
        $FormBallots = new FormBallots;
        $ballotList = $FormBallots->getBallotList($voteID, $FormManageVote->round)->asArray()->all();
        // 不再驗證時間內顯示錯誤
        $toDate = strtotime(date('Y-m-d H:i:s')); // strtotime(date('Y-m-d H:i:s'))
        if ($toDate < strtotime($FormManageVote->votesInfo['verifyStart']) || $toDate > strtotime($FormManageVote->votesInfo['verifyEnd'])) {
            self::NotAllowedAccess();
        }
        $ballotCountAry = $FormManageVote->getBallotCount();
        // 密碼
        $passwordListByParty = (new Passwords())->getPasswordListByParty($voteID, $manageCount->voteInfo);

        return $this->render('count', [
            'model' => $manageCount,
            'ballotList' => $ballotList,
            'ballotCountSort' => $ballotCountSort,
            'ballotCountAry' => $ballotCountAry,
            'passwordList' => $passwordListByParty,    // 密碼
        ]);
    }

    /**
     * 計票單統計匯出
     *
     * @param  string $voteID
     * @return void
     */
    public function actionCountExport($voteID, $sort)
    {
        $model = new FormCountExport();
        return $model->export($voteID, $sort);
    }
    
    /**
     * 下載檔案
     *
     * @param  string $file
     * @param  string $voteID
     * @return bool
     */
    public function actionDownloadFile($file, $voteID)
    {
        // voteID 必須是存在的投票場次，避免路徑穿越
        $vote = Votes::findOne($voteID);
        if (!$vote) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', '找不到該投票場次'));
        }

        // 匿名投票且投票資訊需驗證時，附件亦須通過該場次的密碼驗證
        if ($vote->type == Votes::TYPE_ANON && $vote->authBeforeDetail == 1) {
            $authData = Yii::$app->anon->identity ? Yii::$app->anon->identity->getAuthData() : [];
            if (!isset($authData['voteID'])
                || ($authData['voteID'] != $vote->voteID && $authData['voteID'] != $vote->bindWhichVote)) {
                self::NotAllowedAccess();
            }
        }

        $path = FileHelper::normalizePath(realpath(Yii::getAlias('@filePool').'/candidateFile/'.$vote->voteID.'/'));
        if (empty($path) || !is_dir($path)) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', '找不到該檔案'));
        }

        $files = scandir($path);
        // 僅允許目錄下實際存在的一般檔案（排除 . / .. 與子目錄）
        $fileName = isset($files[$file]) ? basename($files[$file]) : null;
        if ($fileName && !in_array($fileName, ['.', '..'], true)
            && is_file($path.DIRECTORY_SEPARATOR.$fileName)) {
            $FileLoader = new FileLoader(Yii::getAlias('@filePool'));
            return $FileLoader->downloadFile($fileName, '/candidateFile/'.$vote->voteID.'/');
        }

        throw new \yii\web\NotFoundHttpException(Yii::t('app', '找不到該檔案'));
    }
    
    /**
     * 預覽圈選須知
     *
     * @param  string $voteID
     * @return string
     */
    public function actionPreviewInformation($voteID)
    {
        $request = $this->request;

        if ($request->isPost) {
            $vote = new Votes(compact('voteID'));
            $information = $vote->replaceQuestionRule($request->post('information'));
            return $this->asJson(compact('information'));
        }
    }
}
