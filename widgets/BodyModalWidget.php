<?php

namespace app\widgets;

use Yii;
use yii\web\View;
use yii\base\Widget;

/**
 * BodyModalWidget 是專門用於將Modal元素動態移動到<body>標籤下的Widget。
 *
 * 此Widget解決了z-index相關的顯示問題，通過將Modal元素直接插入到<body>底部，
 * 並允許通過參數自定義z-index值來確保Modal能夠正確顯示。
 */
class BodyModalWidget extends Widget
{
    /**
     * @var int Modal的z-index值。
     */
    public $modalZIndex = 1070;

    /**
     * @var int Modal背景的z-index值。
     */
    public $backdropZIndex = 1060;

    /**
     * 初始化Widget並註冊所需的JavaScript代碼。
     */
    public function init()
    {
        parent::init();

        // 移除 CSS
        if(!is_null($this->backdropZIndex) && !is_null($this->modalZIndex))
        {
            $this->registerCss();
        }

        // 移除 JS
        $this->registerJavaScript();
    }

    /**
     * 運行Widget，此方法可用於渲染Modal相關的HTML代碼，如果需要的話。
     */
    public function run()
    {
        // 這裡可以添加任何特定的HTML標記或PHP代碼來渲染Modal
    }

    /**
     * 註冊自定義CSS代碼，根據提供的z-index值動態設置。
     */
    protected function registerCss()
    {
        // 動態添加Css代碼
        $css = <<<CSS
.modal-backdrop {
    z-index: {$this->backdropZIndex}; /* 自定義背景 z-index*/
}
.modal {
    z-index: {$this->modalZIndex}; /* 自定義Modal z-index */
}
CSS;
        Yii::$app->view->registerCss($css, [], self::className());
    }

    /**
     * 註冊JavaScript代碼，將Modal動態移動到<body>標籤下。
     */
    protected function registerJavaScript()
    {
        // 動態添加JavaScript代碼
        $js = <<<JS
// 將Modal移動到<body>下的JavaScript代碼
$(document).ready(function(){
    var modal = $('.modal').detach(); // 從原來的位置移除Modal
    $('body').append(modal); // 將Modal添加到<body>標籤下
});
JS;
        Yii::$app->view->registerJs($js, View::POS_READY, self::className());
    }
}
