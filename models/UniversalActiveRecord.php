<?php
namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\HttpException;
use app\components\helper\ArrayHelper;

/**
 * 萬用資料表
 */
class UniversalActiveRecord extends \yii\db\ActiveRecord
{
    /**
     * 模式: 查詢
     * @var string
     */
    const SCENARIO_SEARCH = 'search';

    /**
     * 模式: 創建
     * @var string
     */
    const SCENARIO_CREATE = 'create';

    /**
     * 模式: 更新
     * @var string
     */
    const SCENARIO_UPDATE = 'update';

    /** @var bool $ruleRequired 產生的規則是否必填 */
    public $ruleRequired = false;
    /** @var array $ruleAdd 增加至預設產生規則中的規則 */
    public $ruleAdd = [];
    /** @var array $attrLabels 欄位及欄位說明 */
    public $attrLabels = [];

    /** @var string $dbName 資料庫名稱 */
    public static $dbName = 'db';
    /** @var string $tableName 資料表名稱 */
    public static $tableName;

    /**
     * {@inheritdoc}
     */
    public static function getDb()
    {
        return Yii::$app->get(self::$dbName);
    }

    /**
     * {@inheritdoc}
     */
    public static function setDb($name)
    {
        if(is_null($name))
        {
            // TODO: 建立自定義連線
            // $connection = new \yii\db\Connection([
            //     'dsn' => $dsn,
            //     'username' => $username,
            //     'password' => $password,
            // ]);
            // $connection->open();
        }
        else
        {
            if(!Yii::$app->has($name))
            {
                throw new HttpException(400, 'Yii::$app->'.$name.' 不存在');
            }
            $db = Yii::$app->get($name);
            if(!($db instanceof \yii\db\Connection))
            {
                throw new HttpException(400, 'Yii::$app->'.$name.' 無法建立有效的DB連線');
            }
            self::$dbName = $name;
        }
    }

    /**
     * 取得為 DB 連線的物件
     */
    public function getDbNames()
    {
        $componentsName = array_keys(Yii::$app->components);
        $dbNames = [];
        foreach($componentsName as $componentName)
        {
            try {
                $db = Yii::$app->get($componentName);
                if($db instanceof \yii\db\Connection)
                {
                    $dbInfo = ArrayHelper::matchPregAll(
                        '/^(.*):host=(.*?);dbname=(.*?)$/m',
                        $db->dsn,
                        true
                    );
                    $dbNames[$componentName] = "{$dbInfo[3]}: {$dbInfo[2]}";
                }
            } catch (\Exception $e) {
                Yii::error($e, __METHOD__);
            }
        }
        return $dbNames;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return self::$tableName;
    }

    /**
     * 設定資料表名稱
     *
     * @param string $name 資料表名稱
     *
     * @return bool 是否完成設定
     */
    public static function setTableName($name)
    {
        // 取得所有資料表欄位
        $tableNames = self::getDb()->getSchema()->getTableNames();

        // 判斷資料表名稱是否存在
        if(in_array($name, $tableNames))
        {
            self::$tableName = $name;
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        // 回傳所有規則
        return $this->genRules();
    }

    /**
     * 生成規則
     *
     * @return array 規則
     */
    protected function genRules()
    {
        // 建立修改組合
        $scenarioCreateOrUpdate = [static::SCENARIO_CREATE, static::SCENARIO_UPDATE];

        // 建立產生 RULE 物件，但不包含必填
        $tableSchemaRules = new TableSchemaRules([
            'db'=>self::$dbName,
            'required'=>$this->ruleRequired,
            'universalForeignKeyExist'=>true,
        ]);
        // 取得表格 Schema
        $schema = $this->getTableNameSchema();
        // 生成資料表規則
        $rules = $tableSchemaRules->generateRules($schema, $scenarioCreateOrUpdate);

        // 自行組合必填欄位
        $required = [];
        foreach($schema->columns as $column => $columnSchema)
        {
            // 為 PrimaryKey 或 (enumValues 且不能為 NULL)
            if($columnSchema->isPrimaryKey || (!$columnSchema->allowNull && !is_null($columnSchema->enumValues)))
            {
                $required[] = $column;
            }
        }

        $result = [
            [$required, 'required', 'on'=>$scenarioCreateOrUpdate],
        ];
        $result = array_merge($this->ruleAdd, $rules);

        // 回傳所有規則
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        if(empty($this->attrLabels))
        {
            $attrs = $this->attributes();
            return array_combine($attrs, $attrs);
        }
        return $this->attrLabels;
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validateExist($attribute, $params, $validator, $current, $view)
    {
        // TODO: 處理關聯規則，還不知道這個為何不作用？？？
        header('content-Type: text/plain; charset=utf-8');
        \yii\helpers\VarDumper::dump('validateExist', $depth=10, $highlight=false);echo "\n";
        \yii\helpers\VarDumper::dump($attribute, $depth=10, $highlight=false);echo "\n";
        \yii\helpers\VarDumper::dump($params, $depth=10, $highlight=false);echo "\n";
        \yii\helpers\VarDumper::dump($validator, $depth=10, $highlight=false);echo "\n";
        \yii\helpers\VarDumper::dump($current, $depth=10, $highlight=false);echo "\n";
        \yii\helpers\VarDumper::dump($view, $depth=10, $highlight=false);echo "\n";
        exit();
        if (!$this->hasErrors())
        {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Incorrect username or password.');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert))
        {
            return false;
        }

        /** @var null|array $tbColumns 資料表的欄位屬性 */
        $tbColumns = null;
        foreach ($this->activeAttributes() as $attribute)
        {
            if (is_string($this->$attribute) && trim($this->$attribute) == '')
            {
                if(is_null($tbColumns))
                {
                    // 取得資料表結構
                    $tbColumns = $this->getTableNameSchema()->columns;
                }
                if( $tbColumns[$attribute]->allowNull == false)
                {
                    if(is_null($tbColumns[$attribute]->defaultValue))
                    {
                        // 自動填寫資料表中不能為 NULL 之欄位
                        switch($tbColumns[$attribute]->type)
                        {
                            case 'integer':
                                $this->$attribute = 0;
                                break;
                            case 'char':
                            case 'string':
                                $this->$attribute = '';
                                break;
                            case 'date':
                                $this->$attribute = '0000-00-00';
                                break;
                            case 'datetime':
                                $this->$attribute = '0000-00-00 00:00:00';
                                break;
                            default:
                                $this->$attribute = '';
                                break;
                        }
                    }
                    else
                    {
                        $this->$attribute = $tbColumns[$attribute]->defaultValue;
                    }
                }
                else
                {
                    // 把空值轉換成NULL
                    $this->$attribute = NULL;
                }
            }
        }

        $dirtyAttributes = $this->getDirtyAttributes(null);
        // 處理僅更新者欄位變更
        if (count($dirtyAttributes) == 1 && array_key_exists('update_logname', $dirtyAttributes)) {
            $this->setAttribute('update_logname', $this->getOldAttribute('update_logname'));
            return true;
        }

        return true;
    }

    /**
     * 根據給定的主鍵返回數據模型。
     *
     * @param string $id 要加載的模型的 ID。
     * 如果模型有復合主鍵，
     * ID 必須是以逗號分隔的主鍵值的字符串。
     * 主鍵值的順序應遵循 `primaryKey()` 方法返回的順序型號。
     *
     * @return UniversalActiveRecord|null 找到的模型或沒找到的空
     */
    public function findModel($id)
    {
        $keys = $this->primaryKey();
        if (count($keys) > 1) {
            $values = explode(',', $id);
            if (count($keys) === count($values)) {
                $model = $this->findOne(array_combine($keys, $values));
            }
        } elseif ($id !== null) {
            $model = $this->findOne($id);
        }

        if (isset($model)) {
            return $model;
        }

        return null;
    }

    /**
     * 匯入查詢數據
     *
     * @param array $postData POST查詢表單
     *
     * @return void
     */
    public function loadSearch($postData=[])
    {
        $params = $postData[$this->formName()];
        $this->setAttributes($params, false);
    }

    /**
     * 建立查詢資料表數據
     *
     * @param string $db
     *
     * @return \yii\db\Query
     */
    public function getTablesQuery($db)
    {
        // 預設
        if(is_null($db))
        {
            $db = 'db';
        }

        /** @var \yii\db\Connection $dbConn */
        $dbConn = Yii::$app->{$db};

        // 抓出 db name
        $re = '/(\w+):host=([\d|.|\w]+);dbname=(\w+)/m';
        preg_match_all($re, $dbConn->dsn, $matches, PREG_SET_ORDER, 0);
        $dbName = $matches[0][3];

        // 查出所有資料表
        $query = (new \yii\db\Query())
            ->from('information_schema.TABLES')
            ->where(['TABLE_SCHEMA' => $dbName]);

        return $query;
    }

    /**
     * 取得數據庫中所有資料表名稱
     *
     * @return string[] 數據庫中的所有表名
     */
    public function getTableNames()
    {
        return $this->getDb()
            ->getSchema()
            ->getTableNames();
    }

    /**
     * 取得資料表的結構
     *
     * @param string $name 資料表名稱
     *
     * @return \yii\db\TableSchema 資料表結構
     */
    public function getTableNameSchema($name=null)
    {
        if(is_null($name))
        {
            $name = $this->tableName();
        }

        $tableSchema = $this->getDb()
            ->getSchema()
            ->getTableSchema($name);

        return $tableSchema;
    }

    /**
     * 建立查詢數據
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params=[])
    {
        $query = $this->find();

        $dataProvider = new ActiveDataProvider([
            'db' => $this->getDb(),
            'query' => $query,
            'pagination' => Yii::$app->tablePag->getPagination(),
            'sort' => [
                'enableMultiSort' => true,
            ],
        ]);

        // 將查詢表單的欄位填入物件
        $this->setAttributes($params, false);
        // header('content-Type: text/plain; charset=utf-8');
        // \yii\helpers\VarDumper::dump($this, $depth=10, $highlight=false);
        // exit();

        // 取得表格 Schema
        $schema = $this->getTableNameSchema();
        // header('content-Type: text/plain; charset=utf-8');
        // \yii\helpers\VarDumper::dump($schema, $depth=10, $highlight=false);
        // exit();

        foreach($schema->columns as $column => $columnSchema)
        {
            // 跳過空白的不處理
            if(is_null($this->{$column}) || trim($this->{$column}) == '')
            {
                continue;
            }
            if(count($orMatches = ArrayHelper::matchPregAll('/^\|(.+)/m', $this->{$column})) > 0)
            {
                $orMatches2 = ArrayHelper::matchPregAll('/(?:\\\\.|[^\\\\|])+/', $orMatches[0][1]);// 不分割反斜線
                $orMatches3 = ArrayHelper::getColumn($orMatches2, 0);

                $result = [];
                foreach($orMatches3 as $matche)
                {
                    $matche2 = str_replace('\|', '|', $matche);// 處理反斜線
                    $orMatches4 = ArrayHelper::matchPregAll('/^(?:`(\w+)`)?(.+)/m', $matche2);
                    $orColumn = $column;
                    if(count($orMatches4) > 0 && trim($orMatches4[0][1]) != '')
                    {
                        $orColumn = in_array($orMatches4[0][1], $this->attributes)?$orMatches4[0][1]:$column;
                    }
                    $orConditions = $this->conditionPreg($orColumn, $orMatches4[0][2], $schema);
                    // 加入指定條件
                    $result = array_merge($result, $orConditions);
                }
                if(count($result) > 0)
                {
                    array_unshift($result, 'OR');
                    $query->andWhere($result);
                }
            }
            // else if(count($andMatches = ArrayHelper::matchPregAll('/^&(.+)/m', $this->{$column})) > 0)
            // {
            //     $andMatches2 = ArrayHelper::matchPregAll('/(?:\\\\.|[^\\\\&])+/', $andMatches[0][1]);
            //     $andMatches3 = ArrayHelper::getColumn($andMatches2, 0);
            //     foreach($andMatches3 as $i => $matche)
            //     {
            //         $result[] = [$matche[1], $column, trim($matche[2])];
            //     }
            //     return $result;
            // }
            else
            {
                $conditions = $this->conditionPreg($column, null, $schema);
                foreach($conditions as $condition)
                {
                    $query->andWhere($condition);
                }
            }
        }

        return $dataProvider;
    }

    /**
     * Undocumented function
     *
     * @param string $column 欄位名稱
     * @param mixed $value 指定欄位數值
     * @param yii\db\TableSchema $schema 資料表結構
     *
     * @return array 所有條件
     */
    protected function conditionPreg($column, $value=null, $schema=null)
    {
        /** @var array $result 結果 */
        $result = [];

        if(is_null($value))
        {
            $value = $this->{$column};
        }

        // LIKE 為 `%` 開頭的字串
        $matches = ArrayHelper::matchPregAll('/^(%|!%)(.+)/m', $value);
        if(count($matches) > 0)
        {
            foreach($matches as $i => $matche)
            {
                if($matche[1] == '%')
                {
                    $result[] = ['LIKE', $column, $matche[2], false];
                }
                else if($matche[1] == '!%')
                {
                    $result[] = ['NOT LIKE', $column, $matche[2], false];
                }
            }
            return $result;
        }

        // REGEXP 為 `?` 開頭的字串
        $matches = ArrayHelper::matchPregAll('/^(\?|!\?)(.+)/m', $value);
        if(count($matches) > 0)
        {
            foreach($matches as $i => $matche)
            {
                if($matche[1] == '?')
                {
                    $result[] = ['REGEXP', $column, $matche[2]];
                }
                else if($matche[1] == '!?')
                {
                    $result[] = ['NOT REGEXP', $column, $matche[2]];
                }
            }
            return $result;
        }

        // (Schema為數字)大於小於等於不等於 為 `>` `>=` `<` `<=` 開頭的字串
        if(in_array($schema->columns[$column]->type, ['integer','date']))
        {
            // $value = '>+20
            //     >=20
            //     <50
            //     <=-20';
            $matches = ArrayHelper::matchPregAll('/^(>|>=|<|<=)((?:\+|-)?\d+)$/m', $value);
            if(count($matches) > 0)
            {
                foreach($matches as $i => $matche)
                {
                    $result[] = [$matche[1], $column, trim($matche[2])];
                }
                return $result;
            }
        }

        // 大於小於等於不等於 為 `=` `!=` 開頭的字串
        $matches = ArrayHelper::matchPregAll('/^(=|!=)(.+)?/m', $value);
        if(count($matches) > 0)
        {
            foreach($matches as $i => $matche)
            {
                if($matche[1] == '=')
                {
                    $result[] = [$column => isset($matche[2])?$matche[2]:''];
                }
                else if($matche[1] == '!=')
                {
                    $result[] = ['NOT', [$column => isset($matche[2])?$matche[2]:'']];
                }
            }
            return $result;
        }

        // (Schema為日期)between 為 `~` 開頭的字串
        if($schema->columns[$column]->type == 'date')
        {
            // $value = '~2023-01-01~2024-04-12
            //     ~2024~2025
            //     ~2024~2025-01
            //     ~2010-01~2015
            //     ~2012-12-15~2015-15
            //     ~2023-05~2028-03
            //     ~2023/10~2025/20
            //     ~2023/20~2025/30
            //     ~2000.11~2099/05
            //     ~2011-01~2050/03
            //     ~2023
            //     ~~2024
            //     ~0000~2023
            //     ~0000~';
            $matches = ArrayHelper::matchPregAll(
                '/^(~|!~)([0-2]\d{3})?(?:[-\/\.]([1-9]|0[1-9]|1[012])(?:[-\/\.](0[1-9]|[12][0-9]|3[01]))?)?(?:~([0-2]\d{3})(?:[-\/\.]([1-9]|0[1-9]|1[012])(?:[-\/\.](0[1-9]|[12][0-9]|3[01]))?)?)?$/m',
                $value
            );
            if(count($matches) > 0)
            {
                foreach($matches as $i => $matche)
                {
                    $startDate = ArrayHelper::strtr('{year}-{month}-{day}', [
                        'year' => isset($matche[2]) && trim($matche[2]) != ''?$matche[2]:'0000',
                        'month' => isset($matche[3]) && trim($matche[3]) != ''?$matche[3]:'01',
                        'day' => isset($matche[4]) && trim($matche[4]) != ''?$matche[4]:'01',
                    ]);
                    $endDate = ArrayHelper::strtr('{year}-{month}-{day}', [
                        'year' => isset($matche[5]) && trim($matche[5]) != ''?$matche[5]:'2999',
                        'month' => isset($matche[6]) && trim($matche[6]) != ''?$matche[6]:'12',
                        'day' => isset($matche[7]) && trim($matche[7]) != ''?$matche[7]:'31',
                    ]);
                    if($matche[1] == '~')
                    {
                        $result[] = ['between', $column, $startDate, $endDate];
                    }
                    else if($matche[1] == '!~')
                    {
                        $result[] = ['not between', $column, $startDate, $endDate];
                    }
                }
                return $result;
            }
        }
        // (Schema非日期)between 為 `~` 開頭的字串
        $matches = ArrayHelper::matchPregAll(
            '/^(~|!~)([+-]?\d+(?:[.]\d*)?|\w)~([+-]?\d+(?:[.]\d*)?|\w)/m',
            $value
        );
        if(count($matches) > 0)
        {
            foreach($matches as $i => $matche)
            {
                if($matche[1] == '~')
                {
                    $result[] = ['between', $column, trim($matche[2]), trim($matche[3])];
                }
                else if($matche[1] == '!~')
                {
                    $result[] = ['not between', $column, trim($matche[2]), trim($matche[3])];
                }
            }
            return $result;
        }

        // in 為 `,` 開頭的字串
        $matches = ArrayHelper::matchPregAll('/^(,|!,)(.+)/m', $value);
        if(count($matches) > 0)
        {
            foreach($matches as $i => $matche)
            {
                $matches2 = ArrayHelper::matchPregAll('/(?:\\\\.|[^\\\\,])+/', $matche[2]);
                $matches3 = ArrayHelper::getColumn($matches2, 0);
                foreach ($matches3 as &$matche3)
                {
                    if(trim($matche3) == '-')
                    {
                        $matche3 = null;
                    }
                    else if(trim($matche3) == '\-')
                    {
                        $matche3 = '-';
                    }
                }
                if($matche[1] == ',')
                {
                    $result[] = ['in', $column, $matches3];
                }
                else if($matche[1] == '!,')
                {
                    $result[] = ['not in', $column, $matches3];
                }
            }
            return $result;
        }

        // 若不符合上述規則，值不為空
        if(!is_null($value) && trim($value) != '')
        {
            $result[] = ['like', $column, $value];
        }

        return $result;
    }

    /**
     * 分割 SQL 語法
     *
     * @param string $string 可能多筆的SQL語法
     *
     * @return array 回傳切割好多筆SQL語法
     */
    public function splitSqlStatement($string)
    {
        $pattern = "/(;)(?=(?:[^'\"]|'[^']*'|\"[^\"]*\")*$)/";
        $splitArray = preg_split($pattern, $string);
        $result = [];
        foreach($splitArray as $sArray)
        {
            if(trim($sArray) != '')
            {
                $result[] = $sArray;
            }
        }
        return $result;
    }

    /**
     * 查詢或執行
     *
     * @param string $sql SQL 語法
     *
     * @return array|int `array`代表為查詢，`int`代表為執行
     */
    public function queryOrExecute($sql)
    {
        $executeArray = $this->getExecuteArray();
        $sqlType = $this->getQueryType($sql);
        if(in_array($sqlType, $executeArray))
        {
            return $this->getDb()
                ->createCommand($sql)
                ->execute();
        }
        else
        {
            return $this->getDb()
                ->createCommand($sql)
                ->queryAll();
            // 不支持該方式表格存取
            // return new ActiveDataProvider([
            //     'query' => static::getDb()->createCommand($sql),
            //     'pagination' => Yii::$app->tablePag->getPagination(),
            // ]);
        }
    }

    /**
     * 檢查是否同時含有兩種類型的語法
     *
     * @param string[] $sqls 多筆SQL語法
     * @param bool $returnNumber 返回類型的筆數
     *
     * @return bool|array 是否為多種類別，或多筆查詢
     */
    public function checkTwoTypeSql($sqls, $returnNumber=false)
    {
        $executeArray = $this->getExecuteArray();

        $typeExecute = 0;
        $typeQuery = 0;
        foreach($sqls as $sql)
        {
            $sqlType = $this->getQueryType($sql);
            if(in_array($sqlType, $executeArray))
            {
                $typeExecute += 1;
            }
            else
            {
                $typeQuery += 1;
            }
        }
        if($returnNumber)
        {
            return [
                'exec' => $typeExecute,
                'query' => $typeQuery,
            ];
        }
        if($typeExecute > 0 && $typeQuery > 0)
        {
            return true;
        }
        else if($typeQuery > 1)
        {
            return true;
        }
        return false;
    }

    /**
     * 執行語法的開頭類型
     *
     * @return array 屬於執行語法的開頭陣列
     */
    protected function getExecuteArray()
    {
        return [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER', 'CREATE',
            'REPLACE', 'GRANT', 'REVOKE', 'CALL', 'EXEC', 'EXECUTE', 'RENAME',
            'LOCK', 'UNLOCK', 'SET', 'HANDLER', 'PREPARE', 'DEALLOCATE',
        ];
    }

    /**
     * 驗證 SQL 編輯器輸入是否為允許的唯讀 SELECT
     *
     * @param string $sql
     * @return bool
     */
    public function isAllowedSelectSql(string $sql): bool
    {
        $normalized = ltrim($sql);
        if ($normalized === '') {
            return false;
        }

        $sqlType = $this->getQueryType($normalized);
        if ($sqlType !== 'SELECT' && $sqlType !== 'SHOW' && $sqlType !== 'DESCRIBE' && $sqlType !== 'EXPLAIN') {
            return false;
        }

        if (preg_match('/\b(INTO\s+(OUTFILE|DUMPFILE)|LOAD\s+FILE|LOAD_FILE\s*\(|LOAD\s+DATA|FOR\s+UPDATE|LOCK\s+IN\s+SHARE\s+MODE)/i', $normalized)) {
            return false;
        }

        return true;
    }

    /**
     * 返回數據庫查詢類型。
     *
     * @param string $timing 計時程序字符串
     *
     * @return string 查詢類型，例如選擇、插入、刪除等。
     * @see yii\debug\panels\DbPanel::getQueryType()
     */
    protected function getQueryType($timing)
    {
        $timing = ltrim($timing);
        $matches = ArrayHelper::matchPregAll('/^([a-zA-z]*)/', $timing)[0];

        return count($matches) ? mb_strtoupper($matches[0], 'utf8') : '';
    }
}