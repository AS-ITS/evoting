<?php
namespace app\controllers;

use Yii;
use app\models\Logs;

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\Json;
use app\models\Config;
use app\models\Passwd;
use app\models\PasswordDisplay;
use app\models\FormVotes;
use app\models\FormPasswords;
use app\models\Users;
use app\components\helper\ArrayHelper;
use app\models\FormExportPasswd;
use app\components\helper\FileLoader;
use PhpOffice\PhpWord\TemplateProcessor;
use app\components\SensitiveReauth;
use app\traits\PlaintextUnlockTrait;

class PasswdController extends \app\components\Controller
{
    use PlaintextUnlockTrait;
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
                        // 匿名投票才能設定密碼
                        'allow' => false,
                        'matchCallback' => function ($rule, $action) use (&$voteID,&$candiConfig)
                        {
                            $voteID = Yii::$app->getRequest()->get('voteID');
                            $voteInfo = (new FormVotes)->getVoteInfo($voteID);
                            if($voteInfo->type != FormVotes::TYPE_ANON)
                            {
                                return true;
                            }
                            return false;
                        },
                        'denyCallback' => function ($rule, $action) use (&$voteID) 
                        {
                            if (!Yii::$app->user->isGuest)
                            {
                                Yii::$app->session->setFlash('error', '記名投票類型無法設定投票密碼！');
                                return $this->redirect(['elect/edit-vote','voteID'=>$voteID]);
                            }
                            $action->controller->NotAllowedAccess();
                        }
                    ],
                    [
                        // 根據網站設定決定是否可以刪除密碼
                        'actions' => ['delete', 'delete-all'],
                        'allow' => false,
                        'matchCallback' => function ($rule, $action) use (&$config)
                        {
                            $config = Config::findOne(['id' => Yii::$app->id]);
                            if($config === null || $config->canDeletePassword != 1 || !Yii::$app->user->can('sa'))
                            {
                                return true;
                            }
                            return false;
                        },
                        'denyCallback' => function ($rule, $action) use (&$voteID) 
                        {
                            if (!Yii::$app->user->isGuest)
                            {
                                Yii::$app->session->setFlash('error', '無法刪除投票密碼！');
                                return $this->redirect(['passwd/index', 'voteID' => $voteID]);
                            }
                            $action->controller->NotAllowedAccess();
                        }
                    ],
                    [
                        // 不設定actions表示全部
                        'allow' => true,
                        'permissions' => ['votePasswd'],
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
     * 密碼列表
     */
    public function actionIndex($voteID)
    {
        $model = new FormPasswords;

        // 基本資料
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);
        $model->updateRoundPasswordsCount($voteID);
        $ctx = FormPasswords::resolvePasswordContext($voteInfo);
        // 是否共用密碼: 包含密碼內容、組別、標記
        $bindVoteInfo = null;
        $passwordVoteID = $ctx['passwordVoteID'];
        if ($voteInfo->isBindVote) {
            $bindVoteInfo = $FormVotes->getVoteInfo($voteInfo->bindWhichVote);
        }
        $parties = $FormVotes->getVoteParty($passwordVoteID, Yii::$app->language, $voteInfo->partyOrNot);
        $marks = $model->getMarks($passwordVoteID);
        $dataProvider = $model->getDataProvider(
            $passwordVoteID,
            Yii::$app->request->queryParams,
            $ctx['ballotVoteID'],
            $ctx['round']
        );
        // 取得已投票的資料（本輪 ballots.creator）
        $votedBallot = FormPasswords::getVotedPasswordIds($ctx['ballotVoteID'], $ctx['round']);

        return $this->render('index',[
            'model' => $model,
            'dataProvider' => $dataProvider,
            'passwd' => new \app\models\Passwd,
            'exportPasswd' => new FormExportPasswd,
            'voteInfo' => $voteInfo,
            'bindVoteInfo' => $bindVoteInfo,
            'parties'  => $parties,
            'marks'  => $marks,
            'votedBallot'  => $votedBallot,
            'plaintextUnlocked' => SensitiveReauth::isPlaintextUnlocked(),
            'plaintextUnlockRemaining' => SensitiveReauth::plaintextUnlockRemainingSeconds(),
            'reauthRequired' => SensitiveReauth::isRequired(),
            'useTotp' => SensitiveReauth::isRequired() && \app\components\TotpService::isEnabledForUser(
                Users::findOne(Yii::$app->user->id) ?? new Users()
            ),
        ]);
    }

    public function actionUnlockPlaintext($voteID)
    {
        return $this->performUnlockPlaintext($voteID, 'passwd/index');
    }

    public function actionLockPlaintext($voteID)
    {
        return $this->performLockPlaintext($voteID, 'passwd/index');
    }
    
    /**
     * 生成密碼
     *
     * @param  string $voteID
     * @return void
     */
    public function actionCreatePassword($voteID)
    {
        $model = new FormPasswords;

        // 處理生成密碼
        $request = $this->request;
        if($request->isPost && empty($request->post()[Yii::$app->tablePag->setPageGetName]))// 提交表單
        {
            $post = $request->post();
            $post['format'] = $post['format'] ?? '';
            // 密碼組成為數字或英文不能設定格式
            if (in_array($post['type'], ['int', 'en']) && !empty($post['format'])) {
                Yii::$app->session->setFlash('error', '密碼組成為數字或英文不能設定格式！');
                return $this->redirect(['index', 'voteID' => $voteID]);
            }
            // 密碼長度必須與格式長度相同
            if (!empty($post['format']) && strlen($post['format']) != $post['length']) {
                Yii::$app->session->setFlash('error', '密碼長度必須與格式長度相同！');
                return $this->redirect(['index', 'voteID' => $voteID]);
            }
            // 密碼組成英文只有小寫格式不能含有S
            if ($post['type'] == Passwd::TYPE_MIX_LOWER && strpos($post['format'], 'S') !== false) {
                Yii::$app->session->setFlash('error', '密碼組成英文只有小寫格式不能有大寫S！');
                return $this->redirect(['index', 'voteID' => $voteID]);
            }
            // 密碼組成英文只有大寫格式不能含有s
            if ($post['type'] == Passwd::TYPE_MIX_UPPER && strpos($post['format'], 's') !== false) {
                Yii::$app->session->setFlash('error', '密碼組成英文只有大寫格式不能有小寫s！');
                return $this->redirect(['index', 'voteID' => $voteID]);
            }
            $passwdAry = $model->genPasswd($voteID, $post['num'], $post['length'], $post['type'], $post['format']);
            // 注意！必須禁用 yii2 debug，在 php5.4 未禁用的情況下運行可能導致內存不足！
            while(!empty($passwdAry)) {
                $model->creationPasswd(
                    $voteID, 
                    array_splice($passwdAry, 0, 500), // 批次新增，每次500筆
                    $post['party'], 
                    $post['status'], 
                    $post['dtrack'],
                    empty($post['mark']) ? NULL : trim($post['mark'])
                );
            }
            $model->updateRoundPasswordsCount($voteID);
            $formData = ArrayHelper::only($post, ['party', 'length', 'num', 'dtrack', 'status', 'type', 'format', 'mark']);
            Logs::add(Logs::VOTE_PASSWORD_GENERATE, Json::encode(compact('voteID')+$formData, 336));
            Yii::$app->session->setFlash('success', '密碼生成完成！');

            return $this->redirect(['index', 'voteID' => $voteID]);
        }
    }
    
    /**
     * 設定狀態
     *
     * @param  mixed $voteID
     * @return void
     */
    public function actionSwitchStatus($voteID)
    {
        $model = new FormPasswords;

        $request = $this->request;
        if ($request->isPost) {
            $FormVotes = new FormVotes;
            $voteInfo = $FormVotes->getVoteInfo($voteID);
            $ctx = FormPasswords::resolvePasswordContext($voteInfo);
            $formData = ArrayHelper::only($request->post(), ['party', 'mark', 'dtrack', 'status', 'voted']);
            $updateCount = $model->setPasswdStatus(
                $ctx['passwordVoteID'],
                $formData,
                $ctx['ballotVoteID'],
                $ctx['round']
            );
            Logs::add(Logs::VOTE_PASSWORD_SET_STATUS, Json::encode([
                'voteID' => $voteID,
                'passwordVoteID' => $ctx['passwordVoteID'],
                'round' => $ctx['round'],
            ] + $formData, 336));
            Yii::$app->session->setFlash('success', "設定狀態完成，共更新了{$updateCount}組密碼。");

            return $this->redirect(['index', 'voteID' => $voteID]);
        }
    }

    /**
     * 密碼長度計算
     * 
     * 結果如下:
     * -------------------
     * 密碼長度 | 加密長度
     * --------|---------
     *  6 ~ 14 |    32
     * 15 ~ 30 |    60
     * 31 ~ 46 |    88
     * 47 ~ 62 |   120
     * -------------------
     */
    // protected function actionTest()
    // {
    //     $Passwd = new \app\models\Passwd;
    //     $passwdAry = [];
    //     foreach(range(1,100) as $length)
    //     {
    //         $passwd = $Passwd->genShuffleStr(1,$length,Passwd::TYPE_MIX_EXCL);
    //         $passwdE = $Passwd->encrypt($passwd);
    //         $passwdAry[] = [
    //             'passwd' => $Passwd->decrypt($passwdE),
    //             'passwdE' => $passwdE,
    //             'len' => strlen($passwdE),
    //         ];
    //     }
    //     header('content-Type: text/plain; charset=utf-8');
    //     print_r($passwdAry); echo "\n";exit();
    // }

    /**
     * 切換開關
     */
    public function actionToggle($voteID)
    {
        $model = new FormPasswords;

        $request = Yii::$app->request;
        if($request->isAjax)
        {
            if(is_null($request->post('action')) || is_null($request->post('id')))
                throw new \yii\web\HttpException( 400, '缺少必要參數');
            $id = $request->post('id');
            $url = \yii\helpers\Url::to(['passwd/toggle', 'voteID' => $voteID]);
            $action = $request->post('action');
            switch($action)
            {
                case 'status':
                    $text = Yii::$app->params['ct.passwd.validAry'][
                        $model->toggleStatus($id)
                    ];
                    return \yii\helpers\Html::a( $text, '#', [
                        'data-id' => $id,
                        'onclick' => "postToggle('$url', '$action', this);"
                    ]);
                    break;
                
                case 'dtrack':
                    $text = Yii::$app->params['ct.passwd.dtrackAry'][
                        $model->toggleDtrack($id)
                    ];
                    return \yii\helpers\Html::a( $text, '#', [
                        'data-id' => $id,
                        'onclick' => "postToggle('$url', '$action', this);"
                    ]);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * 刪除密碼
     */
    public function actionDelete($voteID)
    {
        $model = new FormPasswords;

        $request = Yii::$app->request;
        if($request->isAjax)
        {
            if(is_null($request->post('id')))
                throw new \yii\web\HttpException( 400, '缺少必要參數');
            if($model->DeletePasswd($request->post('id')))
            {
                $model->updateRoundPasswordsCount($voteID);
                Logs::add(Logs::VOTE_PASSWORD_DELETE, Json::encode(compact('voteID'), 336));
                return 'success';
            }
            Logs::add(Logs::VOTE_PASSWORD_DELETE_FAIL, Json::encode(compact('voteID'), 336));
            throw new \yii\web\HttpException( 400, '刪除失敗！');
        }
    }

    /**
     * 組別批量刪除密碼
     */
    public function actionDeleteAll($voteID, $party=null, $mark=null)
    {
        $model = new FormPasswords;
        $num = $model->DeletePasswdAll($voteID, $party, $mark);
        $model->updateRoundPasswordsCount($voteID);
        Logs::add(Logs::VOTE_PASSWORD_DELETE, Json::encode(compact('voteID', 'party', 'mark'), 336));
        Yii::$app->session->setFlash('success', "已刪除 $num 筆投票密碼！");
        return $this->redirect(['index', 'voteID' => $voteID]);
    }
    
    /**
     * 密碼匯出
     *
     * @param  string $voteID
     * @return void
     */
    public function actionExport($voteID)
    {
        $model = new FormPasswords;

        $request = Yii::$app->request;
        if($request->isPost)
        {
            if ($request->post('exportAcknowledged') !== '1') {
                Yii::$app->session->setFlash('error', '請確認密碼匯出操作後再送出。');
                return $this->redirect(['index', 'voteID' => $voteID]);
            }

            $adminUser = Users::findOne(Yii::$app->user->id);
            if (!\app\components\SensitiveReauth::verify(
                $request->post('adminPassword'),
                $request->post('totpCode')
            )) {
                Yii::$app->session->setFlash(
                    'error',
                    $adminUser
                        ? \app\components\SensitiveReauth::failureMessage($adminUser)
                        : Yii::t('app', '二次驗證失敗。')
                );
                return $this->redirect(['index', 'voteID' => $voteID]);
            }

            $post = $request->post();
            $FormVotes = new FormVotes;
            $voteInfo = $FormVotes->getVoteInfo($voteID);
            $ctx = FormPasswords::resolvePasswordContext($voteInfo);
            $parties = $FormVotes->getVoteParty($ctx['passwordVoteID'], Yii::$app->language, true);
            $passwords = $model->search(
                $ctx['passwordVoteID'],
                $post,
                $ctx['ballotVoteID'],
                $ctx['round']
            )->asArray()->all();
            $votedBallot = FormPasswords::getVotedPasswordIds($ctx['ballotVoteID'], $ctx['round']);

            $headers = [
                'sn' => '編號', 'party' => '組別', 'passwd' => '密碼', 'dtrack' => '雙軌投票', 'status' => '狀態', 
                'voted' => '本輪已投票', 'mark' => '標記'
            ];

            $callback = [
                'party' => function ($row, $column) use ($parties) {
                    return $parties[$row[$column]];
                },
                'passwd' => function ($row, $column) {
                    $version = (int) ($row['crypto_version'] ?? Passwd::CRYPTO_V0);
                    return PasswordDisplay::decryptForExport($row[$column], $version);
                },
                'dtrack' => function ($row, $column) {
                    return Yii::$app->params['ct.passwd.dtrackAry'][$row[$column]];
                },
                'status' => function ($row, $column) {
                    return Yii::$app->params['ct.passwd.validAry'][$row[$column]];
                },
                'voted' => function ($row, $column) use ($votedBallot) {
                    $isVoted = in_array((int) $row['id'], $votedBallot, true);
                    return Yii::$app->params['ct.yesOrNoAry'][(int) $isVoted];
                },
            ];

            $date = date('YmdHis');
            $fileName = strip_tags($voteInfo->voteName)."密碼匯出_{$date}.csv";

            Logs::add(Logs::VOTE_PASSWORD_EXPORT, Json::encode([
                'voteID' => $voteID,
                'count' => count($passwords),
                'format' => 'csv',
            ], 336), ['voteID' => $voteID]);

            return FileLoader::csvSimple($headers, $passwords, $fileName, $callback);
        }
    }

    /**
     * 生成密碼函
     *
     * @param  mixed $voteID
     * @return void
     */
    public function actionExportPasswd($voteID)
    {
        $model = new FormExportPasswd;

        if ($this->request->isPost) {
            if ($this->request->post('exportAcknowledged') !== '1') {
                Yii::$app->session->setFlash('error', '請確認密碼函生成操作後再送出。');
                return $this->redirect(['index', 'voteID' => $voteID]);
            }

            $adminUser = Users::findOne(Yii::$app->user->id);
            if (!SensitiveReauth::verify(
                $this->request->post('adminPassword'),
                $this->request->post('totpCode')
            )) {
                Yii::$app->session->setFlash(
                    'error',
                    $adminUser
                        ? SensitiveReauth::failureMessage($adminUser)
                        : Yii::t('app', '二次驗證失敗。')
                );
                return $this->redirect(['index', 'voteID' => $voteID]);
            }

            // 分析上傳的樣板
            $model->template = \yii\web\UploadedFile::getInstance($model, 'template');
            if ($model->validate()) {
                $FormVotes = new FormVotes;
                $voteInfo = $FormVotes->getVoteInfo($voteID);
                $ctx = FormPasswords::resolvePasswordContext($voteInfo);
                $passwd = new Passwd;
                $post = $this->request->post();
                $filters = ArrayHelper::only($post, ['party', 'mark', 'voted', 'start', 'end']);
                $passwords = (new FormPasswords)->buildListQuery(
                    $ctx['passwordVoteID'],
                    $filters,
                    $ctx['ballotVoteID'],
                    $ctx['round']
                )->orderBy(['party' => SORT_ASC, 'sn' => SORT_ASC]);

                $count = $passwords->count();
                Logs::add(Logs::VOTE_PASSWORD_EXPORT, Json::encode([
                    'voteID' => $voteID,
                    'passwordVoteID' => $ctx['passwordVoteID'],
                    'round' => $ctx['round'],
                    'count' => $count,
                    'format' => 'docx',
                ] + $filters, 336), ['voteID' => $voteID]);
                // 導入樣板至PHPWord
                $templateProcessor = new TemplateProcessor($model->template->tempName);
                // 複製密碼數量的區塊
                $templateProcessor->cloneBlock('content', $count, true, true);
                // 透過迴圈將密碼等資訊填入樣板
                foreach ($passwords->each() as $key => $password) {
                    $num = $key + 1;
                    $version = (int) ($password->crypto_version ?? Passwd::CRYPTO_V0);
                    $templateProcessor->setValue('password#'.$num, $passwd->decrypt($password->passwd, null, null, $version));
                    $templateProcessor->setValue('number#'.$num, $password->party.'-'.$password->sn);
                    
                    if ($num != $count) {
                        $templateProcessor->setValue('PAGE_BREAK#'.$num, '</w:t></w:r>'.'<w:r><w:br w:type="page"/></w:r>'.'<w:r><w:t>');
                    }
                    else {
                        $templateProcessor->setValue('PAGE_BREAK#'.$num, '');
                    }
                }

                // 儲存檔案
                $path = Yii::$app->runtimePath.'/storage';
                if (!file_exists($path)) {
                    mkdir($path, 0775, true);
                }
                $fileName = "密碼函_".date('YmdHis').".docx";
                $file = Yii::$app->runtimePath."/storage/{$fileName}";
                $templateProcessor->saveAs($file);
                
                // 下載檔案並刪除暫存檔
                return Yii::$app->response->sendFile($file, $fileName)
                    ->on(\yii\web\Response::EVENT_AFTER_SEND, function($event) {
                        unlink($event->data);
                    }, $file);
            }
            else {
                // 錯誤訊息
                foreach($model->errors as $message) {
                    Yii::$app->session->addFlash('error', $message[0]);
                }
                return $this->refresh();
            }
        }

        return $this->redirect(['index', 'voteID' => $voteID]);
    }
    
    /**
     * 下載密碼函樣板範例
     *
     * @param  mixed $voteID
     * @return void
     */
    public function actionExportDefault($voteID)
    {
        $voteInfo = (new FormVotes)->getVoteInfo($voteID);

        $templateProcessor = new TemplateProcessor(Yii::$app->basePath.'/web/docx/passwd/template.docx');
        $templateProcessor->setValue('title', Html::encode($voteInfo->Name));
        $templateProcessor->setValue('titleE', Html::encode($voteInfo->NameE));
        $templateProcessor->setValue('link', Url::base('https').'/'.$voteInfo->shortUrl);
        $templateProcessor->setValue('sessionCode', $voteInfo->session);
        
        $path = Yii::$app->runtimePath.'/storage';
        if (!file_exists($path)) {
            mkdir($path, 0775, true);
        }
        $fileName = "範例密碼函_".date('YmdHis').".docx";
        $file = Yii::$app->runtimePath."/storage/{$fileName}";
        $templateProcessor->saveAs($file);

        return Yii::$app->response->sendFile($file, $fileName)
            ->on(\yii\web\Response::EVENT_AFTER_SEND, function($event) {
                unlink($event->data);
            }, $file);
    }
}
