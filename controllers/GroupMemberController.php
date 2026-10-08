<?php
namespace app\controllers;

use Yii;

use app\models\Users;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use app\components\Controller;
use yii\filters\AccessControl;
use app\models\FormGroupMember;

class GroupMemberController extends Controller
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
                        'actions' => ['index'],// 群組成員列表
                        'permissions' => ['groupViewMember'],
                        'roleParams' => ['groupId' => Yii::$app->request->get('groupId')]
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create', 'clear-search'], // 新增群組成員
                        'permissions' => ['groupCreateMember'],
                        'roleParams' => ['groupId' => Yii::$app->request->get('groupId')]
                    ],
                    [
                        'allow' => true,
                        'actions' => ['store'], // 儲存新增群組成員資料
                        'permissions' => ['groupCreateMember'],
                        'roleParams' => ['groupId' => Yii::$app->request->post('FormGroupMember')['groupId'] ?? null]
                    ],
                    [
                        'allow' => true,
                        'actions' => ['update', 'edit'], // 修改群組成員
                        'permissions' => ['groupEditMember'],
                        'roleParams' => ['groupId' => Yii::$app->request->get('groupId')]
                    ],
                    [
                        'allow' => true,
                        'actions' => ['delete'], // 刪除群組成員
                        'permissions' => ['groupDeleteMember'],
                        'roleParams' => ['groupId' => Yii::$app->request->get('groupId')]
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
     * 群組成員管理
     */
    public function actionIndex($groupId)
    {
        $model = new FormGroupMember;
        $groupMemberList = $model->getGroupMemberList($groupId);
        
        // 取成員基本資料
        /** @var \yii\db\ActiveRecord $groupMemberList */
        $members = $groupMemberList->query->select('cn')->asArray()->all();
        $cns = [];
        array_walk($members, function($value) use (&$cns){
            $cns[] = $value['cn'];
        });
        $members = Users::find()->where(['cn' => $cns])->all();

        $membersInfo = ArrayHelper::map($members, 'cn', 'name');
        return $this->render('index',[
            'model' => $model,
            'membersInfo' => $membersInfo,
            'groupId' => $groupId,
            'groupMemberList' => $model->getGroupMemberList($groupId),
        ]);
    }

    /**
     * 群組成員新增
     */
    public function actionCreate($groupId)
    {
        $request = Yii::$app->request;

        $FormMemberCreator = new \app\models\FormMemberCreator;
        $model = new FormGroupMember;

        // 保存新查詢
        if ($request->isPost && empty($request->post()[Yii::$app->tablePag->setPageGetName])) {
            $postData = Yii::$app->request->post();
            $FormMemberCreator->saveInquire($postData);
            return $this->redirect(['create', 'groupId' => $groupId]);
        }

        // 查詢結果
        $personUser = Yii::$app->session->get($FormMemberCreator::$sessionKey);
        
        if (!is_null($personUser)) {
            return $this->render('create', [
                'groupId' => $groupId,
                'model' => $model,
                'dataProvider' => $FormMemberCreator->getDataProvider($personUser),
                'searchModel'  => $FormMemberCreator->getSearchModel($personUser),
                'filterItem'   => $FormMemberCreator::$filterItem,
                'showTable'    => true
            ]);
        }
        
        //開始建立查詢
        return $this->render('create', [
            'groupId' => $groupId,
            'model' => $model,
            'filterItem' => $FormMemberCreator::$filterItem,
            'showTable'  => false,
        ]);
    }

    /**
     * 儲存新增資料
     */
    public function actionStore()
    {
        $model = new FormGroupMember;
        $request = Yii::$app->request;
        if ($request->isPost) {
            $create = $model->createMember($request->post());

            if ($create) {
                Yii::$app->session->addFlash('success', '群組成員新增完成！');
            } else {
                Yii::$app->session->addFlash('error', '群組成員新增失敗！');
            }

            return $this->redirect($request->referrer);
        }
    }

    /**
     * 清除搜尋紀錄
     */
    public function actionClearSearch($groupId)
    {
        Yii::$app->session->remove(\app\models\FormMemberCreator::$sessionKey);
        return $this->redirect(['create','groupId' => $groupId]);
    }

    /**
     * 群組成員編輯
     */
    public function actionUpdate($groupId, $cn)
    {
        $model = new FormGroupMember;
        $member = $model::findOne(['groupId' => $groupId, 'cn' => $cn]);
        if (is_null($member))
            throw new \yii\web\NotFoundHttpException('找不到群組成員！');
        $memberInfo = Users::find()->where(['cn' => $member['cn']])->asArray()->all();

        return $this->render('update',[
            'model' => $model,
            'member' => $member,
            'memberInfo' => $memberInfo,
        ]);
    }

    /**
     * 更新成員資料
     */
    public function actionEdit($groupId, $cn)
    {
        $model = new FormGroupMember;
        $request = Yii::$app->request;
        if ($request->isPost) {

            $update = $model->updateMember($groupId, $cn, $request->post());

            if ($update) {
                Yii::$app->session->addFlash('success', '群組成員修改成功！');
            } else {
                Yii::$app->session->addFlash('error', '群組成員修改失敗！');
            }

            return $this->redirect($request->referrer);
        }
    }

    /**
     * 群組成員刪除
     */
    public function actionDelete($groupId, $cn)
    {
        $model = new FormGroupMember;
        $request = Yii::$app->request;
        if ($request->isPost) {

            $delete = $model->deleteMember($groupId, $cn, $request->post());

            if ($delete) {
                Yii::$app->session->addFlash('success', '群組成員刪除成功！');
            } else {
                Yii::$app->session->addFlash('error', '群組成員刪除失敗！');
            }

            return $this->redirect($request->referrer);
        }
    }
}
