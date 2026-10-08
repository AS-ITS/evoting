<?php
namespace app\actions;

use Yii;
use yii\data\SqlDataProvider;
use yii\base\Action;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use app\models\UniversalActiveRecord;
use app\interfaces\CacheDataInterface;
use app\models\DataTableModifierSearch;
use app\components\SaDbTools;
use Exception;

/**
 * 資料表編輯器 data-table-modifier
 */
class DataTableModifierAction extends Action implements CacheDataInterface
{
    /**
     * 動作: 搜尋
     * @var string
     */
    const ACTION_SEARCH = 'search';
    /**
     * 動作: 搜尋結果
     * @var string
     */
    const ACTION_RESULT = 'result';
    /**
     * 動作: 搜尋結果
     * @var string
     */
    const ACTION_RESULT_DELETE = 'result-delete';
    /**
     * 動作: 創建
     * @var string
     */
    const ACTION_CREATE = 'create';
    /**
     * 動作: 更新
     * @var string
     */
    const ACTION_UPDATE = 'update';
    /**
     * 動作: 刪除
     * @var string
     */
    const ACTION_DELETE = 'delete';
    /**
     * 動作: SQL
     * @var string
     */
    const ACTION_SQL = 'sql';
    /**
     * 動作: SQL 結果
     * @var string
     */
    const ACTION_SQL_RESULT = 'sql-result';

    /**
     * @var string|null|false 要應用於此操作視圖的佈局的名稱。
     * 若未設置，將使用控制器中配置的佈局。
     * @see \yii\base\Controller::$layout
     */
    public $layout;
    /**
     * @var string|null 要渲染的視圖文件。
     * 若未設置，將使用 `id` 的值。
     */
    public $view='@app/views/actions/data-table-modifier-';

    /**
     * @var int 查詢結果生命週期。單位:秒
     */
    public $searchDependency=28800;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        
    }

    /**
     * 資料表編輯
     *
     * @param string $tableName 資料表名稱
     * @param string $action 動作(CRUD)
     * @param string $sid 查詢結果的ID
     * @param string $id 當指定某一筆資料時，綜合id
     *
     * @return \yii\web\Response
     */
    public function run($tableName=null, $action=null, $sid=null, $id=null, $db=null)
    {
        SaDbTools::assertEnabled();

        $model = new UniversalActiveRecord();

        // 若要指定資料庫
        if(!is_null($db))
        {
            if($db == 'db')
            {
                return $this->controller->redirect([]);
            }
            $model::setDb($db);
        }
        // 判斷是否有資料表
        if(!is_null($tableName) && !is_null($action))
        {
            $tableNames = $model->getTableNames();
            sort($tableNames);
            if (!in_array($tableName, $tableNames))
            {
                throw new BadRequestHttpException('資料表名稱不存在！');
            }
            // 設定資料表名稱
            $model->setTableName($tableName);
            // header('content-Type: text/plain; charset=utf-8');
            // \yii\helpers\VarDumper::dump($model->getTableNameSchema(), $depth=10, $highlight=false);
            // exit();
        }
        else if(!is_null($tableName) && is_null($action))
        {
            // 導向資料查詢結果
            return $this->controller->redirect([
                'db' => $db,
                'action' => static::ACTION_SEARCH,
                'tableName' => $tableName,
            ]);
        }

        // 根據不同需要導向
        switch ($action)
        {
            case static::ACTION_SQL: // SQL
                if (Yii::$app->request->isPost)
                {
                    // 以分號切割SQL語法
                    $inputSql = Yii::$app->request->post('sql');
                    $iSql = $model->splitSqlStatement($inputSql);
                    // 判斷是否同時更新及查詢
                    if ($model->checkTwoTypeSql($iSql))
                    {
                        throw new Exception('無法同時更新資料及查詢資料QQ');
                    }
                    // 檢查是否同時含有兩種類型的語法
                    $ctts = $model->checkTwoTypeSql($iSql, true);
                    if ($ctts['exec'] > 0) {
                        throw new BadRequestHttpException(
                            '基於安全政策，SQL 編輯器僅允許 SELECT 查詢。'
                        );
                    }
                    foreach ($iSql as $sql)
                    {
                        if (!$model->isAllowedSelectSql($sql)) {
                            throw new BadRequestHttpException(
                                '基於安全政策，SQL 編輯器僅允許 SELECT / SHOW / DESCRIBE / EXPLAIN 查詢。'
                            );
                        }
                        if ($ctts['query'] > 0)
                        {
                            \app\models\Logs::add(
                                \app\models\Logs::SYSTEM_SQL_QUERY,
                                \yii\helpers\Json::encode([
                                    'sql' => mb_substr($sql, 0, 500),
                                    'user' => Yii::$app->user->id,
                                ])
                            );
                            // 擷取網頁的唯一識別標籤
                            $redirectUniqueId = $_SERVER["UNIQUE_ID"];
                            // 設定查詢結果至暫存(catch)
                            $this->handleCatch($redirectUniqueId, $sql);

                            return $this->controller->redirect([
                                'db' => $db,
                                'action' => static::ACTION_SQL_RESULT,
                                'sid' => $redirectUniqueId,
                            ]);
                        }
                    }

                    // 記住我
                    $remember = Yii::$app->request->post('remember');
                    if ($remember == '1')
                    {
                        Yii::$app->session->set(static::className().'.remember', $inputSql);
                    }
                    else
                    {
                        Yii::$app->session->remove(static::className().'.remember');
                    }

                    // 顯示查詢介面
                    return $this->controller->redirect([
                        'db' => $db,
                        'action' => static::ACTION_SQL,
                    ]);
                }
                if (Yii::$app->session->has(static::className().'.remember'))
                {
                    $inputSql = Yii::$app->session->get(static::className().'.remember');
                }
                else
                {
                    $inputSql = '';
                }
                return $this->controller->render($this->view.'sql', [
                    'model' => $model,
                    'action' => $action,
                    'inputSql' => $inputSql,
                    'linkCreate' => [ // 資料建立連結
                        'db' => $db,
                        'action' => static::ACTION_CREATE,
                        'tableName' => $tableName,
                    ],
                    'linkIndex' => [ // 資料表條列
                        'db' => $db,
                    ],
                ]);
                break;
            case static::ACTION_SQL_RESULT: // SQL 查詢結果
                if(!is_null($sid) && $this->handleCatch($sid) !== false)
                {
                    $userInput = $this->handleCatch($sid);
                }
                else
                {
                    Yii::$app->session->setFlash('warning', '未找到查詢結果或查詢結果已失效！'.date('Y-m-d H:i:s'));
                    return $this->controller->redirect([
                        'db' => $db,
                        'action' => static::ACTION_SQL,
                    ]);
                }

                if (!$model->isAllowedSelectSql($userInput)) {
                    throw new BadRequestHttpException(
                        '基於安全政策，SQL 編輯器僅允許 SELECT / SHOW / DESCRIBE / EXPLAIN 查詢。'
                    );
                }

                // 創建一個 SqlDataProvider 實例
                $dataProvider = new SqlDataProvider([
                    'sql' => $userInput,
                    'pagination' => Yii::$app->tablePag->getPagination(),
                ]);

                // 嘗試獲取結果集的第一筆記錄來確定列
                $columns = [];
                if ($dataProvider->getCount() > 0)
                {
                    $firstRow = current($dataProvider->getModels());
                    foreach (array_keys($firstRow) as $columnName)
                    {
                        $columns[] = [
                            'attribute' => $columnName,
                            'label' => $columnName,
                        ];
                    }
                }

                return $this->controller->render($this->view.'result', [
                    'action' => $action,
                    'sid' => $sid,
                    'title' => "自定義 SQL 查詢結果 (<code>{$sid}</code>)",
                    'columns' => $columns,
                    'dataProvider' => $dataProvider,
                    'addFirstColumn' => false,
                    'showSql' => $userInput,
                    'linkDeleteSearch' => [
                        'db' => $db,
                        'action' => static::ACTION_RESULT_DELETE,
                        'sid'=>$sid
                    ],
                    'linkNewSearch' => [
                        'db' => $db,
                        'action' => static::ACTION_SQL,
                        'sid'=>$sid
                    ],
                ]);
                break;
            case static::ACTION_SEARCH: // 查詢
                if(Yii::$app->request->isPost)
                {
                    $model->loadSearch(Yii::$app->request->post());
                    // 擷取網頁的唯一識別標籤
                    $redirectUniqueId = $_SERVER["UNIQUE_ID"];
                    // 設定查詢結果至暫存(catch)
                    $this->handleCatch($redirectUniqueId, $model->getAttributes());
                    // 導向資料查詢結果
                    return $this->controller->redirect([
                        'db' => $db,
                        'action' => static::ACTION_RESULT,
                        'tableName' => $tableName,
                        'sid' => $redirectUniqueId,
                    ]);
                }
                return $this->controller->render($this->view.'form', [
                    'model' => $model,
                    'action' => $action,
                    'linkCreate' => [ // 資料建立連結
                        'db' => $db,
                        'action' => static::ACTION_CREATE,
                        'tableName' => $tableName,
                    ],
                    'linkIndex' => [ // 資料表條列
                        'db' => $db,
                    ],
                ]);
                break;
            case static::ACTION_RESULT: // 查詢結果
                if(!is_null($sid) && $this->handleCatch($sid) !== false)
                {
                    $dataProvider = $model->search($this->handleCatch($sid));
                }
                else
                {
                    Yii::$app->session->setFlash('warning', '未找到查詢結果或查詢結果已失效！'.date('Y-m-d H:i:s'));
                    return $this->controller->redirect([
                        'db' => $db,
                        'action' => static::ACTION_SEARCH,
                        'tableName' => $tableName,
                    ]);
                }

                $tableSchema = $model->getTableSchema();
                $columns = [];
                foreach($tableSchema->columns as $columnName => $tableSchemaColumn)
                {
                    $columns[] = [
                        'attribute' => $columnName,
                        'value' => function ($model, $key, $index, $column) {
                            if (is_null($model->{$column->attribute}))
                            {
                                return '-';
                            }
                            return $model->{$column->attribute};
                        },
                        'headerOptions' => ['class' => 'align-middle text-center'],
                        'contentOptions'=> ['class' => 'align-middle text-center text-monospace'],
                    ];
                }

                /** @var \yii\db\ActiveQuery $query */
                $query = $dataProvider->query;

                return $this->controller->render($this->view.'result', [
                    'action' => $action,
                    'sid' => $sid,
                    'title' => "{$tableSchema->name} 查詢結果 (<code>{$sid}</code>)",
                    'columns' => $columns,
                    'dataProvider' => $dataProvider,
                    'addFirstColumn' => true,
                    'showSql' => $query->createCommand()->getRawSql(),
                    'linkDeleteSearch' => [
                        'db' => $db,
                        'action' => static::ACTION_RESULT_DELETE,
                        'tableName' => $tableName,
                        'sid'=>$sid
                    ],
                    'linkNewSearch' => [
                        'db' => $db,
                        'action' => static::ACTION_SEARCH,
                        'tableName' => $tableName,
                        'sid'=>$sid
                    ],
                    'linkUpdate' => [
                        'db' => $db,
                        'action' => static::ACTION_UPDATE,
                        'tableName' => $tableName,
                        'sid'=>$sid
                    ],
                ]);
                break;
            case static::ACTION_RESULT_DELETE: // 刪除查詢結果
                if (Yii::$app->request->isPost)
                {
                    // 取得資料
                    if(!is_null($sid) && $this->handleCatch($sid) !== false)
                    {
                        // 刪除查詢結果
                        $this->handleCatch($sid, null, true);
                        // 提示訊息: 查詢結果刪除成功！
                        Yii::$app->session->setFlash('success', '查詢結果刪除成功！'.date('Y-m-d H:i:s'));
                        // 清除 sid 參數
                        $sid = null;
                        // 設定動作
                        $action = static::ACTION_SEARCH;
                    }
                    else
                    {
                        // 提示訊息: 未找到查詢結果或傳遞參數錯誤，未執行刪除！
                        Yii::$app->session->setFlash('warning', '未找到查詢結果或傳遞參數錯誤，未執行刪除！'.date('Y-m-d H:i:s'));
                        // 導向回查詢結果
                        $action = static::ACTION_RESULT;
                    }
                }
                else
                {
                    // 提示訊息: 資料刪除只能使用POST。
                    Yii::$app->session->setFlash('warning', '刪除查詢結果只能使用POST。'.date('Y-m-d H:i:s'));
                    // 導向回查詢結果
                    $action = static::ACTION_RESULT;
                }
                // 當沒有交代資料表則判定為 SQL
                if (is_null($tableName) || trim($tableName) == '')
                {
                    $action = static::ACTION_SQL;
                }
                // 導向查詢畫面
                return $this->controller->redirect([
                    'db' => $db,
                    'action' => $action,
                    'tableName' => $tableName,
                    'sid' => $sid,
                ]);
                break;
            case static::ACTION_CREATE: // 資料建立
                SaDbTools::assertWritable();
                // 定義情境
                $model->scenario = $model::SCENARIO_CREATE;
                // 處理表單
                if (Yii::$app->request->isPost)
                {
                    if ($model->load(Yii::$app->request->post()) && $model->save())
                    {
                        // 提示訊息: 資料建立成功！
                        Yii::$app->session->setFlash('success', '資料建立成功！'.date('Y-m-d H:i:s'));
                        return $this->controller->redirect([
                            'db' => $db,
                            'action' => static::ACTION_UPDATE,
                            'tableName' => $tableName,
                            'sid' => $sid,
                            'id' => $model->getPrimaryKey(),
                        ]);
                    }
                }
                // 資料新增畫面
                return $this->controller->render($this->view.'form', [
                    'action' => $action,
                    'model' => $model,
                    'linkSearch' => [ // 查詢連結
                        'db' => $db,
                        'action' => static::ACTION_SEARCH,
                        'tableName' => $tableName,
                    ],
                    'linkResult' => is_null($sid)?false:[ // 查詢結果連結
                        'db' => $db,
                        'action' => static::ACTION_RESULT,
                        'tableName' => $tableName,
                        'sid' => $sid,
                    ],
                ]);
                break;
            case static::ACTION_UPDATE: // 資料修改
                SaDbTools::assertWritable();
                // 沒有 id
                if (is_null($id))
                {
                    // 提示訊息: 未傳遞 id 參數！
                    Yii::$app->session->setFlash('warning', '未傳遞 id 參數！'.date('Y-m-d H:i:s'));
                    if(!is_null($sid))
                    {
                        // 有查詢結果就導向查詢結果
                        return $this->controller->redirect([
                            'db' => $db,
                            'action' => static::ACTION_RESULT,
                            'tableName' => $tableName,
                            'sid' => $sid,
                        ]);
                    }
                    // 沒查詢結果就導向查詢
                    return $this->controller->redirect([
                        'db' => $db,
                        'action' => static::ACTION_SEARCH,
                        'tableName' => $tableName,
                    ]);
                }
                // 取得資料
                if (($updateModel = $model->findModel($id)) === null)
                {
                    throw new NotFoundHttpException('找不到資料。');
                }
                // 定義情境
                $updateModel->scenario = $model::SCENARIO_UPDATE;
                // 處理表單
                if (Yii::$app->request->isPost)
                {
                    if ($updateModel->load(Yii::$app->request->post()) && $updateModel->save())
                    {
                        // 提示訊息: 資料修改成功！
                        Yii::$app->session->setFlash('success', '資料修改成功！'.date('Y-m-d H:i:s'));
                        // 返回資料編輯
                        return $this->controller->redirect([
                            'db' => $db,
                            'action' => static::ACTION_UPDATE,
                            'tableName' => $tableName,
                            'sid' => $sid,
                            'id' => $id,
                        ]);
                    }
                }
                // 資料編輯畫面
                return $this->controller->render($this->view.'form', [
                    // 'tableName' => $tableName,
                    'action' => $action,
                    'model' => $updateModel,
                    'linkSearch' => [ // 查詢連結
                        'db' => $db,
                        'action' => static::ACTION_SEARCH,
                        'tableName' => $tableName,
                    ],
                    'linkResult' => is_null($sid)?false:[ // 查詢結果連結
                        'db' => $db,
                        'action' => static::ACTION_RESULT,
                        'tableName' => $tableName,
                        'sid' => $sid,
                    ],
                    'linkDelete' => [ // 資料刪除連結
                        'db' => $db,
                        'action' => static::ACTION_DELETE,
                        'tableName' => $tableName,
                        'sid' => $sid,
                        'id' => $id,
                    ],
                    'linkCreate' => [ // 資料建立連結
                        'db' => $db,
                        'action' => static::ACTION_CREATE,
                        'tableName' => $tableName,
                    ],
                ]);
                break;
            case static::ACTION_DELETE: // 資料刪除
                SaDbTools::assertWritable();
                if (Yii::$app->request->isPost)
                {
                    // 取得資料
                    if (($updateModel = $model::findOne($id)) === null) {
                        throw new NotFoundHttpException('找不到資料。');
                    }
                    // 刪除指定資料
                    $updateModel->delete();
                    // 提示訊息: 資料刪除成功
                    Yii::$app->session->setFlash('success', '資料刪除成功！'.date('Y-m-d H:i:s'));
                    // 導向回去
                    if(!is_null($sid))
                    {
                        // 有查詢結果就導向查詢結果
                        return $this->controller->redirect([
                            'db' => $db,
                            'action' => static::ACTION_RESULT,
                            'tableName' => $tableName,
                            'sid' => $sid,
                        ]);
                    }
                    // 沒查詢結果就導向查詢
                    return $this->controller->redirect([
                        'db' => $db,
                        'action' => static::ACTION_SEARCH,
                        'tableName' => $tableName,
                    ]);
                }
                else
                {
                    // 提示訊息: 資料刪除只能使用POST。
                    Yii::$app->session->setFlash('warning', '資料刪除只能使用POST。'.date('Y-m-d H:i:s'));
                    // 導向回資料編輯
                    return $this->controller->redirect([
                        'db' => $db,
                        'action' => static::ACTION_UPDATE,
                        'tableName' => $tableName,
                        'sid' => $sid,
                        'id' => $id,
                    ]);
                }
                break;
        }

        /** @var \yii\db\Query $query Tables基礎查詢語法 */
        $query = $model->getTablesQuery($db);
        $searchModel = new DataTableModifierSearch();
        $dataProvider = $searchModel->search($query, Yii::$app->request->queryParams);

        // 沒上一頁，返回首頁
        return $this->controller->render($this->view.'index', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'action' => static::ACTION_SEARCH,
            'linkSQL' => [ // 資料SQL連結
                'db' => $db,
                'action' => static::ACTION_SQL,
            ],
            'linkSearch' => [ // 查詢連結
                'db' => $db,
                'action' => static::ACTION_SEARCH,
                'tableName' => $tableName,
            ],
        ]);
    }

    /**
     * 快取處理
     *
     * @param string $id 鍵值
     * @param mixed $value 傳入外部定義的方法之參數
     * @param bool $delete 是否刪除
     *
     * @return mixed
     */
    protected function handleCatch($id, $value=null, $delete=false)
    {
        if (is_null($id) || trim($id) == '')
        {
            throw new Exception('未傳遞暫存所需 id 參數！');
        }
        else
        {
            $key = [
                CacheDataInterface::ACTION_DATA_TABLE_MODIFIER_SEARCH,
                'id' => $id
            ];
        }
        $cache = Yii::$app->cache;
        if (!is_null($value))
        {
            $cache->set($key, $value, $this->searchDependency);
            return true;
        }
        else
        {
            if ($delete)
            {
                if ($cache->exists($key))
                {
                    $cache->delete($key);
                    return true;
                }
                return false;
            }
            else
            {
                if ($cache->exists($key))
                {
                    return $cache->get($key);
                }
                return false;
            }
        }
        throw new Exception('快取函式異常！');
    }

    /**
     * 存取事件
     *
     * @param string $fn 存取外部定義的方法
     * @param array $attr 傳入外部定義的方法之參數
     *
     * @return mixed
     */
    protected function callEvent($fn, $attr)
    {
        if(!is_null($this->handleEvent) && method_exists($this->handleEvent, $fn))
        {
            return call_user_func_array([$this->handleEvent, $fn], $attr);
        }
    }
}