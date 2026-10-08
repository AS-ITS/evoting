<?php
namespace app\components\helper;

/**
 * 從 CSV 文件中解析和檢索數據。 將數據導出為 CSV。
 * Parse and retrieve data from CSV files. Export data to CSV.
 *
 * @link github https://github.com/shuchkin/simplecsv
 * @author Sergey Shuchkin sergey.shuchkin@gmail.com
 */
class CsvHelper
{
    /** @var string $_delimiter CSV 分隔符號(預設:auto) */
    private $_delimiter;
    /** @var string $_enclosure CSV 引號(預設:auto) */
    private $_enclosure;
    /** @var string $_linebreak CSV 換行符號(預設:auto) */
    private $_linebreak;

    /** @var string $_csv CSV 檔案內容 */
    private $_csv = '';

    /**
     * Returns the fully qualified name of this class.
     * @return string the fully qualified name of this class.
     * @deprecated since 2.0.14. On PHP >=5.5, use `::class` instead.
     */
    public static function className()
    {
        return get_called_class();
    }

    /**
     * 處理 CSV 匯入
     *
     * @param string $filename_or_data 檔名或檔案內容(字串)
     * @param boolean $is_data 傳入的是否是檔案內容(預設:否)
     * @param string $delimiter CSV 分隔符號(預設:auto)
     * @param string $enclosure CSV 引號(預設:auto)
     * @param string $linebreak CSV 換行符號(預設:auto)
     *
     * @return array
     */
    public static function import($filename_or_data, $is_data = false, $delimiter = 'auto', $enclosure = 'auto', $linebreak = 'auto')
    {
        $csv = new static($delimiter, $enclosure, $linebreak);
        return $csv->toArray($filename_or_data, $is_data);
    }

    /**
     * 處理 CSV 匯出
     *
     * @param array $items 要匯入的資料
     * @param string $delimiter CSV 分隔符號(預設:,)
     * @param string $enclosure CSV 引號(預設:")
     * @param string $linebreak CSV 換行符號(預設:\r\n)
     *
     * @return string CSV檔案內容
     */
    public static function export($items, $delimiter = ',', $enclosure = '"', $linebreak = "\r\n")
    {
        $csv = new static($delimiter, $enclosure, $linebreak);
        return $csv->fromArray($items);
    }

    /**
     * 建構函數
     *
     * @param string $delimiter CSV 分隔符號(預設:auto)
     * @param string $enclosure CSV 引號(預設:auto)
     * @param string $linebreak CSV 換行符號(預設:auto)
     */
    public function __construct($delimiter = 'auto', $enclosure = 'auto', $linebreak = 'auto')
    {
        $this->_delimiter = $delimiter;
        $this->_enclosure = $enclosure;
        $this->_linebreak = $linebreak;
    }

    /**
     * 設定的 CSV 分隔符號
     *
     * @param boolean|string $set 設定之 CSV 分隔符號
     *
     * @return boolean|string 取得回傳 string，設定回傳 boolean
     */
    public function delimiter($set = false)
    {
        if ($set !== false)
        {
            return $this->_delimiter = $set;
        }
        if ($this->_delimiter === 'auto')
        {
            // detect delimiter 檢測分隔符
            if (strpos($this->_csv, $this->_enclosure . ',') !== false)
            {
                $this->_delimiter = ',';
            }
            else if (strpos($this->_csv, $this->_enclosure . "\t") !== false)
            {
                $this->_delimiter = "\t";
            }
            else if (strpos($this->_csv, $this->_enclosure . ';') !== false)
            {
                $this->_delimiter = ';';
            }
            else if (strpos($this->_csv, ',') !== false)
            {
                $this->_delimiter = ',';
            }
            else if (strpos($this->_csv, "\t") !== false)
            {
                $this->_delimiter = "\t";
            }
            else if (strpos($this->_csv, ';') !== false)
            {
                $this->_delimiter = ';';
            }
            else
            {
                $this->_delimiter = ',';
            }
        }
        return $this->_delimiter;
    }

    /**
     * 設定的 CSV 引號
     *
     * @param boolean|string $set 設定之 CSV 引號
     *
     * @return boolean|string 取得回傳 string，設定回傳 boolean
     */
    public function enclosure($set = false)
    {
        if ($set !== false)
        {
            return $this->_enclosure = $set;
        }
        if ($this->_enclosure === 'auto')
        {
            // detect quot 檢測引號
            if (strpos($this->_csv, '"') !== false)
            {
                $this->_enclosure = '"';
            }
            else if (strpos($this->_csv, "'") !== false)
            {
                $this->_enclosure = "'";
            }
            else
            {
                $this->_enclosure = '"';
            }
        }
        return $this->_enclosure;
    }

    /**
     * 設定的 CSV 換行符號
     *
     * @param boolean|string $set 設定之 CSV 換行符號
     *
     * @return boolean|string 取得回傳 string，設定回傳 boolean
     */
    public function linebreak($set = false)
    {
        if ($set !== false)
        {
            return $this->_linebreak = $set;
        }
        if ($this->_linebreak === 'auto')
        {
            if (strpos($this->_csv, "\r\n") !== false)
            {
                $this->_linebreak = "\r\n";
            }
            else if (strpos($this->_csv, "\n") !== false)
            {
                $this->_linebreak = "\n";
            }
            else if (strpos($this->_csv, "\r") !== false)
            {
                $this->_linebreak = "\r";
            }
            else
            {
                $this->_linebreak = "\r\n";
            }
        }
        return $this->_linebreak;
    }

    /**
     * 處理 CSV 轉 Array
     *
     * @param string $filename 檔名或檔案內容(字串)
     * @param boolean $is_csv_content 是否為檔案內容(預設:否)
     *
     * @return array 根據 CSV 轉換的 Array
     */
    public function toArray($filename, $is_csv_content = false)
    {

        $this->_csv = $is_csv_content ? $filename : file_get_contents($filename);

        $CSV_LINEBREAK = $this->linebreak();
        $CSV_ENCLOSURE = $this->enclosure();
        $CSV_DELIMITER = $this->delimiter();


        $r = array();
        $cnt = strlen($this->_csv);

        $esc = false;
        $i = $k = $n = 0;
        $r[$k][$n] = '';

        while ($i < $cnt)
        {
            $ch = $this->_csv[$i];
            $chch = ($i < $cnt - 1) ? $ch . $this->_csv[$i + 1] : $ch;

            if ($ch === $CSV_LINEBREAK)
            {
                if ($esc)
                {
                    $r[$k][$n] .= $ch;
                }
                else
                {
                    $k++;
                    $n = 0;
                    $esc = false;
                    $r[$k][$n] = '';
                }
            }
            else if ($chch === $CSV_LINEBREAK)
            {
                if ($esc)
                {
                    $r[$k][$n] .= $chch;
                }
                else
                {
                    $k++;
                    $n = 0;
                    $esc = false;
                    $r[$k][$n] = '';
                }
                $i++;
            }
            else if ($ch === $CSV_DELIMITER)
            {
                if ($esc)
                {
                    $r[$k][$n] .= $ch;
                }
                else
                {
                    $n++;
                    $r[$k][$n] = '';
                    $esc = false;
                }
            }
            else if ($chch === $CSV_ENCLOSURE . $CSV_ENCLOSURE && $esc)
            {
                $r[$k][$n] .= $CSV_ENCLOSURE;
                $i++;
            }
            elseif ($ch === $CSV_ENCLOSURE)
            {

                $esc = !$esc;
            }
            else
            {
                $r[$k][$n] .= $ch;
            }
            $i++;
        }
        return $r;
    }

    /**
     * 處理 Array 轉 CSV
     *
     * @param array $items
     *
     * @return string CSV檔案內容
     */
    public function fromArray($items)
    {

        if (!is_array($items))
        {
            trigger_error('CSV::export array required', E_USER_WARNING);
            return false;
        }

        $CSV_DELIMITER = $this->delimiter();
        $CSV_ENCLOSURE = $this->enclosure();
        $CSV_LINEBREAK = $this->linebreak();

        $result = '';
        foreach ($items as $i)
        {
            $line = '';

            foreach ($i as $v)
            {
                if (strpos($v, $CSV_ENCLOSURE) !== false)
                {
                    $v = str_replace($CSV_ENCLOSURE, $CSV_ENCLOSURE . $CSV_ENCLOSURE, $v);
                }

                if ((strpos($v, $CSV_DELIMITER) !== false)
                    || (strpos($v, $CSV_ENCLOSURE) !== false)
                    || (strpos($v, $CSV_LINEBREAK) !== false)
                )
                {
                    $v = $CSV_ENCLOSURE . $v . $CSV_ENCLOSURE;
                }
                $line .= $line ? $CSV_DELIMITER . $v : $v;
            }
            $result .= $result ? $CSV_LINEBREAK . $line : $line;
        }

        return $result;
    }
}
