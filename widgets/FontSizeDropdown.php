<?php
namespace app\widgets;

use Yii;

use yii\bootstrap5\Nav;

class FontSizeDropdown extends \yii\base\Widget
{    
    /**
     * 要改變文字大小的DOM
     *
     * @var string
     */
    public $element = 'main';
    
    /**
     * 大的文字尺寸，可以是任何css的尺寸度量(%、px、em...)
     *
     * @var string
     */
    public $large = '24px';

    /**
     * 中等的文字尺寸，可以是任何css的尺寸度量(%、px、em...)
     *
     * @var string
     */
    public $medium = '100%';

    /**
     * 小的文字尺寸，可以是任何css的尺寸度量(%、px、em...)
     *
     * @var string
     */
    public $small = '16px';

	/**
	 * Initializes the detail view.
	 * This method will initialize required property values.
	 */
	public function init()
	{
        parent::init();
	}

	/**
	 * Renders the detail view.
	 * This is the main entry of the whole detail view rendering.
	 */
	public function run()
	{
        $view = Yii::$app->view;
        $view->registerJs(<<<JS
            // 載入文字尺寸，透過loacalStorage儲存資料
            function LoadFont() {
                //無loacalStorage才宣告
                if (localStorage['font'] == null) {
                    localStorage.setItem('font', 'middle');
                    $(".font").addClass("ative");
                    $('$this->element').add('$this->element .btn').css({ "font-size": "$this->medium" });
                }

                let storage = localStorage.getItem('font');
                $('.fontlevel .ative').removeClass('ative d-none');

                switch (true) {
                    case storage == 'big':
                    $('$this->element').add('$this->element .btn').css({ "font-size": "$this->large" });
                    $(".bigfont").addClass("ative d-none");
                    break;

                    case storage == 'middle':
                    $('$this->element').add('$this->element .btn').css({ "font-size": "$this->medium" });
                    $(".font").addClass("ative d-none");
                    break;

                    case storage == 'small':
                    $('$this->element').add('$this->element .btn').css({ "font-size": "$this->small" });
                    $(".smallfont").addClass("ative d-none");
                    break;
                }
                $('#font-size-dropdown').text($('.fontlevel .ative').text())
            }

            // 設定文字尺寸
            function SetFont(size) {
                $(".fontlevel .ative").removeClass("ative d-none");

                if (size == "bigfont") {
                    $('$this->element').add('$this->element .btn').animate({ "fontSize": "$this->large" }, 400);
                    $(".bigfont").addClass("ative");
                    localStorage.setItem('font', 'big');
                } else if (size == "smallfont") {
                    $('$this->element').add('$this->element .btn').animate({ "fontSize": "$this->small" }, 400);
                    $(".smallfont").addClass("ative");
                    localStorage.setItem('font', 'small');
                } else {
                    $('$this->element').add('$this->element .btn').animate({ "fontSize": "$this->medium" }, 400);
                    $(".font").addClass("ative"); 
                    localStorage.setItem('font', 'middle');
                }
                LoadFont();
            }

            $('.fontlevel .dropdown-menu').css({ "min-width": "1rem" });
            LoadFont();
JS
            , $view::POS_END
        );
        
        // Dropdown
        echo (new Nav())->renderItem(
            [
                'label' => 'A',
                'items' => [
                    ['label' => 'A+', 'url' => 'javascript:void(0);', 'linkOptions' => ['class' => 'bigfont', 'onclick' => "SetFont('bigfont')"]],
                    ['label' => 'A', 'url' => 'javascript:void(0);', 'linkOptions' => ['class' => 'font', 'onclick' => "SetFont('font')"]],
                    ['label' => 'A-', 'url' => 'javascript:void(0);', 'linkOptions' => ['class' => 'smallfont', 'onclick' => "SetFont('smallfont')"]],
                ],
                'options' => ['class' => 'fontlevel'],
                'linkOptions' => ['id' => 'font-size-dropdown'],
            ]
        );
	}
}

