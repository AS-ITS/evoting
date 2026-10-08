<?php

namespace app\controllers;

use Yii;
use app\models\Parties;
use app\models\FormVotes;
use app\models\Questions;
use app\models\Round;

class QuestionController extends \app\components\Controller
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
                        // 不設定actions表示全部
                        'allow' => true,
                        'permissions' => ['voteQuestion'],
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
     * 問題管理頁面
     *
     * @param  string $voteID
     * @return Response
     */
    public function actionIndex($voteID)
    {
        $questions = new Questions();
        $voteInfo = (new FormVotes())->getVoteInfo($voteID);
        $query = $questions->search($voteID, $voteInfo->round, Yii::$app->request->queryParams);
        $parties = (new FormVotes())->getVoteParty($voteID, Yii::$app->language, $voteInfo->partyOrNot);
        $dataProvider = (new \app\models\DataProvider)->getBasicDataProvider($query);

        return $this->render('index', compact('questions', 'dataProvider', 'parties', 'voteID', 'voteInfo'));
    }
    
    /**
     * 新增問題頁面
     *
     * @param  string $voteID
     * @return string
     */
    public function actionCreate($voteID)
    {
        $questions = new Questions();
        $voteInfo = (new FormVotes())->getVoteInfo($voteID);
        $parties = (new FormVotes())->getVoteParty($voteID, Yii::$app->language, $voteInfo->partyOrNot);
        return $this->render('create', compact('questions', 'parties', 'voteID', 'voteInfo'));
    }
    
    /**
     * 儲存問題
     *
     * @param  string $voteID
     * @return Response
     */
    public function actionStore($voteID)
    {
        $model = new Questions();
        $request = Yii::$app->request;
        if ($request->isPost) {
            $create = $model->createQuestions($voteID, $request->post(), false);

            if ($create) {
                Yii::$app->session->addFlash('success', '問題新增完成！');
                return $this->redirect(['question/index', 'voteID' => $voteID]);
            } else {
                Yii::$app->session->addFlash('error', '問題新增失敗！');
                return $this->redirect($request->referrer);
            }
        }
    }
    
    /**
     * 問題編輯頁面
     *
     * @param  string $voteID
     * @param  string $party
     * @param  int $questionID
     * @return string
     */
    public function actionUpdate($voteID, $questionID)
    {
        $question = Questions::findOne(['questionID' => $questionID]);
        $voteInfo = (new FormVotes())->getVoteInfo($voteID);
        if ($question) {
            $parties = (new FormVotes())->getVoteParty($voteID, Yii::$app->language, $voteInfo->partyOrNot);
            return $this->render('update', compact('question', 'voteID', 'parties', 'voteInfo'));
        }

        Yii::$app->session->addFlash('error', '問題不存在!');
        return $this->redirect(['question/index', compact('voteID')]);
    }
    
    /**
     * 問題更新
     *
     * @param  string $voteID
     * @param  int $questionID
     * @return Response
     */
    public function actionEdit($voteID, $questionID)
    {
        $model = new Questions();
        $request = Yii::$app->request;
        if ($request->isPost) {

            $update = $model->updateQuestion($questionID, $request->post());

            if ($update) {
                Yii::$app->session->addFlash('success', '問題修改成功！');
            } else {
                Yii::$app->session->addFlash('error', '問題修改失敗！');
            }

            return $this->redirect($request->referrer);
        }
    }
    
    /**
     * 刪除問題
     *
     * @param  string $voteID
     * @param  int $questionID
     * @return Response
     */
    public function actionDelete($voteID, $questionID)
    {
        $model = new Questions();
        $request = Yii::$app->request;

        if ($request->isPost) {
            
            $delete = $model->deleteQuestion($questionID);

            if ($delete) {
                Yii::$app->session->addFlash('success', '問題刪除成功！');
            } else {
                Yii::$app->session->addFlash('error', '問題刪除失敗！');
            }

            return $this->redirect($request->referrer);
        }
    }
    
    /**
     * 匯入輪次問題
     *
     * @param  string $voteID
     * @param  mixed $round
     * @return void
     */
    public function actionRoundImport($voteID, $round=1)
    {
        $maxRound = (new Round)->getMaxRound($voteID);
        $rounds = Round::find()->select(['voteID', 'round', 'name', 'nameE'])
            ->where(['voteID' => $voteID])->andWhere(['<', 'round', $maxRound])
            ->asArray()->all();
        if ($maxRound <= 1) {
            Yii::$app->session->setFlash('error', '請先新增輪次');
            return $this->redirect(['question/index', 'voteID' => $voteID]);
        }
        $questions = (new Questions())->getRoundQuestions($voteID, $round)->all();
        $voteInfo = (new FormVotes())->getVoteInfo($voteID);
        $parties = (new FormVotes())->getVoteParty($voteID, Yii::$app->language, $voteInfo->partyOrNot);

        $request = Yii::$app->request;
        $model = new Questions();
        if ($request->isPost) {
            $import = $model->importQuestions($voteID, $request->post());

            if ($import) {
                Yii::$app->session->addFlash('success', '問題匯入完成！');
                return $this->redirect(['question/index', 'voteID' => $voteID]);
            } else {
                Yii::$app->session->addFlash('error', '問題匯入失敗！');
                return $this->redirect($request->referrer);
            }
            return $this->refresh();
        }
        
        return $this->render('round-import', compact('questions', 'parties', 'voteID', 'voteInfo', 'rounds', 'round'));
    }
    
    /**
     * ajax取得分組資訊
     *
     * @param  string $voteID
     * @param  string $party
     * @return string
     */
    public function actionGetPartyInfo($voteID, $party)
    {
        if (Yii::$app->request->isAjax) {
            $Parties = new Parties;
            $party = $Parties->getPartyInfo($voteID, $party)->asArray()->one();
            echo json_encode($party);
        }
    }
    
    /**
     * ajax取得分組問題
     *
     * @param  string $voteID
     * @param  string $round
     * @return void
     */
    public function actionGetPartyQuestions($voteID, $round)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            $questions = Questions::find()
                ->where(['voteID' => $voteID, 'party' => $request->post('party'), 'round' => $round])
                ->asArray()->all();
            echo json_encode($questions);
        }
    }
}
