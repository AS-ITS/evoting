<?php
namespace app\controllers;

use Yii;
use app\models\Votes;

use app\models\FormVotes;
use app\models\FormResults;
use app\components\helper\ArrayHelper;
use app\models\FormCandiConfig;
use app\models\FormResultsConfig;
use app\models\Results;

/**
 * 開計票、處理投票結果
 */
class ResultController extends \app\components\Controller
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
                        'matchCallback' => function ($rule, $action) use (&$voteID,&$candiConfig)
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
                            return false;
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
                        'permissions' => ['voteResult'],
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

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'edit' => [                                       // identifier for your editable column action
                'class' => \kartik\grid\EditableColumnAction::className(),     // action class name
                'modelClass' => FormResults::className(),            // the model for the record being edited
                'findModel' => function($id, $action) {
                    $model = FormResults::find()->where(['candID' => $id])->one();
                    if (is_null($model)) {
                        throw new \yii\web\NotFoundHttpException('找不到候選人資料！');
                    }
                    $model->scenario = 'update';
                    return $model;
                },
                'outputValue' => function ($model, $attribute, $key, $index) {
                    if($attribute == 'elected')
                        return Yii::$app->params['ct.result.electedAry'][$model->elected];
                    return $model->$attribute;
                },
                'outputMessage' => function($model, $attribute, $key, $index) {
                    return '';                                  // any custom error to return after model save
                },
                'showModelErrors' => true,                        // show model validation errors after save
                'errorOptions' => ['header' => ''],               // error summary HTML options
                // 'postOnly' => true,
                // 'ajaxOnly' => true,
                // 'checkAccess' => function($action, $model) {}
            ]
        ]);
    }

    /**
     * 投票結果
     */
    public function actionIndex($voteID, $type=null)
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
            $view = 'index';
            $party = false;
            $status = false;
        }

        return $this->render($view,[
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
     * 批次操作當選狀態
     *
     * @param  string $voteID
     * @return void
     */
    public function actionMultiEdit($voteID)
    {
        $request = $this->request;
        if ($request->isPost) {
            $candis = $request->post('ids');
            $action = $request->post('action');
            try {
                Yii::$app->db->createCommand()
                    ->update(Results::tableName(), ['elected' => $action], ['voteID' => $voteID, 'candID' => $candis])
                    ->execute();
                return $this->asJson(['success' => true]);
            } catch (\Exception $e) {
                return $this->asJson(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }
}
