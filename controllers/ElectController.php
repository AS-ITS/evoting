<?php
namespace app\controllers;

use Yii;
use app\models\Logs;

use app\models\Round;
use yii\helpers\Json;
use app\models\FormGroup;
use app\models\FormVotes;
use app\models\Questions;
use yii\web\UploadedFile;
use app\models\FormBallots;
use app\models\FormParties;
use app\models\FormResults;
use yii\helpers\FileHelper;
use app\models\FormUploadFile;
use app\models\FormResultsConfig;
use app\components\helper\ArrayHelper;

/**
 * 投票新修刪、基本資料設定、重啟投票
 */
class ElectController extends \app\components\Controller
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
                        'allow' => true,
                        'actions' => ['index'],// 投票管理
                        'permissions' => ['voteManag'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create-vote', 'upload-file'],// 建立投票
                        'permissions' => ['voteCreate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['edit-vote', 'delete-vote', 'process', 'upload-file', 'delete-file'],// 編輯投票
                        'permissions' => ['voteInfo'],
                        'roleParams' => ['voteID' => Yii::$app->request->get('voteID')],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['reset-vote'],// 重啟投票
                        'permissions' => ['voteReset'],
                        'roleParams' => ['voteID' => Yii::$app->request->get('voteID')],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['renew-short-url'],// 更新短網址
                        'roles' => ['sa'],
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
     * 投票管理
     */
    public function actionIndex()
    {
        $model = new FormVotes;
        $model->setScenario('search');

        $query = $model->getVoteList('all', Yii::$app->request->queryParams);

        $sysidList = $model->getCnByVote($query);
        return $this->render('index',[
            'model' => $model,
            'dataProvider' => (new \app\models\DataProvider)->getBasicDataProvider($query),
            'sysidList' => $sysidList,
        ]);
    }

    /**
     * 投票詳細資料
     */
    public function actionEditVote($voteID)
    {
        $request = Yii::$app->request;

        // 基本資料
        $FormVotes = new FormVotes;
        // 投票組別
        $FormParties = new FormParties;
        // 群組
        $FormGroup = new FormGroup();
        $groups = $FormGroup->getGroups(Yii::$app->user->can('sa') ? 'all' : 'member');
        // 檔案上傳
        $FormUploadFile = new FormUploadFile();

        $voteInfo = $FormVotes->getVoteInfo($voteID);
        if(is_null($voteInfo))
        {
            Yii::$app->session->setFlash('warning', '投票編號不存在！');
            return $this->redirect(['/elect/index']);
        }

        if($request->isPost)// 提交表單
        {
            $postData = $request->post();
            $action = $postData['action'] ?? 'save';
            switch($action)
            {
                case 'saveAs': // 另存資料
                    // 取得新的 voteID
                    $voteID = $FormVotes->genVoteId();
                    // 投票基本資料
                    $statusVoteInfo = $FormVotes->updateVoteInfo($voteID, $postData, true);
                    // 投票組別
                    $statusParty = $FormParties->updateParty($voteID, $postData, true);

                    // 輪次
                    $statusRound = (new Round())->createRound($voteID, 1, '第1次投票');

                    if($statusVoteInfo === true && $statusParty === true && $statusRound === true)
                    {
                        Logs::add(Logs::VOTE_SAVE_AS, $voteInfo['voteID'].' save as '.$voteID);
                        Yii::$app->session->setFlash('success', '投票另存完成！');
                    }
                    else {
                        $FormVotes->deleteVote($voteID);
                    }
                    break;

                default:
                case 'save': // 更新資料
                    // 投票基本資料
                    $statusVoteInfo = $FormVotes->updateVoteInfo($voteID, $postData, false);
                    // 投票組別
                    $statusParty = $FormParties->updateParty($voteID, $postData, false);

                    if($statusVoteInfo === true && $statusParty === true)
                    {
                        Yii::$app->session->setFlash('success', '投票修改完成！');
                    }
                    break;
            }
            return $this->redirect(['edit-vote','voteID'=>$voteID]);
        }
        
        $partyData = $FormParties->getPartyAll($voteID);

        $questions = Questions::find()->where(['voteID' => $voteID])->indexBy('questionID')->asArray()->all();
        return $this->render('edit-vote',[
            'action'    => 'edit',
            'FormVotes' => $FormVotes,
            'voteInfo'  => $voteInfo,
            'canBindVoteAry'  => (function() use ($FormVotes, $voteID) {
                $ary = $FormVotes->getCanBindVoteAry();
                ArrayHelper::forget($ary, $voteID);
                return $ary;
            })(),
            'VoteCreatorAry'  => $FormVotes->getVoteCreatorAry($voteInfo->creator),
            // 投票組別
            'FormParties'=> $FormParties,
            'partyData'  => $partyData,
            // 問題
            'questions'  => $questions,
            // 群組
            'groups' => $groups,
            // 檔案上傳
            'FormUploadFile' => $FormUploadFile,
        ]);
    }

    /**
     * 投票詳細資料
     */
    public function actionCreateVote()
    {
        $request = Yii::$app->request;

        // 基本資料
        $FormVotes = new FormVotes;
        // 投票組別
        $FormParties = new FormParties;
        // 群組
        $FormGroup = new FormGroup();
        $groups = $FormGroup->getGroups(Yii::$app->user->can('sa') ? 'all' : 'member');
        // 問題
        $Questions = new Questions();
        // 輪次
        $round = new Round();

        if($request->isPost)// 提交表單
        {
            $postData = $request->post();
            $voteID = $FormVotes->genVoteId();
            
            // 投票基本資料
            $statusVoteInfo = $FormVotes->updateVoteInfo($voteID, $postData, true);
            // 投票組別
            $statusParty = $FormParties->updateParty($voteID, $postData, true);
            // 輪次
            $statusRound = $round->createRound($voteID, 1, '第1次投票');

            if($statusVoteInfo === true && $statusParty === true && $statusRound === true)
            {
                Yii::$app->session->setFlash('success', '投票創建完成！');
                Logs::add(Logs::VOTE_CREATE, Json::encode(compact('voteID'), 336));
                return $this->redirect(['edit-vote','voteID'=>$voteID]);
            }
            else {
                $FormVotes->deleteVote($voteID);
            }
        }

        return $this->render('edit-vote',[
            'action'    => 'create',
            'FormVotes' => $FormVotes,
            'voteInfo'  => $FormVotes->createVote(),
            'canBindVoteAry'  => $FormVotes->getCanBindVoteAry(),
            'VoteCreatorAry'  => $FormVotes->getVoteCreatorAry(),
            // 投票組別
            'FormParties'=> $FormParties,
            // 群組
            'groups' => $groups
        ]);
    }

    /**
     * 刪除投票
     */
    public function actionDeleteVote($voteID)
    {
        // 基本資料
        $ManageVote = new \app\models\FormManageVote($voteID);
        $status = $ManageVote->deleteVote();
        Logs::add(Logs::VOTE_DELETE, Json::encode(compact('voteID')));
        return $this->redirect(['index']);
    }

    /**
     * 流程
     */
    public function actionProcess($voteID)
    {
        // 基本資料
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);
        
        return $this->render('process',[
            'FormVotes' => $FormVotes,
            'voteInfo'  => $voteInfo,
        ]);
    }

    /**
     * 重啟投票
     */
    public function actionResetVote($voteID = null)
    {
        $request = Yii::$app->request;
        $voteInfo = (new FormVotes)->getVoteInfo($voteID);

        if ($request->isPost) {
            // 刪除選票(含人、票的資訊)
            $FormBallots = new FormBallots;
            $statusBallots = $FormBallots->deleteAllBallot($voteID, $voteInfo->round);
            $countBallots = $FormBallots->getBallotList($voteID, $voteInfo->round)->count();

            // 刪除投票結果
            $FormResults = new FormResults;
            $statusResults = $FormResults->deleteResultsAll($voteID, $voteInfo->round);
            $countResults = $FormResults->isResults($voteID, $voteInfo->round);

            // 刪除投票結果(配置)
            $FormResultsConfig = new FormResultsConfig;
            $statusResultsConfig = $FormResultsConfig->deleteResultsConfigAll($voteID);
            $countResultsConfig = FormResultsConfig::find()->where(['voteID' => $voteID])->count();

            if ($countBallots == 0 && !$countResults && $countResultsConfig == 0) {
                Logs::add(Logs::VOTE_RESET, Json::encode(compact('voteID')));
                Yii::$app->session->setFlash('success', '投票重啟成功！');
            }
            else {
                Logs::add(Logs::VOTE_RESET_FAIL, Json::encode(compact('voteID')));
                Yii::$app->session->setFlash('error', '投票重啟失敗！');
            }
            return $this->redirect(['reset-vote', 'voteID' => $voteID]);
        }
        
        return $this->render('reset-vote', compact('voteInfo'));
    }
    
    /**
     * 檔案上傳
     *
     * @param  string $voteID
     * @return Response
     */
    public function actionUploadFile($voteID)
    {
        $request = Yii::$app->request;

        $FormUploadFile = new FormUploadFile();

        $path = FileHelper::normalizePath(realpath(Yii::getAlias('@filePool').'/candidateFile/'.$voteID));
        $files = !empty($path) ? FileHelper::findFiles($path) : [];

        if ($request->isPost) {
            $FormUploadFile->files = UploadedFile::getInstances($FormUploadFile, 'files');
            // 限制檔案總數量
            if ((count($files) + count($FormUploadFile->files)) > FormUploadFile::MAX_UPLOAD_COUNT) {
                Logs::add(Logs::VOTE_FILE_UPLOAD_FAIL, $voteID." 檔案上傳失敗，數量達上限");
                Yii::$app->session->addFlash('error', "檔案上傳失敗，檔案總數量上限為".FormUploadFile::MAX_UPLOAD_COUNT."個");
            }
            elseif ($FormUploadFile->upload($voteID)) {
                Yii::$app->session->addFlash('success', '檔案上傳成功！');
            }
            else {
                Yii::$app->session->addFlash('error', '檔案上傳失敗！');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    
    /**
     * 刪除指定檔案
     *
     * @param  string $file
     * @param  string $voteID
     * @return Response
     */
    public function actionDeleteFile($file, $voteID)
    {
        if (!\app\models\Votes::findOne(['voteID' => $voteID])) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', '找不到該投票場次'));
        }

        $baseDir = realpath(Yii::getAlias('@filePool') . '/candidateFile/' . $voteID . '/');
        if ($baseDir === false || !is_dir($baseDir)) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', '找不到該檔案'));
        }

        $fileName = basename((string) $file);
        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            throw new \yii\web\BadRequestHttpException(Yii::t('app', '無效的檔案名稱'));
        }

        $targetPath = realpath($baseDir . DIRECTORY_SEPARATOR . $fileName);
        if ($targetPath === false || !is_file($targetPath)
            || strpos($targetPath, $baseDir . DIRECTORY_SEPARATOR) !== 0) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', '找不到該檔案'));
        }

        if (FileHelper::unlink($targetPath)) {
            Logs::add(Logs::VOTE_FILE_DELETE, Json::encode(compact('fileName', 'voteID'), 336));
            Yii::$app->session->addFlash('success', $fileName . '檔案刪除成功！');
        } else {
            Logs::add(Logs::VOTE_FILE_DELETE_FAIL, Json::encode(compact('fileName', 'voteID'), 336));
            Yii::$app->session->addFlash('error', $fileName . '檔案刪除失敗！');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    /**
     * 重製短網址
     *
     * @return Response
     */
    public function actionRenewShortUrl()
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            // 投票基本資料
            $FormVotes = new FormVotes;
            $voteID = $request->post('voteID');
            $voteInfo = FormVotes::findOne($voteID);
            // 取得短網址
            $shortUrl = $FormVotes->genShortUrl();
            // 更新投票資訊
            $oldShortUrl = $voteInfo->getOldAttribute('shortUrl');
            $voteInfo->shortUrl = $shortUrl;
            if ($voteInfo->save()) {
                Logs::add(Logs::VOTE_SHORT_URL_EDIT, Json::encode(compact('voteID', 'shortUrl', 'oldShortUrl'), 336));
                return $this->asJson(['success' => true, 'voteID' => $voteID, 'shortUrl' => $shortUrl, 'oldShortUrl' => $oldShortUrl]);
            }
            $message = $voteInfo->errors;
            Logs::add(Logs::VOTE_SHORT_URL_EDIT_FAIL, Json::encode(compact('voteID', 'message'), 336));
            return $this->asJson(['success' => false, 'voteID' => $voteID, 'message' => $message]);
        }
    }
}
