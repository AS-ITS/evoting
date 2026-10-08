<?php
namespace app\controllers;

use Yii;

use app\models\FormGroup;
use app\models\FormVotes;

use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\components\Controller;

class GroupController extends Controller
{
    /**
     * 行為
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index'],// 群組管理
                        'permissions' => ['groupView'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create'],// 群組建立
                        'permissions' => ['groupCreate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['view-base'],// 群組基本資料
                        'permissions' => ['groupViewBase'],
                        'roleParams' => ['groupId' => Yii::$app->request->get('groupId')]
                    ],
                    [
                        'allow' => true,
                        'actions' => ['edit-base'],// 編輯群組基本資料
                        'permissions' => ['groupEditBase'],
                        'roleParams' => ['groupId' => Yii::$app->request->get('groupId')]
                    ],
                    [
                        'allow' => true,
                        'actions' => ['view-vote'],// 群組投票管理
                        // 'permissions' => ['groupVoteManage'],
                        // 'roleParams' => ['groupId' => Yii::$app->request->get('groupId')]
                    ],
                    [
                        'allow' => false,
                        'roles' => ['?', '@'],// 已登入
                    ],
                ],
                'denyCallback' => self::denyCallback()
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'https' => ['post','get'],
                ],
            ],
        ];
    }

    /**
     * 於首頁"開放中之投票項目"
     */
    public function actionIndex()
    {
        $model = new FormGroup;

        $type = Yii::$app->user->can('groupManag') ? 'all' : 'member';
        
        return $this->render('index',[
            'model' => $model,
            'dataProvider' => $model->getGroupList($type),
        ]);
    }

    /**
     * 處理建立群組
     */
    public function actionCreate()
    {
        $model = new FormGroup;

        $request = Yii::$app->request;

        if($request->isPost)// 提交表單
        {
            $create = $model->createBase($request->post());

            if ($create) {
                Yii::$app->session->addFlash('success', '群組建立完成！');
            } else {
                Yii::$app->session->addFlash('error', '群組建立失敗！');
            }
            
            return $this->redirect($request->referrer);
        }
    }

    /**
     * 群組基本資料
     */
    public function actionViewBase($groupId)
    {
        $model = new FormGroup;
        $group = $model->getGroupInfo($groupId)->query->one();
        if (is_null($group))
            throw new \yii\web\NotFoundHttpException('找不到群組！');

        return $this->render('view-base',[
            'model' => $group,
        ]);
    }

    /**
     * 處理編輯群組基本資料
     */
    public function actionEditBase($groupId)
    {
        $model = new FormGroup;

        $request = Yii::$app->request;

        if($request->isPost)// 提交表單
        {
            $update = $model->updateBase($groupId, $request->post());

            if ($update) {
                Yii::$app->session->addFlash('success', '群組基本資料編輯完成！');
            } 
            else {
                Yii::$app->session->addFlash('error', '群組基本資料編輯失敗！');
            }
            
            return $this->redirect($request->referrer);
        }
    }

    /**
     * 群組投票管理
     */
    public function actionViewVote()
    {
        $model = new FormVotes;
        $model->setScenario('search');
        $query = $model->getVoteList('group', Yii::$app->request->queryParams);
        $sysidList = $model->getCnByVote($query);
        return $this->render('view-vote',[
            'model' => $model,
            'dataProvider' => (new \app\models\DataProvider)->getBasicDataProvider($query),
            'sysidList' => $sysidList,
        ]);
    }
}
