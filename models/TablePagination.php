<?php
namespace app\models;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Url;
use app\components\helper\ArrayHelper;

/**
 * 換頁用元件
 *
 * 設定 web/config.php：
 * ```php
 * return [
 *     'components' => [
 *         'tablePag' => [ // 每頁筆數更換小工具
 *             'class' => 'app\models\TablePagination',
 *         ],
 *     ],
 * ];
 * ```
 *
 * 使用方法：
 *
 * - SiteController
 *
 * ```php
 * // ...
 * class SiteController extends \app\components\Controller
 * {
 *     // ...
 *     public function actions()
 *     {
 *         return [
 *             'set-page-size' => [ // 切換頁數
 *                 'class' => \app\actions\SwitchPagesAction::className(),
 *                 'handleEventClass' => \app\models\HandleSwitch::className(),
 *             ],
 *         ];
 *     }
 *     // ...
 * }
 * ```
 *
 * - Controller
 *
 * ```php
 * // ...
 * return $this->render('index',[
 *     'dataProvider' => \yii\data\ActiveDataProvider([
 *         'query' => $query,
 *         'pagination' => Yii::$app->tablePag->getPagination(), // add this line
 *      ]),
 * ]);
 * ```
 *
 * - View
 *
 * ```php
 * // Please set the following ids to be the same.
 * // This example uses myGrid as the Id.
 * \yii\widgets\Pjax::begin(['id' => 'myGrid']);
 * echo \yii\widgets\GridView::widget([
 *     'id' => 'myGrid',
 *     'dataProvider' => $dataProvider,
 *     'summary' => Yii::$app->tablePag->getSummaryText('myGrid'),
 *     // ...
 * ]);
 * \yii\widgets\Pjax::end();
 * ```
 *
 * 備註: 可以透過增加 data-pjax="0" 屬性來禁用內特定鏈接的 pjax
 *
 * @link yii\widgets\Pjax https://www.yiiframework.com/doc/api/2.0/yii-widgets-pjax
 */
class TablePagination extends \yii\base\BaseObject
{
    /** @var string 保存於 session 的 flag */
    public $sessionName = 'Table.pagination';

    /** @var string 頁數設置參數 */
    public $setPageGetName = 'pageSize';

    /** @var string GridVie頁數參數 */
    public $gridViewPageGetParam = 'page';

    /** @var array 頁數變更時POST到此動作 */
    public $changeAction = ['/site/set-page-size'];

    /** @var array 最外框所定義之 HTML 標籤 */
    public $options = ['class'=>'summary text-end mb-2'];

    /** @var string 訊息語言包類別 */
    public $category = 'switch';

    /** @var bool 預設可顯示所有頁數 */
    public $pageAllSwitch = true;

    /** @var int 預設每頁的筆數 */
    public $pageSizeDef = 200;

    /** @var int 每頁最大筆數 */
    public $pageSizeMax = 1000000;

    /** @var null|string 換頁的文字格式 */
    public $pageTitleFormat;

    /** @var null|int[] 生成每頁顯示筆數的頁數 */
    public $pageSizeGenerateList;

    /** @var null|string[] 每頁顯示筆數的項目 */
    public $pageSizeList;

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     */
    public function init()
    {
        // 自訂頁數標籤
        if(is_null($this->pageSizeList))
        {
            // 僅自訂頁數
            if(is_null($this->pageSizeGenerateList))
            {
                $this->pageSizeGenerateList = [15, 30, 50, 100, 200, 500, 1000];
            }

            // 根據提供的頁數自動生成
            $this->pageSizeList = [];
            foreach($this->pageSizeGenerateList as $page)
            {
                $this->pageSizeList[strval($page)] = $this->getPageLabel(intval($page));
            }

            // 可否顯示所有頁數
            if($this->pageAllSwitch)
            {
                $this->pageSizeList['0'] = Yii::t($this->category, '全部');
            }
        }
        if(is_null($this->pageTitleFormat))
        {
            $this->pageTitleFormat = Yii::t(
                $this->category,
                '第 {page} 頁，共 {pageCount} 頁 ({totalCount} 筆)，顯示 {showCount}'
            );
        }
    }

    /**
     * 翻譯頁數語言
     *
     * @param int $item 頁數
     *
     * @return string 翻譯後頁數文字
     */
    public function getPageLabel($item)
    {
        return Yii::t($this->category,'{item} 筆',['item'=>$item]);
    }

    /**
     * 設定當前表格每頁筆數
     *
     * 設定方法:
     * ```php
     * Yii::$app->tablePag->pagination = 200;
     * ```
     *
     * @param int $value 設定當前表格每頁筆數
     *
     * @return string
     */
    public function setPagination($value=null)
    {
        if(is_null($value))
            $value = $this->pageSizeDef;
        if($value == 0 || (is_numeric($value) && (int) $value < $this->pageSizeMax))
            Yii::$app->session->set($this->sessionName, ($value == 0)?false:[$this->setPageGetName => (int) $value]);
    }

    /**
     * 取得當前表格每頁筆數
     *
     * 取得方法:
     * ```php
     * $page = Yii::$app->tablePag->pagination;
     * ```
     *
     * @param bool $returnNum 是否僅返回頁數
     *
     * @return array|int 如果 `$returnNum` 是 `true`，才返回數字。
     */
    public function getPagination($returnNum=false)
    {
        $pagination = Yii::$app->session->get($this->sessionName, [$this->setPageGetName => $this->pageSizeDef]);
        if($returnNum)
        {
            return ArrayHelper::getValue($pagination, $this->setPageGetName);
        }
        return $pagination;
    }

    /**
     * 從頁數取得當前筆數
     *
     * 用法：
     * ```php
     * echo yii\grid\GridView::widget([
     *     // ...
     *     'columns' => [
     *         [
     *             'label' => '筆數序號',
     *             'value' => function ($model, $key, $index, $column) {
     *                 return Yii::$app->tablePag->getItemCountWithPageCount($index);
     *             },
     *         ],
     *     ]
     * ]);
     * ```
     *
     * @param int $index `$dataProvider` 返回的模型數組中數據模型的從 `0` 開始的索引
     * @param null|int $page 當前頁數，若不提供將使用GridView預設的GET參數
     *
     * @return int 當前筆數
     *
     * @link yii\grid\GridView::$rowOptions https://www.yiiframework.com/doc/api/2.0/yii-grid-gridview#$rowOptions-detail
     */
    public function getItemCountWithPageCount($index, $page=null)
    {
        if(is_null($page))
        {
            $page = Yii::$app->request->get($this->gridViewPageGetParam, 1);
        }
        if(!is_numeric($page) || (int)$page < 1)
        {
            $page = 1;
        }
        if(!is_numeric($index) || (int)$index < 0)
        {
            $index = 0;
        }
        $pageNum = $this->getPagination(true);// 每頁筆數
        $pageSize = intVal($page)-1;// 當前頁數
        $pageStartNum = ($pageSize * $pageNum) + 1;// 當前頁起始筆數
        return $pageStartNum + intVal($index);
    }

    /**
     * 從當前筆數取得當前頁數
     *
     * 用法：
     * ```php
     * $page = Yii::$app->tablePag->getPageCountWithItemCount($pageCount);
     * ```
     *
     * @param int $pageCount 當前筆數
     *
     * @return int 當前頁數
     */
    public function getPageCountWithItemCount($pageCount)
    {
        $pageNum = $this->getPagination(true);// 每頁筆數
        // 避免DivisionByZeroError
        if ($pageNum <= 0)
        {
            $pageNum = 1;
        }
        $divisible = ($pageCount % $pageNum) == 0 ? 0 : 1;// 當整除不加 1
        return floor($pageCount/$pageNum) + $divisible;
    }

    /**
     * 取得當前表格摘要文本
     *
     * 使用方法:
     * ```php
     * echo \yii\widgets\GridView::widget([
     *     'id' => 'myGrid',
     *     'summary' => Yii::$app->tablePag->getSummaryText('myGrid'),
     *     // ...
     * ]);
     * ```
     *
     * @param string 需要換頁表格 yii\grid\GridView 的 id
     *
     * @return string 換頁的 HTML 語法
     */
    public function getSummaryText($id)
    {
        // (原始)發送更新頁數請求+取得新表格更新，被黑箱認定為默認 XSS 攻擊
        // $url = sprintf( 'window.location.origin+"%s"', Url::to($this->changeAction) );
        // $onchange = "$.pjax.reload({ type: 'POST', container: '#{$id}', data:{ {$this->setPageGetName}: $(this).val()}, url: {$url} });";
        // $onchange .= "$('select').selectpicker();";

        // 拒絕當前網址的使用行為
        if(empty($this->changeAction))
        {
            throw new InvalidConfigException('無法使用當前網址，避免引發XSS攻擊。');
        }

        // 發送更新頁數請求
        $url = sprintf( '%s', Url::to($this->changeAction, 'https') );
        $onchange = "$.ajax({ type:\"POST\", url: \"$url\", data:{ {$this->setPageGetName}: $(this).val()}, async:false});";

        // 取得新表格更新
        // if(is_null(Yii::$app->request->get('_pjax'))) // 由 PHP 實作
        //     $url2 = Url::to(['_pjax'=>"#{$id}"], true);
        // else
        //     $url2 = Url::current([], true);
        // $url2 = "'{$url2}'";
        $onchange .= "var usp = new URLSearchParams(window.location.search);"; // 由 JS 實作
        $onchange .= "usp.set('_pjax', '#{$id}');";
        $onchange .= "console.log(window.location.origin+window.location.pathname+'?'+usp.toString());";
        $url2 = "window.location.origin+window.location.pathname+'?'+usp.toString()+window.location.hash";

        // 組合語法
        $onchange .= "$.pjax.reload({ container: '#{$id}', url: $url2 });";
        $onchange .= "$('select').selectpicker();";

        // 取得設定頁數
        $pageSize = $this->pagination;
        if(isset($pageSize[$this->setPageGetName]))
            $pageSize = $pageSize[$this->setPageGetName];

        // 表格更新後執行的 JS
        // https://stackoverflow.com/questions/34491080/yii2-gridview-pjax-event
        $autoReg = join(' ',[
            'if($(\'.selectpicker\').length > 0) {',
                '$(\'.selectpicker\').selectpicker();',
            '}',
            'if($(\'[data-bs-toggle="tooltip"]\').length > 0) {',
                '$(\'[data-bs-toggle="tooltip"]\').each(function() { new bootstrap.Tooltip(this); });',
            '}',
        ]);

        Yii::$app->view->registerJs("$(document).on('pjax:success', function() { $autoReg });");
        return Html::tag('div',\app\components\helper\ArrayHelper::strtr($this->pageTitleFormat, [
            'page' => Html::a('{page}', null, [
                'onclick' => 'return false;',
                'title'   => Yii::t($this->category,'第 {begin} - {end} 筆'),
                'class'   => 'text-monospace',
                'data-bs-toggle' => 'tooltip',
            ]),
            'pageCount' => Html::tag('span', '{pageCount}', ['class'=>'text-monospace']),
            'totalCount' => Html::tag('span', '{totalCount}', ['class'=>'text-monospace']),
            'showCount' => Html::dropDownList($this->setPageGetName, $pageSize, $this->pageSizeList, [ 'onchange'=>$onchange, 'class'=>'pjax'])
        ]), $this->options);
    }
}
