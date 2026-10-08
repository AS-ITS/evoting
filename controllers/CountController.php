<?php
namespace app\controllers;

use Yii;
use app\models\Logs;
use app\models\Round;
use app\models\Votes;
use yii\helpers\Json;
use app\models\FormVotes;
use app\models\Passwords;
use app\models\Questions;
use app\models\CandiConfig;
use app\models\FormBallots;
use app\models\FormManageVote;
use app\models\FormCandiConfig;
use app\models\FormCountExport;
use app\models\FormManageCount;
use app\components\helper\ArrayHelper;

/**
 * 開計票、處理投票結果
 */
class CountController extends \app\components\Controller
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
                        'allow' => false,
                        'matchCallback' => function ($rule, $action) use (&$voteID, &$candiConfig)
                        {
                            $voteID = Yii::$app->getRequest()->get('voteID');
                            $voteInfo = Votes::findOne($voteID);
                            if (!$voteInfo) {
                                return false;
                            }
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
                        'denyCallback' => function ($rule, $action) use (&$voteID)
                        {
                            if (!Yii::$app->user->isGuest)
                            {
                                Yii::$app->session->setFlash('warning', '請先建立候選名單配置！');
                                return $this->redirect(['candi/config','voteID'=>$voteID]);
                            }
                            $action->controller->NotAllowedAccess();
                        }
                    ],
                    [
                        // 不設定actions表示全部
                        'allow' => true,
                        'permissions' => ['voteCount'],
                        'roleParams' => ['voteID' => Yii::$app->request->get('voteID')],
                    ],
                ],
                'denyCallback' => self::denyCallback()
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'https' => ['post', 'get'],
                ],
            ],
        ];
    }

    /**
     * 計票單
     * TODO: 新增問題
     */
    public function actionIndex($voteID, $questionID=null)
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
        
        return $this->render('index',[
            'model' => $manageCount,
            'ballotList' => $ballotList,
            'ballotCountSort' => $ballotCountSort,
            'ballotCountAry' => $ballotCountAry,
            'passwordList' => $passwordListByParty,    // 密碼
            'showExportModal' => true // 組別得票匯出
        ]);
    }

    /**
     * 開票設定
     */
    public function actionResult($voteID)
    {
        $model = new \app\models\FormResultsConfig;
        $resultData = $model->getData($voteID);
        $isResultData = is_null($resultData);

        // 投票資訊
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);

        $request = Yii::$app->request;
        if($request->isPost)// 提交表單
        {
            $postData = $request->post();
            $action = $postData['action'] ?? 'save';
            switch($action)
            {
                case 'save': // 更新資料
                    $updateModel = $isResultData ? $model : $resultData;
                    $update = $model->updateConfig($updateModel, $postData, $isResultData);
                    if($update && $isResultData) {
                        Yii::$app->session->setFlash('success', '開票設定新增完成！');
                    }
                    elseif ($update && !$isResultData) {
                        Yii::$app->session->setFlash('success', '開票設定修改成功！');
                    }
                    else {
                        Yii::$app->session->setFlash('error', '開票設定失敗！');
                    }
                    return $this->redirect(['count/result', 'voteID' => $voteID]);
                    break;
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
                        if (!$model->isResults($voteID, $voteInfo->round)) {
                            Yii::$app->session->setFlash('warning', '所有選票皆為廢票，無意義的開票！');
                        }
                        else {
                            Yii::$app->session->setFlash('success', '開票成功！');
                        }
                    }
                    else
                    {
                        Logs::add(Logs::VOTE_RESULT_MAKE_FAIL, Json::encode(compact('voteID')));
                        Yii::$app->session->setFlash('error', '開票失敗！');
                    }
                    return $this->redirect(['count/result','voteID'=>$voteID]);
                    break;
                case 'remake': // 重新開票
                    $model = new \app\models\FormResults;
                    if($model->isResults($voteID, $voteInfo->round))
                    {
                        $model->deleteResults($voteID, $voteInfo->round);
                    }
                    if($model->createResults($voteID))
                    {
                        Logs::add(Logs::VOTE_RESULT_REMAKE, Json::encode(compact('voteID')));
                        if (!$model->isResults($voteID, $voteInfo->round)) {
                            Yii::$app->session->setFlash('warning', '所有選票皆為廢票，無意義的開票！');
                        }
                        else {
                            Yii::$app->session->setFlash('success', '重新開票成功！');
                        }
                    }
                    else
                    {
                        Logs::add(Logs::VOTE_RESULT_REMAKE_FAIL, Json::encode(compact('voteID')));
                        Yii::$app->session->setFlash('error', '重新開票失敗！');
                    }
                    return $this->redirect(['count/result','voteID'=>$voteID]);
                    break;
                case 'delete': // 刪除開票資料
                    $model = new \app\models\FormResults;
                    if($model->isResults($voteID, $voteInfo->round) && $model->deleteResults($voteID, $voteInfo->round))
                    {
                        Logs::add(Logs::VOTE_RESULT_DELETE, Json::encode(compact('voteID')));
                        Yii::$app->session->setFlash('success', '開票資料刪除成功！');
                    }
                    else
                    {
                        Logs::add(Logs::VOTE_RESULT_DELETE_FAIL, Json::encode(compact('voteID')));
                        Yii::$app->session->setFlash('error', '開票資料刪除失敗！');
                    }
                    return $this->redirect(['count/result','voteID'=>$voteID]);
                    break;
            }
        }

        // 判斷資料是否存在
        if($isResultData)
        {
            $action = 'create';
            $model->voteID = $voteID;
            $model->isLogin = '1';// 是否需登入
            $model->isParty = '1';// 是否分組看投票
            $model->sort = FormManageCount::COUNT_BY_BALLOTS;// 排序方式: 得票數
            $model->showFieldSort = $model::$defFieldSort;
        }
        else
        {
            $action = 'update';
        }

        // 候選名單配置
        $FormCandiConfig = new FormCandiConfig;
        $candiConfig = $FormCandiConfig->getConfigWithVoteID($voteID);

        return $this->render('result',[
            'model' => is_null($resultData) ? $model : $resultData,
            'isConfig' => !is_null($resultData),
            'showFieldSort' => $model::$defFieldSort,
            'candiConfig' => $candiConfig,
            'voteInfo' => $voteInfo,
            'action' => $action,
            'isResults' => (new \app\models\FormResults)->isResults($voteID, $voteInfo->round),
        ]);
    }
    
    /**
     * 計票單統計匯出
     *
     * @param  string $voteID 
     * @param  string $sort 排序方式
     * @return void
     */
    public function actionExport($voteID, $sort)
    {
        if (!isset(Yii::$app->params['ct.result.sortAry'][$sort])) {
            throw new \yii\web\BadRequestHttpException('無效的排序方式');
        }
        $model = new FormCountExport();
        return $model->export($voteID, $sort);
    }
}
