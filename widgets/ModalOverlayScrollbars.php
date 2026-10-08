<?php
namespace app\widgets;

use Yii;
use yii\web\View;
use yii\base\InvalidConfigException;

/**
 * ModalOverlayScrollbars 類別用於在Yii2應用中為Bootstrap模態框（Modal）提供自定義滾動條功能。
 *
 * 透過動態添加JavaScript代碼，來實現在模態框中使用OverlayScrollbars庫的功能。
 */
class ModalOverlayScrollbars extends \yii\base\Widget
{
    /** @var string 用於選擇模態框內容的CSS類別名稱 */
    public $classId = 'modal-body';
    /** @var null|string 模態框內容的右邊距樣式，用於自定義滾動條顯示時的間隙 */
    public $stylePaddingRight = null;

    /**
     * 初始化 Widget，檢查必要的配置是否已設置。
     *
     * @throws InvalidConfigException 如果必要的配置未設置則拋出異常
     */
    public function init()
    {
        parent::init(); // 確保呼叫基類的初始化方法
    }

    /**
     * 執行 Widget，輸出 HTML 內容。
     *
     * @return string 輸出的 HTML 內容
     */
    public function run()
    {
        /** @var string $blockId 用於動態生成的區塊ID。 */
        $blockId = $this->classId;

        if (!is_null($this->stylePaddingRight))
        {
            $showStylePaddingRight = 'true';
            $stylePaddingRight = $this->stylePaddingRight;
        }
        else
        {
            $showStylePaddingRight = 'false';
        }

        // 動態添加JavaScript代碼
        Yii::$app->view->registerJs(<<<JS
// Overlay Scrollbars for Bootstrap Modals
// ex: https://jsfiddle.net/djwmyur4/1/
$('.{$blockId}').each(function( index ) {
    var osInstance = OverlayScrollbars(this, {});
    osInstance.getElements().host.classList.add('os-host-flexbox');
    if ({$showStylePaddingRight})
    {
        osInstance.getElements().host.style['padding-right'] = '{$stylePaddingRight}';
    }
});
JS
, View::POS_READY, self::className().$this->classId);
    }
}