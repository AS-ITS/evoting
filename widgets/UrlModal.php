<?php
namespace app\widgets;

use Yii;
use yii\web\View;
use yii\helpers\Url;
use yii\helpers\Html;
use app\widgets\Modal;
use yii\base\InvalidConfigException;
use app\assets\UrlModalAsset;

/**
 * 使用 Bootstrap Modal 實現浮動顯示頁面的 Widget。
 * 此類別提供了一個彈出視窗（Modal），可動態加載指定的 URL 內容。
 */
class UrlModal extends \yii\base\Widget
{
    /**
     * 參數
     */

    /** @var string $contactAjaxAction 主要內容的 AJAX Action URL */
    public $contactAjaxAction = null;
    /** @var string $footerAjaxAction 底部內容的 AJAX Action URL */
    public $footerAjaxAction  = null;

    /**
     * 模板參數
     */

    /** @var string $title Modal 的標題 */
    public $title = '';
    /** @var array $options Modal 的 HTML 屬性選項 */
    public $options = [];
    /** @var array $clientOptions Bootstrap Modal 插件的客戶端選項 */
    public $clientOptions = [];
    /** @var string $modalId Modal 的 HTML ID 屬性 */
    public $modalId = 'details-modal';
    /** @var bool $centerVertical 是否垂直置中 Modal */
    public $centerVertical = true;
    /** @var bool $scrollable Modal 內容是否可滾動 */
    public $scrollable = true;
    /** @var string $size Modal 的尺寸，可選值有 Modal::SIZE_DEFAULT、Modal::SIZE_EXTRA_LARGE、Modal::SIZE_LARGE、Modal::SIZE_SMALL */
    public $size = Modal::SIZE_EXTRA_LARGE;
    /** @var bool $overlayScrollbar 是否使用自定義滾動條功能 */
    public $overlayScrollbar = true;
    /** @var string $footer Modal 的底部內容（HTML格式） */
    public $footer = null;
    /** @var array $footerOptions Modal 底部的 HTML 屬性選項 */
    public $footerOptions = ['style'=>['display'=>'block','padding'=>'0.25rem 0.75rem']];
    /** @var string $loadingText 加載時顯示的文字或 HTML 內容 */
    public $loadingText = '<div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>';

    /**
     * 初始化 Widget，檢查必要的配置是否已設置。
     *
     * @throws InvalidConfigException 如果必要的配置未設置則拋出異常
     */
    public function init()
    {
        parent::init(); // 確保呼叫基類的初始化方法

        if (is_null($this->contactAjaxAction) || trim($this->contactAjaxAction) == '')
        {
            throw new InvalidConfigException('Please specify the "contactAjaxAction" property.');
        }
        if (is_null($this->footer))
        {
            if (is_null($this->footerAjaxAction) || trim($this->footerAjaxAction) == '')
            {
                throw new InvalidConfigException('Please specify the "footerAjaxAction" property.');
            }
        }
    }

    /**
     * 執行 Widget，輸出 HTML 內容。
     *
     * @return string 輸出的 HTML 內容
     */
    public function run()
    {
        $contactId = $this->modalId.'-contact';
        $footerId = is_null($this->footer) ? $this->modalId.'-footer' : '';

        // 將 JavaScript 代碼註冊到視圖中
        $this->registerClientScript($this->modalId, $contactId, $footerId);

        // 建構並顯示 Modal
        $this->renderModal($contactId, $footerId);
    }

    /**
     * 渲染 Modal。
     *
     * 將 Modal 的渲染邏輯分離到獨立的方法，以提高代碼的模組化和重用性。
     *
     * @param string $contactId 聯絡資訊區塊的 ID
     * @param string $footerId 底部區塊的 ID
     */
    protected function renderModal($contactId, $footerId)
    {
        $modalAry = [
            'id'=>$this->modalId,
            'title'=>$this->title,
            'options'=>$this->options,
            'clientOptions'=>$this->clientOptions,
            'size'=>$this->size,
            'scrollable'=>$this->scrollable,
            'centerVertical'=>$this->centerVertical,
        ];

        // 未定義為空字串('')，則當作使用 Ajax 存取
        if (is_null($this->footer))
        {
            $this->footer = Html::tag('div', $this->loadingText, ['id'=>$footerId]);
        }

        // 當非空字串，則需要建立 div 供 Ajax 替換內容
        if(trim($this->footer) != '')
        {
            $modalAry['footer'] = $this->footer;
            $modalAry['footerOptions'] = $this->footerOptions;
        }

        // 輸出 Modal
        Modal::begin($modalAry);
        echo Html::tag('div', 'Loading... please wait', ['id'=>$contactId]);
        Modal::end();
    }

    /**
     * 註冊客戶端腳本（JavaScript）。
     *
     * @param string $modalId Modal ID
     * @param string $contactId 聯絡資訊區塊的 ID
     * @param string $footerId 底部區塊的 ID
     *
     * 分離 JavaScript 代碼到獨立的方法，以提高代碼的可讀性和維護性。
     */
    protected function registerClientScript($modalId, $contactId, $footerId)
    {
        $loadingText = $this->loadingText;
        $contactURL = Url::to([$this->contactAjaxAction], 'https');

        if (is_null($this->footer))
        {
            $footerStatus = 'true';
            $footerURL = Url::to([$this->footerAjaxAction], 'https');
        }
        else
        {
            $footerStatus = 'false';
        }

        // 註冊腳本方法
        $this->registerFunctionScript();

        // 註冊腳本參數
        Yii::$app->view->registerJs(<<<JS
        $('#$modalId .modal-header-right #refresh').on( "click", function() {
            var params = $('#$modalId').find('.modal-content').data();

            var contactURL = updateURLParamAry('$contactURL',params);
            changeBlockUrlModal( '#$contactId', contactURL);

            if ($footerStatus)
            {
                var footerURL = updateURLParamAry('$footerURL',params);
                changeBlockUrlModal( '#$footerId', footerURL);
            }
        });
        $('#$modalId').on('show.bs.modal', function (event)
        {
            var button = $(event.relatedTarget);
            var params = button.data();

            var contactURL = updateURLParamAry('$contactURL',params);
            changeBlockUrlModal( '#$contactId', contactURL);

            if ($footerStatus)
            {
                var footerURL = updateURLParamAry('$footerURL',params);
                changeBlockUrlModal( '#$footerId', footerURL);
            }

            // 將本次存取之參數放入浮動視窗中 modal-content 的 dataset
            for (const [key, value] of Object.entries(params))
            {
                if(key == 'toggle' || key == 'target')
                {
                    continue;
                }
                $(event.target).find('.modal-content')[0].dataset[key] = value;
            }
        });
        $('#$modalId').on('hide.bs.modal', function (event) {
            // 當視窗關閉時，刪除所有放入浮動視窗中 modal-content 的 dataset 之參數
            var element = $(event.target).find('.modal-content')[0];
            Object.keys(element.dataset).forEach(key => delete element.dataset[key]);
        });
        JS);

        // 是否使用自定義滾動條功能
        if($this->overlayScrollbar)
        {
            // ModalOverlayScrollbars 類別用於在Yii2應用中為Bootstrap模態框（Modal）提供自定義滾動條功能
            \app\widgets\ModalOverlayScrollbars::widget([
                'classId' => 'modal-body',
            ]);
        }

        // 解決Yii2框架中遇到的Modal與Backdrop的z-index問題
        \app\widgets\BodyModalWidget::widget();
    }

    /**
     * 註冊腳本方法（JavaScript）。
     *
     * 分離 JavaScript 代碼到獨立的方法，以提高代碼的可讀性和維護性。
     */
    protected function registerFunctionScript()
    {
        $view = $this->getView();
        UrlModalAsset::register($view);
    }
}
