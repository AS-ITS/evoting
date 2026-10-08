<?php
namespace app\controllers;

use Yii;
use app\models\Logs;
use yii\helpers\Json;
use app\models\CandiData;

use app\models\FormVotes;
use app\models\Questions;
use app\components\helper\ArrayHelper;
use app\models\FormCandiData;
use app\models\FormCandiConfig;
use app\models\FormManageCount;
use app\components\helper\FileLoader;

/**
 * 投票新修刪候選人、候選人相關設定
 */
class CandiController extends \app\components\Controller
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
                        'permissions' => ['voteCandi'],
                        'roleParams' => ['voteID' => Yii::$app->request->get('voteID')],
                    ],
                    [
                        'actions' => ['view-photo'],
                        'allow' => true,
                        'roles' => ['?'],// 未登入
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
     * 候選人管理
     */
    public function actionIndex($voteID)
    {
        return $this->redirect(['candi/data', 'voteID' => $voteID]);
    }

    /**
     * 候選人配置
     */
    public function actionConfig($voteID, $questionID=null)
    {
        // 投票資訊
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);

        // 候選人配置
        $model = new FormCandiConfig;
        $CandiConfig = FormCandiConfig::findOne(['voteID' => $voteID, 'questionID' => $questionID]);
        if(is_null($CandiConfig))
        {
            $model->voteID = $voteID;
            $model->Name = '姓名';
            $model->NameE = 'Name';
            $model->fontSize = '26px';
            $model->fontSizeE = '19px';
            $model->cellHeight = '2.4rem';
            $model->cellHeightE = '3rem';
            $model->headerColor = '#ffffff';
            $model->NameUnit = '名';
            $model->NameUnitE = 'candidate';
            $model->showFieldSort = $model::$defFieldSort;
            $action = 'create';
        }
        else
        {
            $model = $CandiConfig;
            $model->alignLeft = Json::decode($CandiConfig->alignLeft);
            $action = 'update';
        }
        $model->setScenario($action);

        // 處理 POST 請求
        $request = Yii::$app->request;
        if($request->isPost)
        {
            $postData = $request->post();
            $statusConfig = $model->updateConfig($voteID, $postData, $action);
            if($statusConfig === true)
            {
                Yii::$app->session->setFlash('success', '候選名單配置'.($action == 'update' ? '修改' : '建立').'完成！');
            }
            if ($voteInfo->candiConfig == FormVotes::CANDI_CONFIG_BY_Q) {
                return $this->redirect(['config', 'voteID' => $voteID, 'questionID' => $model->questionID]);
            }
            else {
                return $this->redirect(['config', 'voteID' => $voteID]);
            }
        }

        return $this->render('config', [
            'model' => $model,
            'action'=> $action,
            
            'voteID' => $voteID,
            'voteInfo' => $voteInfo,
        ]);
    }

    /**
     * 候選名單管理
     */
    public function actionData($voteID, $questionID=null)
    {
        // 投票資訊
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);

        // 候選名單配置
        $FormCandiConfig = new FormCandiConfig;
        $questions = (new Questions())->getRoundQuestions($voteID, $voteInfo->round)->asArray()->all();
        if (empty($questions)) {
            Yii::$app->session->setFlash('warning', '尚未建立問題！');
            return $this->redirect(['question/index', 'voteID' => $voteID]);
        }
        if ($voteInfo->candiConfig == FormVotes::CANDI_CONFIG_BY_Q) {
            foreach ($questions as $question) {
                $candiConfig = $FormCandiConfig->getConfigWithVoteID($voteID, $question['questionID']);
                if(is_null($candiConfig)) {
                    Yii::$app->session->setFlash('warning', '問題【'.$question['title'].'】尚未建立候選名單配置！');
                    return $this->redirect(['candi/config', 'voteID' => $voteID, 'questionID' => $question['questionID']]);
                }
            }
            if (!empty($questionID)) {
                $candiConfig = $FormCandiConfig->getConfigWithVoteID($voteID, $questionID);
            }
        }
        else {
            $candiConfig = $FormCandiConfig->getConfigWithVoteID($voteID);
            if(is_null($candiConfig)) {
                Yii::$app->session->setFlash('warning', '請先建立候選名單配置！');
                return $this->redirect(['candi/config','voteID' => $voteID]);
            }
        }
        // 候選名單資料
        $searchModel = new FormCandiData;
        $searchModel->questionID = $questionID;
        $query = $searchModel->search($voteID, Yii::$app->request->queryParams);

        // 條件刪除候選名單
        $request = Yii::$app->request;
        if($request->isPost && empty($request->post()[Yii::$app->tablePag->setPageGetName]))
        {
            $postData = $request->post();
            $searchModel->deleteCandiData($voteID, $voteInfo->round, $postData);
            return $this->redirect(['data', 'voteID' => $voteID]);
        }
        return $this->render('data', [
            'voteID' => $voteID,
            'candiConfig' => $candiConfig,
            'FormVotes' => $FormVotes,
            'voteInfo' => $voteInfo,

            'searchModel' => $searchModel,
            'dataProvider' => (new \app\models\DataProvider)->getBasicDataProvider($query),
        ]);
    }

    /**
     * 刪除候選名單
     */
    public function actionDelete($voteID, $id)
    {
        // 候選名單資料
        $FormCandiData = new FormCandiData;
        if(is_null($FormCandiData->deleteCandiDataWithVoteID($voteID, $id)))
        {
            Yii::$app->session->setFlash('warning', '候選名單不存在！');
            return $this->redirect(['candi/data', 'voteID'=>$voteID]);
        }
        Yii::$app->session->setFlash('success', '候選名單刪除完成！');
        return $this->redirect(['candi/data', 'voteID'=>$voteID]);
    }

    /**
     * 手動建立候選名單
     */
    public function actionManual($voteID, $action, $id = null)
    {
        // 投票資訊
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);

        // 候選名單配置
        $FormCandiConfig = new FormCandiConfig;

        // 候選名單資料
        $FormCandiData = new FormCandiData;

        // 編輯時，id不得為空
        if($action == 'update' && empty($id)) {
            return $this->redirect(['voteID'=>$voteID, 'action'=>'create']);
        }

        // 處理 POST 請求
        $request = Yii::$app->request;
        if($request->isPost)
        {
            $postData = $request->post();
            $statusConfig = $FormCandiData->updateCandiData($voteID, $id, $postData, $action);
            if($statusConfig === true)
            {
                Yii::$app->session->setFlash('success', '候選名單'.($action == 'update' ? '修改' : '新增').'完成！');
            }
            if($action == 'create')
            {
                switch($postData['callback'])
                {
                    case 'list':
                        return $this->redirect(['candi/data', 'voteID' => $voteID]);
                        break;
                    case 'createAs':
                        return $this->redirect(['manual', 'voteID' => $voteID, 'action' => 'create', 'id' => $FormCandiData->id]);
                        break;
                    case 'create':
                        return $this->redirect(['manual', 'voteID' => $voteID, 'action' => 'create']);
                        break;
                    case 'update':
                        return $this->redirect(['manual', 'voteID' => $voteID, 'action' => 'update', 'id' => $FormCandiData->id]);
                        break;
                }
            }
            else if($action == 'update') {
                return $this->redirect(['manual', 'voteID' => $voteID, 'action' => $action, 'id' => $id]);
            }
        }

        // 初始化表單
        if($action == 'update')
        {
            // 更新: 資料不存在導回列表
            if(is_null($FormCandiData->getCandiDataWithVoteID($voteID, $id)))
            {
                Yii::$app->session->setFlash('warning', '候選項目不存在！');
                return $this->redirect(['candi/data', 'voteID'=>$voteID]);
            }
            else {
                // 問題清單
                $questions = Questions::find()->where(['voteID' => $voteID, 'round' => $voteInfo->round, 'party' => $FormCandiData->party])->asArray()->all();
                $questions = ArrayHelper::map($questions, 'questionID', 'title');
            }
            // 判斷候選名單配置
            if ($voteInfo->candiConfig == FormVotes::CANDI_CONFIG_BY_Q) {
                $candiConfig = $FormCandiConfig->getConfigWithVoteID($voteID, $FormCandiData->questionID);
            }
            else {
                $candiConfig = $FormCandiConfig->getConfigWithVoteID($voteID);
            }
        }
        else if($action == 'create')
        {
            $questions = [];

            if(!empty($id))
            {
                // 另存: 資料不存在導回新增
                if(is_null($FormCandiData->getCandiDataWithVoteID($voteID, $id)))
                {
                    Yii::$app->session->setFlash('warning', '候選項目不存在！');
                    return $this->redirect(['voteID'=>$voteID, 'action'=>'create']);
                }
            }
            else
            {
                // 新增資料預設值
                $FormCandiData->orderNum = '0';
                $FormCandiData->genMode = 'manual';
            }
            // 判斷候選名單配置
            $candiConfig = $FormCandiConfig->getConfigWithVoteID($voteID);
        }
        
        return $this->render('manual', [
            'model' => $FormCandiData,
            'action' => $action,

            'FormVotes' => $FormVotes,
            'voteInfo' => $voteInfo,
            'candiConfig' => $candiConfig,
            'questions' => $questions,
        ]);
    }

    /**
     * 檔案導入候選人
     */
    public function actionCsvfile($voteID)
    {
        $model = new \app\models\FormCsvFile;

        // 投票資訊
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);
        $parties = $FormVotes->getVoteParty($voteInfo->voteID, 'zh-tw', true);

        // 候選人配置
        $FormCandiConfig = new FormCandiConfig;
        $CandiConfig = $FormCandiConfig->getConfigWithVoteID($voteID);

        // 問題清單
        $questions = Questions::find()->where(['voteID' => $voteID, 'round' => $voteInfo->round])->asArray()->all();
        $questions = ArrayHelper::index($questions, 'questionID');
        
        if (Yii::$app->request->isPost) {
            $model->csvFile = \yii\web\UploadedFile::getInstance($model, 'csvFile');
            if ($model->validate()) {
                if ($model->importData($voteID, $parties, $questions)) {
                    // file is uploaded successfully
                    Logs::add(Logs::VOTE_CANDI_CSV_CREATE, Json::encode(compact('voteID'), 336));
                    Yii::$app->session->addFlash('success', "候選人成功匯入 $model->dataCount 筆！");
                }
                else {
                    Logs::add(Logs::VOTE_CANDI_CSV_CREATE_FAIL, Json::encode(compact('voteID'), 336));
                }
                return $this->redirect(['candi/data', 'voteID'=>$voteID]);
            }
            // validate() 失敗（如未選擇檔案），fall-through 重新 render 表單以顯示錯誤訊息
        }

        return $this->render('csvfile', [
            'model' => $model,
            'voteInfo' => $voteInfo,
            'CandiConfig' => $CandiConfig,
            'parties' => $parties,
            'questions' => $questions,
        ]);
    }

    /**
     * 檔案導入候選人
     * 
     * @link mimeType https://stackoverflow.com/questions/7076042/what-mime-type-should-i-use-for-csv
     */
    public function actionCsvExample($voteID)
    {
        $model = new \app\models\FormCsvFile;

        // 投票資訊
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);

        // 候選人配置
        $FormCandiConfig = new FormCandiConfig;
        $CandiConfig = $FormCandiConfig->getConfigWithVoteID($voteID);

        $FieldCP = ArrayHelper::index($model::$FieldCP, 'column');
        if($CandiConfig->Name == '1')
        {
            unset($FieldCP['名稱']);
            unset($FieldCP['名稱英']);
        }
        else
        {
            unset($FieldCP['名字']);
            unset($FieldCP['名字英']);
        }
        unset($FieldCP['工作地點']);
        $column = \app\components\helper\ArrayHelper::getColumn(array_values($FieldCP), 'column');
        return Yii::$app->response->sendContentAsFile(
            // BOM 檔首 https://stackoverflow.com/questions/5601904/encoding-a-string-as-utf-8-with-bom-in-php
            // chr(239) chr(187) chr(191)  ->  0xEF 0xBB 0xBF
            chr(239) . chr(187) . chr(191) . join(',',$column),
            '檔案導入候選人(範例).csv',
            ['mimeType'=>'application/vnd.ms-excel'] // 根據 
        );
    }
    
    /**
     * 輪次導入
     *
     * @param  string $voteID
     * @return void
     */
    public function actionRound($voteID, $questionID=null)
    {
        $model = new \app\models\FormCandiData();

        // 投票資訊
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($voteID);

        // 處理 POST 請求
        $request = Yii::$app->request;
        if($request->isPost)
        {
            $postData = $request->post();
            $questionID = $postData[$model->formName()]['questionID'];
            if (empty($questionID)) {
                Yii::$app->session->addFlash('error', '請選擇候選人問題');
            }
            else {
                $model->importRound($voteID, $postData, $questionID);
            }
        }

        // 指定問題的候選人
        if (!empty($questionID)) {
            $selectQuestion = Questions::findOne($questionID);
            $manageCount = new FormManageCount($voteID, $selectQuestion->round);
            $ballotCountSort = $manageCount->ballotCountSort;
            $candiData = $ballotCountSort[$selectQuestion['party']][$questionID]['ranking'];
            $candi = ArrayHelper::index($candiData, 'candi');
        }
        else {
            $candi = [];
        }
        // 要導入的問題
        $questions = (new Questions())->getRoundQuestions($voteID, $voteInfo->round)->select(['questionID', 'title'])->asArray()->all();
        
        return $this->render('round', [
            'model' => $model,
            'FormVotes' => $FormVotes,
            'voteInfo' => $voteInfo,
            'candi' => $candi,
            'questions' => $questions,
        ]);
    }
    
    /**
     * 刪除指定候選人照片
     *
     * @param  string $voteID
     * @param  int $id
     * @return Response
     */
    public function actionDeletePhoto($voteID, $id)
    {
        $candiData = CandiData::findOne(['id' => $id, 'voteID' => $voteID]);
        $FileLoader = new FileLoader(Yii::getAlias('@filePool'));
        if ($FileLoader->removeImage(DIRECTORY_SEPARATOR.$candiData->voteID.DIRECTORY_SEPARATOR.$candiData->photo)) {
            $candiData->photo = null;
            $candiData->save();
            Yii::$app->session->addFlash('success', "刪除照片成功");
        }
        else {
            Yii::$app->session->addFlash('danger', "刪除照片失敗，找不到圖片路徑");
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    
    /**
     * Ajax取得指定問題的所有候選人
     *
     * @param  string $voteID
     * @return void
     */
    public function actionGetQuestionCandi($voteID)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            $candi = CandiData::find()
                ->select(['id', 'isReachThreshold', 'Name'])
                ->where(['voteID' => $voteID, 'questionID' => $request->post('questionID')])
                ->asArray()->all();
            echo json_encode($candi);
        }
    }
    
    /**
     * 檢視圖片
     *
     * @param  string $voteID
     * @param  string $file
     * @return void
     */
    public function actionViewPhoto($voteID, $file)
    {
        // voteID 必須是存在的投票場次，避免路徑穿越
        $vote = \app\models\Votes::findOne($voteID);
        if (!$vote) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', '找不到該投票場次'));
        }

        // 檔名消毒：僅允許純檔名，禁止任何路徑成分
        $file = basename($file);

        $baseDir = realpath(Yii::getAlias('@filePool').DIRECTORY_SEPARATOR.'candidatePic'.DIRECTORY_SEPARATOR.$vote->voteID);
        if ($baseDir === false) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', '找不到該檔案'));
        }

        $filePath = realpath($baseDir.DIRECTORY_SEPARATOR.$file);

        // 確認解析後的路徑仍位於該投票的圖片目錄內
        if ($filePath !== false && is_file($filePath)
            && strpos($filePath, $baseDir.DIRECTORY_SEPARATOR) === 0) {
            return Yii::$app->response->sendFile($filePath, $file, ['inline' => true]);
        }

        throw new \yii\web\NotFoundHttpException(Yii::t('app', '找不到該檔案'));
    }
}
