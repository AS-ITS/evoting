<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Json;
use app\components\helper\ArrayHelper;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * FormCsvFile 候選人檔案導入模組
 * 
 * @link https://www.php.net/manual/en/function.str-getcsv.php
 */
class FormCsvFile extends Model
{
    /** CSV 上傳大小上限（bytes） */
    const MAX_CSV_SIZE = 5242880;

    /** CSV 資料列上限（不含標題列） */
    const MAX_CSV_ROWS = 5000;

    /**
     * @var UploadedFile CSV 檔案
     */
    public $csvFile;

    public $dataCount = 0;

    protected $_file;
    protected $_field;

    /**
     * 導入模式
     */
    static public $genMode = 'csvfile';

    /**
     * 檔案編碼
     */
    static public $fileEncoding = ['ASCII', 'big5', 'UTF-8'];

    /**
     * CSV 欄位名稱對應資料表欄位(對照)
     */
    static public $FieldCP = [
        [
            'column' => '投票組別(中文)', 'columnE' => 'party',
            'desc' => '投票時的組別中文名稱，未分組請填【預設】',
            'fieldLimit' => '{party}',
        ],
        [
            'column' => '問題代碼', 'columnE' => 'questionID',
            'desc' => '投票時的問題代碼，請參考上方的問題代碼',
            'fieldLimit' => '{questionID}的"問題代碼"',
        ],
        [
            'column' => '工作地點', 'columnE' => 'jobLctn',
            'desc' => '此為"召集人投票"專用欄位',
            'fieldLimit' => '<kbd>不分地點</kbd> / <kbd>國內</kbd> / <kbd>國外</kbd>',
        ],
        [
            'column' => '單位代碼', 'columnE' => 'instCode',
            'desc' => '根據單位定義的代碼，詳細請參考上方的單位代碼',
            'fieldLimit' => '{instCode}的"單位代碼"',
        ],
        [
            'column' => '職稱代碼', 'columnE' => 'tCode',
            'desc' => '依照人事法規定義出來的代碼，詳細請參考上方的職稱代碼',
            'fieldLimit' => '{tCode}的"職稱代碼"',
        ],
        [
            'column' => '單位名稱', 'columnE' => 'instName',
            'desc' => '顯示給投票者參考的單位名稱，建議使用單位代碼中的單位名稱',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '單位名稱英', 'columnE' => 'instNameE',
            'desc' => '英文介面時顯示的單位名稱，使用單位代碼中的單位名稱，此欄位可不填將代預設翻譯',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '職稱', 'columnE' => 'title',
            'desc' => '顯示給投票者參考的職稱，建議使用職稱代碼中的職稱',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '職稱英', 'columnE' => 'titleE',
            'desc' => '英文介面時顯示的職稱，使用職稱代碼中的職稱，此欄位可不填將代預設翻譯',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '名稱', 'columnE' => 'Name',
            'desc' => '投票項目的名稱',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '名字', 'columnE' => 'Name',
            'desc' => '投票者的名字',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '名稱英', 'columnE' => 'NameE',
            'desc' => '英文介面時顯示投票項目的英文名稱',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '名字英', 'columnE' => 'NameE',
            'desc' => '英文介面時顯示投票者的英文名字',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '性別', 'columnE' => 'sex',
            'desc' => '投票者性別',
            'fieldLimit' => '<kbd>男</kbd> / <kbd>女</kbd>',
        ],
        [
            'column' => '背景顏色', 'columnE' => 'backgroundColor',
            'desc' => '該候選人表格列中背景顏色',
            'fieldLimit' => '* 色碼，ex:#FFFFFF',
        ],
        [
            'column' => '排序', 'columnE' => 'orderNum',
            'desc' => '此為自定義排序(此為首要排序欄位)',
            'fieldLimit' => '* 必須是數字',
        ],
        [
            'column' => '自定義欄位 1', 'columnE' => 'otherColA',
            'desc' => '自定義欄位',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '自定義欄位 1英', 'columnE' => 'otherColAE',
            'desc' => '英文介面時顯示自定義欄位',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '自定義欄位 2', 'columnE' => 'otherColB',
            'desc' => '自定義欄位',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '自定義欄位 2英', 'columnE' => 'otherColBE',
            'desc' => '英文介面時顯示自定義欄位',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '自定義欄位 3', 'columnE' => 'otherColC',
            'desc' => '自定義欄位',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '自定義欄位 3英', 'columnE' => 'otherColCE',
            'desc' => '英文介面時顯示自定義欄位',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '自定義欄位 4', 'columnE' => 'otherColD',
            'desc' => '自定義欄位',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '自定義欄位 4英', 'columnE' => 'otherColDE',
            'desc' => '英文介面時顯示自定義欄位',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '自定義欄位 5', 'columnE' => 'otherColE',
            'desc' => '自定義欄位',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '自定義欄位 5英', 'columnE' => 'otherColEE',
            'desc' => '英文介面時顯示自定義欄位',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '自定義欄位 6', 'columnE' => 'otherColF',
            'desc' => '自定義欄位',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '自定義欄位 6英', 'columnE' => 'otherColFE',
            'desc' => '英文介面時顯示自定義欄位',
            'fieldLimit' => '不限',
        ],
        [
            'column' => '關聯組別', 'columnE' => 'relateParty',
            'desc' => '給共同問題使用的，用於取得分組的人數',
            'fieldLimit' => '{partyNoN}',
        ],
        [
            'column' => '特殊榮譽', 'columnE' => 'specialHonor',
            'desc' => '選用標記（1 或空白）',
            'fieldLimit' => '1或空白',
        ],
    ];

    /**
     * 檔案匯入規則，僅許可CSV
     */
    public function rules()
    {
        return [
            [
                ['csvFile'],
                'file',
                'skipOnEmpty' => false,
                'extensions' => 'csv',
                'checkExtensionByMimeType' => false,
                'maxSize' => self::MAX_CSV_SIZE,
                'uploadRequired' => Yii::t('app', '請選擇要匯入的 CSV 檔案！'),
            ],
        ];
    }

    /**
     * 返回指定屬性的文本標籤
     * 
     * @param string $attribute the attribute name
     * @return string the attribute label
     */
    public function attributeLabels()
    {
        return [
            'csvFile' => '匯入 CSV 檔案',
        ];
    }
    
    /**
     * 使用PHPExcel套件處理匯入csv
     *
     * @param  string $voteID
     * @param  array $parties
     * @param  array $questions
     * @return bool
     */
    public function importData($voteID, $parties, $questions)
    {
        if (is_null($this->csvFile) || is_null($this->csvFile->tempName)) {
            return false;
        }

        $csvPath = $this->prepareCsvPath($this->csvFile->tempName);
        $FormCandiConfig = new FormCandiConfig;
        $CandiConfig = $FormCandiConfig->getConfigWithVoteID($voteID);
        if (is_null($CandiConfig)) {
            return false;
        }
        $header = [];
        $fields = self::$FieldCP;
        if($CandiConfig->Name == '1') {
            $exceptField = ['名稱', '名稱英', '工作地點'];
        }
        else {
            $exceptField = ['名字', '名字英', '工作地點'];
        }
        $headerKey = 1;
        foreach ($fields as $field) {
            if (!in_array($field['column'], $exceptField)) {
                $header[Coordinate::stringFromColumnIndex($headerKey).'1'] = $field['column'];
                $headerKey++;
            }
        }
        
        $objReader = IOFactory::createReader('Csv');
        $objPHPExcel = $objReader->load($csvPath);
        if ($csvPath !== $this->csvFile->tempName && is_file($csvPath)) {
            @unlink($csvPath);
        }
        $worksheet = $objPHPExcel->getActiveSheet();
        $dataRowCount = 0;

        // 候選人配置（載入後才需要）
        $divisionAry = array_flip($parties);
        // 問題: 鍵值 => 問題資料
        $questionAry = $questions;
        // 性別: 中文 => 鍵值
        $sexAry = array_flip(Yii::$app->params['ct.candi.sexAry']);
        // 建立資料
        $transaction = CandiData::getDb()->beginTransaction();

        foreach ($worksheet->getRowIterator() as $row) {

            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set

            if ($row->getRowIndex() > 1) {
                $dataRowCount++;
                if ($dataRowCount > self::MAX_CSV_ROWS) {
                    Yii::$app->session->addFlash('error', 'CSV 資料列超過上限 ' . self::MAX_CSV_ROWS . ' 筆');
                    $transaction->rollBack();
                    return false;
                }

                $formModel = new FormCandiData;
                $formModel->setScenario('create');// 模式: 建立
                $mainModel = new CandiData;
                $other = [];
            }
            
            foreach ($cellIterator as $key => $cell) {
                // 檢查檔案標頭
                if ($row->getRowIndex() == 1 && $cell->getValue() != $header[$cell->getCoordinate()] && Coordinate::columnIndexFromString($key) <= 19) {
                    Yii::$app->session->addFlash('error', "csv上傳檔{$cell->getCoordinate()}標頭不對；應該為:{$header[$cell->getCoordinate()]}，目前為:{$cell->getValue()}");
                    return false;
                }
                elseif ($row->getRowIndex() > 1) {
                    // 資料寫入
                    if (!is_null($cell)) {
                        switch ($key) {
                            case 'A':   // 組別
                                $formModel->party = strval($divisionAry[$cell->getValue()] ?? '');
                                break;
                            case 'B':   // 問題
                                $formModel->questionID = $cell->getValue();
                                break;
                            case 'C':   // 單位代碼
                                $formModel->instCode = ArrayHelper::keyExists(
                                    $cell->getValue(),
                                    Yii::$app->session->get('Share.instAry')
                                )? strval($cell->getValue()) : '00';
                                break;
                            case 'D':   // 職稱代碼
                                $formModel->tCode = ArrayHelper::keyExists(
                                    $cell->getValue(),
                                    Yii::$app->session->get('Share.payTitle')
                                ) ? $cell->getValue() : '';
                                break;
                            case 'E':   // 單位名稱
                                $formModel->instName = $cell->getValue();
                                break;
                            case 'F':   // 單位名稱英
                                $formModel->instNameE = $cell->getValue();
                                break;
                            case 'G':   // 職稱
                                $formModel->title = $cell->getValue();
                                break;
                            case 'H':   // 職稱英
                                $formModel->titleE = strval($cell->getValue());
                                break;
                            case 'I':   // 名稱
                                $formModel->Name = strval($cell->getValue());
                                break;
                            case 'J':   // 名稱英
                                $formModel->NameE = strval($cell->getValue());
                                break;
                            case 'K':   // 性別
                                $formModel->sex = strval($sexAry[$cell->getValue()] ?? '');
                                break;
                            case 'L':   // 背景顏色
                                $formModel->backgroundColor = strval($cell->getValue());
                                break;
                            case 'M':   // 排序
                                $formModel->orderNum = ($cell->getValue() == '' || !is_numeric($cell->getValue()))? '0' : $cell->getValue();
                                break;
                            case 'N':   // 自定義欄位1
                                $formModel->otherColA = strval($cell->getValue());
                                break;
                            case 'O':   // 自定義欄位1英
                                $formModel->otherColAE = strval($cell->getValue());
                                break;
                            case 'P':   // 自定義欄位2
                                $formModel->otherColB = strval($cell->getValue());
                                break;
                            case 'Q':   // 自定義欄位2英
                                $formModel->otherColBE = strval($cell->getValue());
                                break;
                            case 'R':   // 自定義欄位3
                                $formModel->otherColC = strval($cell->getValue());
                                break;
                            case 'S':   // 自定義欄位3英
                                $formModel->otherColCE = strval($cell->getValue());
                                break;
                            case 'T':   // 自定義欄位4
                                $formModel->otherColD = strval($cell->getValue());
                                break;
                            case 'U':   // 自定義欄位4英
                                $formModel->otherColDE = strval($cell->getValue());
                                break;
                            case 'V':   // 自定義欄位5
                                $formModel->otherColE = strval($cell->getValue());
                                break;
                            case 'W':   // 自定義欄位5英
                                $formModel->otherColEE = strval($cell->getValue());
                                break;
                            case 'X':   // 自定義欄位6
                                $formModel->otherColF = strval($cell->getValue());
                                break;
                            case 'Y':   // 自定義欄位6英
                                $formModel->otherColFE = strval($cell->getValue());
                                break;
                            case 'Z':   // 關聯組別，給共同問題使用的，用於取得分組的人數
                                $formModel->relateParty = strval($divisionAry[$cell->getValue()] ?? '');
                                break;
                            case 'AA':   // 特殊榮譽
                                $formModel->specialHonor = strval($cell->getValue());
                                break;
                            default:
                                $other[$key] = $cell->getValue();
                                break;
                        }
                    }
                }
            }
            if ($row->getRowIndex() > 1) {
                $formModel->voteID = $voteID;
                $formModel->sysId = NULL;
                $formModel->genMode = self::$genMode;
                $formModel->other = Json::encode($other, 336);

                // 特別處理不得為NULL的欄位
                foreach(FormCandiData::$notNullField as $notNFK => $notNFV)
                {
                    if(is_null($formModel->$notNFK) || trim($formModel->$notNFK) == '')
                        $formModel->$notNFK = $notNFV;
                }

                // 驗證匯入的檔案格式是否正確
                if($formModel->validate() && !$formModel->hasErrors())
                {
                    $mainModel->setAttributes($formModel->attributes, false);
                    // 分組跟問題必須對應
                    if (!isset($questionAry[$mainModel->questionID]) || $questionAry[$mainModel->questionID]['party'] != $mainModel->party) {
                        $mainModel->addError('questionID', '分組【'.$parties[$mainModel->party].'】不存在問題代碼'.$mainModel->questionID);
                    }
                    if($mainModel->hasErrors() || !$mainModel->save()) // 資料是否正確保存
                    {
                        $session = Yii::$app->session;
                        
                        foreach($mainModel->errors as $message)
                        {
                            $session->addFlash('error', $message[0]);
                        }
                    }
                }
                else
                {
                    $session = Yii::$app->session;
                    foreach($formModel->errors as $message)
                    {
                        $session->addFlash('error', $message[0]);
                    }
                }
                $this->dataCount++;
            }
        }

        if ($mainModel->hasErrors() || $formModel->hasErrors()) {
            return false;
        }

        try {
            $transaction->commit();// 批量新增資料
        } catch(\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->addFlash('error', "[Exception] 候選人匯入失敗: $e");
            return false;
        } catch(\Throwable $e) {
            $transaction->rollBack();
            Yii::$app->session->addFlash('error', "[Throwable] 候選人匯入失敗: $e");
            return false;
        }
        return true;
    }

    /**
     * 若 CSV 含 UTF-8 BOM，寫入暫存檔並去除 BOM 後再供 PhpSpreadsheet 讀取
     */
    protected function prepareCsvPath(string $path): string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return $path;
        }
        if (strncmp($content, "\xEF\xBB\xBF", 3) !== 0) {
            return $path;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'csv_bom_');
        if ($tmp === false) {
            return $path;
        }
        file_put_contents($tmp, substr($content, 3));
        return $tmp;
    }

    /**
     * 匯入檔案筆數
     */
    public function getDataCount($voteID, $pattern)
    {
        return count($this->_file);
    }
}
