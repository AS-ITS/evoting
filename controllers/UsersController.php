<?php

namespace app\controllers;

use Yii;
use app\models\Logs;
use app\models\Users;
use app\models\FormTotpSetup;
use app\components\TotpService;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

use yii\helpers\Json;
use app\components\helper\ArrayHelper;

class UsersController extends \app\components\Controller
{
    /**
     * 行為
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'create', 'update', 'delete'],
                        'allow' => true,
                        'roles' => ['sa'],// 系統管理員
                    ],
                    [
                        'actions' => ['totp'],
                        'allow' => !Yii::$app->user->isGuest,
                    ],
                    [
                        'allow' => false,//拒絕訪問，未填 actions 或 allow 表示全部
                    ]
                ],
                'denyCallback' => self::denyCallback()
            ],
        ];
    }
    
    /**
     * 使用者管理首頁
     *
     * @return void
     */
    public function actionIndex()
    {
        $dataProvider = (new \app\models\DataProvider)->getBasicDataProvider(Users::find());
        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }
    
    /**
     * 建立使用者
     *
     * @return void
     */
    public function actionCreate()
    {
        $model = new Users();
        if ($this->request->isPost) {
            if ($model->submitForm($this->request->post())) {
                Logs::add(Logs::USERS_CREATE, Json::encode($model->attributes, 336));	// 200250626 Add by Fisher
                Yii::$app->session->addFlash('success', '新增成功');
                return $this->redirect(['index']);
            } else {
                Logs::add(Logs::USERS_CREATE_FAIL, Json::encode(['cn' => $model->cn, 'errors' => $model->errors], 336));	// 200250626 Add by Fisher
            }
        }
        return $this->render('create', compact('model'));
    }

    /**
     * 更新使用者
     *
     * @return void
     */
    public function actionUpdate($cn)
    {
        $model = Users::findOne($cn);
        $model->roles = explode(',', $model->roles);
        $oldAttributes = $model->oldAttributes;

        if ($this->request->isPost) {
            if ($model->submitForm($this->request->post())) {
                //比較差異
                $attributesDiff = ArrayHelper::getAttributesMigration($model->attributes, $oldAttributes);
                Logs::add(Logs::USERS_EDIT, Json::encode(compact('cn')+$attributesDiff, 336));	// 200250626 Add by Fisher
                Yii::$app->session->addFlash('success', '更新成功');
                return $this->redirect(['index']);
            } else {
                $errors = $model->errors ?? [];
                Logs::add(Logs::USERS_EDIT_FAIL, Json::encode(compact('cn')+$errors, 336));	// 200250626 Add by Fisher
            }
        }
        return $this->render('update', compact('model'));
    }

    /**
     * 刪除使用者
     *
     * @return void
     */
    public function actionDelete($cn)
    {
        $model = Users::findOne($cn);
        if ($model === null) {
            Logs::add(Logs::USERS_DELETE_FAIL, Json::encode(compact('cn') + ['error' => '使用者不存在'], 336));
            Yii::$app->session->addFlash('error', '使用者不存在');
            return $this->redirect(['index']);
        }
        if ($model->delete()) {
            Logs::add(Logs::USERS_DELETE, Json::encode($model->attributes, 336));	// 200250626 Add by Fisher
            Yii::$app->session->addFlash('success', '刪除成功');
        } else {
            Logs::add(Logs::USERS_DELETE_FAIL, Json::encode(compact('cn')+$model->errors, 336));	// 200250626 Add by Fisher
        }
        return $this->redirect(['index']);
    }

    /**
     * 雙因素驗證（TOTP）綁定 — 僅能管理自己的帳號
     */
    public function actionTotp()
    {
        $user = Users::findOne(Yii::$app->user->id);
        if ($user === null) {
            throw new \yii\web\NotFoundHttpException('使用者不存在');
        }

        $model = new FormTotpSetup();
        $provisioningUri = null;
        $qrDataUri = null;

        if ($this->request->isPost && $model->load($this->request->post()) && $model->validate()) {
            if ($model->process($user)) {
                if ($model->step === FormTotpSetup::STEP_GENERATE) {
                    Yii::$app->session->setFlash('info', '請使用驗證器 App 掃描 QR Code 後輸入 6 碼代碼完成綁定。');
                } elseif ($model->step === FormTotpSetup::STEP_CONFIRM) {
                    Logs::add(Logs::USERS_TOTP_ENABLE, Json::encode(['cn' => $user->cn], 336));
                    Yii::$app->session->setFlash('success', '雙因素驗證已啟用。');
                    return $this->redirect(['totp']);
                } else {
                    Logs::add(Logs::USERS_TOTP_DISABLE, Json::encode(['cn' => $user->cn], 336));
                    Yii::$app->session->setFlash('success', '雙因素驗證已停用。');
                    return $this->redirect(['totp']);
                }
            }
        }

        $pendingSecret = Yii::$app->session->get('totp.pending_secret');
        $pendingCn = Yii::$app->session->get('totp.pending_cn');
        if ($pendingSecret && $pendingCn === $user->cn) {
            $provisioningUri = TotpService::getProvisioningUri($user, $pendingSecret);
            try {
                $writer = new PngWriter();
                $qrDataUri = $writer->write(new QrCode($provisioningUri))->getDataUri();
            } catch (\Throwable $e) {
                $qrDataUri = null;
            }
        }

        return $this->render('totp', [
            'model' => $model,
            'user' => $user,
            'provisioningUri' => $provisioningUri,
            'qrDataUri' => $qrDataUri,
            'totpEnabled' => TotpService::isEnabledForUser($user),
        ]);
    }
}
