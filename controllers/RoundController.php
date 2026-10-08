<?php

namespace app\controllers;

use Yii;
use app\models\Logs;
use app\models\Round;
use app\models\Votes;
use yii\helpers\Json;
use app\models\FormVotes;
use app\models\RoundSearch;

class RoundController extends \app\components\Controller
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
                        'permissions' => ['voteInfo'],
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
     * 輪次管理頁面
     *
     * @param  string $voteID
     * @return Response
     */
    public function actionIndex($voteID)
    {
        $voteInfo = (new FormVotes())->getVoteInfo($voteID);
        $check = Round::find()->where(['voteID' => $voteID])->exists();

        if (!$check) {
            (new Round())->createRound($voteID, 1, '第1次投票');
            return $this->refresh();
        }

        $query = (new RoundSearch())->search($voteID, Yii::$app->request->queryParams);
        $dataProvider = (new \app\models\DataProvider)->getBasicDataProvider($query);

        return $this->render('index', compact('voteID', 'voteInfo', 'dataProvider'));
    }
    
    /**
     * 設定新一輪投票
     *
     * @param  string $voteID
     * @return void
     */
    public function actionCreate($voteID)
    {
        $model = new Round();
        $maxRound = $model->getMaxRound($voteID);
        /**
         * 驗證最新一輪是否有投票
         */
        if (!$model->checkMaxRoundVoted($voteID)) {
            Yii::$app->session->setFlash('warning', '第'.$maxRound.'輪尚未投票');
            Logs::add(Logs::VOTE_ROUND_CREATE_FAIL, Json::encode(compact('voteID')+['message' => '第'.$maxRound.'輪尚未投票'], 336));
            return $this->redirect(Yii::$app->request->referrer); 
        }
        else {
            $round = $maxRound + 1;

            $db = Yii::$app->db;
            $transaction = $db->beginTransaction();

            try {
                // 輪次
                $model->createRound($voteID, $round, '第'.$round.'次投票');
                
                $transaction->commit();
                Yii::$app->session->addFlash('success', '建立成功');
                Logs::add(Logs::VOTE_ROUND_CREATE, Json::encode(compact('voteID', 'round'), 336));
                return $this->redirect(['update', 'voteID' => $voteID, 'round' => $round]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error(
                    \yii\helpers\VarDumper::dumpAsString(
                        $e->getMessage(), $depth=10, $highlight=false
                    ),
                    __METHOD__
                );
                return $this->redirect(Yii::$app->request->referrer); 
            }
        }
    }

    /**
     * 編輯輪次
     *
     * @param  string $voteID
     * @param  mixed $round
     * @return void
     */
    public function actionUpdate($voteID, $round)
    {
        $voteInfo = (new FormVotes())->getVoteInfo($voteID);
        $model = Round::find()->where(['voteID' => $voteID, 'round' => $round])->one();

        $request = Yii::$app->request;
        if ($request->isPost) {
            $model->load($request->post());
            if (!$model->save()) {
                foreach($model->errors as $message)
                {
                    Yii::$app->session->addFlash('error', $message[0]);
                    Logs::add(Logs::VOTE_ROUND_EDIT_FAIL, Json::encode(compact('voteID', 'round')+[$message[0]], 336));
                }
            }
            else {
                Yii::$app->session->addFlash('success', '更新成功');
                Logs::add(Logs::VOTE_ROUND_EDIT, Json::encode(compact('voteID', 'round'), 336));
                return $this->refresh();
            }
        }

        return $this->render('update', compact('voteInfo', 'model'));
    }
    
    /**
     * 切換輪次
     *
     * @param  string $voteID
     * @param  mixed $round
     * @return void
     */
    public function actionSwitch($voteID, $round)
    {
        $model = Round::find()->where(['voteID' => $voteID, 'round' => $round])->one();

        if (is_null($model)) {
            Yii::$app->session->addFlash('error', '輪次不存在！');
            return $this->redirect(['index', 'voteID' => $voteID]);
        }

        $vote = Votes::find()->where(['voteID' => $voteID])->one();
        $vote->round = $round;
        $vote->save(false);
        Logs::add(Logs::VOTE_ROUND_SWITCH, Json::encode(compact('voteID', 'round'), 336));
        Yii::$app->session->addFlash('success', '切換成功');

        return $this->redirect(['index',
            'voteID' => $voteID,
            'model' => $model,
        ]);
    }
}
