<?php
namespace app\models;

use Yii;
use yii\db\Schema;
use yii\base\BaseObject;
use yii\helpers\Inflector;

/**
 * 自動生成資料表結構規則
 */
class TableSchemaRules extends BaseObject
{
    /**
     * @var true $required 是否需要必填選項
     */
    public $required = true;
    /**
     * @var true $foreignKeys 是否需要關聯鍵選項
     */
    public $foreignKeys = true;
    /**
     * @var true $foreignKeys 是否使用萬用關聯檢查選項
     */
    public $universalForeignKeyExist = false;
    /**
     * @var string
     */
    public $tableName = '';
    /**
     * @var string
     */
    public $db = 'db';
    /**
     * @var bool
     */
    public $standardizeCapitals = false;
    /**
     * @var bool
     */
    public $singularize = false;

    /**
     * @var string[]|null
     */
    protected $tableNames;
    /**
     * @var string[]
     */
    protected $classNames = [];

    /**
     * Returns the database connection as specified by [[db]].
     *
     * @return Connection|null database connection instance
     */
    protected function getDbConnection()
    {
        return Yii::$app->get($this->db, false);
    }

    /**
     * 為搜索模型生成驗證規則。
     *
     * @param \yii\db\TableSchema $table 資料表結構
     * @param string|array $on 情境
     * @param string $output 輸出資料類型(array|string)
     *
     * @return array 生成的驗證規則
     */
    public function generateSearchRules($table, $on = null, $output = 'array')
    {
        // if ($table === false) {
        //     return ["[['" . implode("', '", $this->getColumnNames()) . "'], 'safe']"];
        // }
        $types = [];
        foreach ($table->columns as $column)
        {
            switch ($column->type)
            {
                case Schema::TYPE_TINYINT:
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                    $types['integer'][] = $column->name;
                    break;
                case Schema::TYPE_BOOLEAN:
                    $types['boolean'][] = $column->name;
                    break;
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DOUBLE:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $types['number'][] = $column->name;
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                default:
                    $types['safe'][] = $column->name;
                    break;
            }
        }

        if (is_null($on))
        {
            $onStr = '';
        }
        else
        {
            if (is_array($on))
            {
                $onStr = ', \'on\' => [\'' . implode("', '", $on) . '\']';
            }
            else if (is_string($on))
            {
                $onStr = ', \'on\' => \'$on\'';
            }
        }
        $rulesStr = [];
        $rulesAry = [];
        foreach ($types as $type => $columns)
        {
            $rulesStr[] = "[['" . implode("', '", $columns) . "'], '$type'$onStr]";
            $rulesAry[] = [$columns, $type];
        }

        if ($output == 'string')
        {
            return $rulesStr;
        }
        else if ($output == 'array')
        {
            if (!is_null($on))
            {
                foreach ($rulesAry as $k => &$v)
                {
                    $v['on'] = $on;
                }
            }
            return $rulesAry;
        }
        return [
            'string' => $rulesStr,
            'array' => $rulesAry,
        ];
    }

    /**
     * 為指定表生成驗證規則。
     *
     * @param \yii\db\TableSchema $table 資料表結構
     * @param string|array $on 情境
     * @param string $output 輸出資料類型(array|string)
     *
     * @return array 生成的驗證規則
     */
    public function generateRules($table, $on=null, $output='array')
    {
        $db = $this->getDbConnection();
        if (is_null($on))
        {
            $onStr = '';
        }
        else
        {
            if (is_array($on))
            {
                $onStr = ', \'on\' => [\'' . implode("', '", $on) . '\']';
            }
            else if (is_string($on))
            {
                $onStr = ", 'on' => '$on'";
            }
        }

        $types = [];
        $lengths = [];
        foreach ($table->columns as $column)
        {
            if ($column->autoIncrement)
            {
                continue;
            }
            if ($this->required)
            {
                if (!$column->allowNull && $column->defaultValue === null)
                {
                    $types['required'][] = $column->name;
                }
            }
            switch ($column->type)
            {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                case Schema::TYPE_TINYINT:
                    $types['integer'][] = $column->name;
                    break;
                case Schema::TYPE_BOOLEAN:
                    $types['boolean'][] = $column->name;
                    break;
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DOUBLE:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $types['number'][] = $column->name;
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                case Schema::TYPE_JSON:
                    $types['safe'][] = $column->name;
                    break;
                default: // strings
                    if ($column->size > 0)
                    {
                        $lengths[$column->size][] = $column->name;
                    }
                    else
                    {
                        $types['string'][] = $column->name;
                    }
                    break;
            }
        }
        $rulesStr = [];
        $rulesAry = [];
        $driverName = $db->driverName;
        foreach ($types as $type => $columns)
        {
            if ($driverName === 'pgsql' && $type === 'integer')
            {
                $rulesStr[] = "[['" . implode("', '", $columns) . "'], 'default', 'value' => null$onStr]";
                $rulesAry[] = [$columns, 'default', 'value' => null];
            }
            $rulesStr[] = "[['" . implode("', '", $columns) . "'], '$type'$onStr]";
            $rulesAry[] = [$columns, $type];
        }
        foreach ($lengths as $length => $columns)
        {
            $rulesStr[] = "[['" . implode("', '", $columns) . "'], 'string', 'max' => $length$onStr]";
            $rulesAry[] = [$columns, 'string', 'max' => $length];
        }

        // 唯一索引規則
        try
        {
            $uniqueIndexes = array_merge($db->getSchema()->findUniqueIndexes($table), [$table->primaryKey]);
            $uniqueIndexes = array_unique($uniqueIndexes, SORT_REGULAR);
            foreach ($uniqueIndexes as $uniqueColumns)
            {
                // 避免驗證自動增量列
                if (!$this->isColumnAutoIncremental($table, $uniqueColumns))
                {
                    $attributesCount = count($uniqueColumns);

                    if ($attributesCount === 1)
                    {
                        $rulesStr[] = "[['" . $uniqueColumns[0] . "'], 'unique'$onStr]";
                        $rulesAry[] = [[$uniqueColumns[0]], 'unique'];
                    }
                    elseif ($attributesCount > 1)
                    {
                        $columnsList = implode("', '", $uniqueColumns);
                        $rulesStr[] = "[['$columnsList'], 'unique', 'targetAttribute' => ['$columnsList']$onStr]";
                        $rulesAry[] = [$uniqueColumns, 'unique', 'targetAttribute' => $uniqueColumns];
                    }
                }
            }
        }
        catch (yii\base\NotSupportedException $e)
        {
            // 不支持唯一索引信息...什麼也不做
        }

        // 外鍵的存在規則
        if($this->foreignKeys)
        {
            foreach ($table->foreignKeys as $refs)
            {
                $refTable = $refs[0];
                $refTableSchema = $db->getTableSchema($refTable);
                if ($refTableSchema === null)
                {
                    // 外鍵可以指向不存在的表: https://github.com/yiisoft/yii2-gii/issues/34
                    continue;
                }
                unset($refs[0]);
                $attributes = join("', '", array_keys($refs));
                $targetAttributes = [];
                foreach ($refs as $key => $value)
                {
                    $targetAttributes[] = "'$key' => '$value'";
                }
                $targetAttributes = join(', ', $targetAttributes);

                if($this->universalForeignKeyExist)
                {
                    unset($refs[0]);
                    $attributes = join("', '", array_keys($refs));
                    $rulesStr[] = "[['$attributes'], 'validateExist', 'skipOnError' => true, 'current'=>['table' => $refTable, 'targetAttribute' => [$targetAttributes]]$onStr]";
                    $rulesAry[] = [array_keys($refs), 'validateExist', 'skipOnError' => true, 'current'=>['table' => $refTable, 'targetAttribute' => $refs]];
                }
                else
                {
                    $refClassName = $this->generateClassName($refTable);
                    $targetClassName = $refClassName::className();
                    $rulesStr[] = "[['$attributes'], 'exist', 'skipOnError' => true, 'targetClass' => $targetClassName::className(), 'targetAttribute' => [$targetAttributes]$onStr]";
                    $rulesAry[] = [array_keys($refs), 'exist', 'skipOnError' => true, 'targetClass' => $refClassName::className(), 'targetAttribute' => $refs];
                }
            }
        }

        if ($output == 'string')
        {
            return $rulesStr;
        }
        else if ($output == 'array')
        {
            if (!is_null($on))
            {
                foreach ($rulesAry as $k => &$v)
                {
                    $v['on'] = $on;
                }
            }
            return $rulesAry;
        }
        return [
            'string' => $rulesStr,
            'array' => $rulesAry,
        ];
    }

    /**
     * 從指定的表名生成類名。
     *
     * @param string $tableName 表名（可能包含架構前綴）
     * @param bool $useSchemaName 模式名稱是否應該包含在類名稱中（如果存在）
     *
     * @return string 生成的類名
     */
    protected function generateClassName($tableName, $useSchemaName = null)
    {
        if (!empty($this->classNames[$tableName]))
        {
            return $this->classNames[$tableName];
        }

        $schemaName = '';
        $fullTableName = $tableName;
        if (($pos = strrpos($tableName, '.')) !== false)
        {
            if (($useSchemaName === null && $this->useSchemaName) || $useSchemaName)
            {
                $schemaName = substr($tableName, 0, $pos) . '_';
            }
            $tableName = substr($tableName, $pos + 1);
        }

        $db = $this->getDbConnection();
        $patterns = [];
        $patterns[] = "/^{$db->tablePrefix}(.*?)$/";
        $patterns[] = "/^(.*?){$db->tablePrefix}$/";
        if (strpos($this->tableName, '*') !== false)
        {
            $pattern = $this->tableName;
            if (($pos = strrpos($pattern, '.')) !== false)
            {
                $pattern = substr($pattern, $pos + 1);
            }
            $patterns[] = '/^' . str_replace('*', '(\w+)', $pattern) . '$/';
        }
        $className = $tableName;
        foreach ($patterns as $pattern)
        {
            if (preg_match($pattern, $tableName, $matches))
            {
                $className = $matches[1];
                break;
            }
        }

        if ($this->standardizeCapitals)
        {
            $schemaName = ctype_upper(preg_replace('/[_-]/', '', $schemaName)) ? strtolower($schemaName) : $schemaName;
            $className = ctype_upper(preg_replace('/[_-]/', '', $className)) ? strtolower($className) : $className;
            $this->classNames[$fullTableName] = Inflector::camelize(Inflector::camel2words($schemaName . $className));
        }
        else
        {
            $this->classNames[$fullTableName] = Inflector::id2camel($schemaName . $className, '_');
        }

        if ($this->singularize)
        {
            $this->classNames[$fullTableName] = Inflector::singularize($this->classNames[$fullTableName]);
        }

        return $this->classNames[$fullTableName];
    }

    /**
     * 檢查是否有任何指定的列是自動增量的。
     *
     * @param \yii\db\TableSchema $table 資料表結構
     * @param array $columns 檢查 autoIncrement 屬性的列
     *
     * @return bool 是否有任何指定的列是自動增量的。
     */
    protected function isColumnAutoIncremental($table, $columns)
    {
        foreach ($columns as $column)
        {
            if (isset($table->columns[$column]) && $table->columns[$column]->autoIncrement)
            {
                return true;
            }
        }

        return false;
    }
}
