<?php
namespace app\widgets;

use yii\helpers\Html;
use rmrevin\yii\fontawesome\FAS;

/**
 * 卡片樣式
 */
class CardStyle extends \yii\base\Widget
{
    /**
     * 樣式顏色: 藍色
     * @var string
     */
    const THEME_COLOR_PRIMARY = 'primary';
    /**
     * 樣式顏色: 藍色
     * @var string
     */
    const THEME_COLOR_BLUE = 'primary';
    /**
     * 樣式顏色: 綠色
     * @var string
     */
    const THEME_COLOR_SUCCESS = 'success';
    /**
     * 樣式顏色: 綠色
     * @var string
     */
    const THEME_COLOR_GREEN = 'success';
    /**
     * 樣式顏色: 藍綠色
     * @var string
     */
    const THEME_COLOR_CYAN = 'info';
    /**
     * 樣式顏色: 藍綠色
     * @var string
     */
    const THEME_COLOR_INFO = 'info';
    /**
     * 樣式顏色: 黃色
     * @var string
     */
    const THEME_COLOR_WARNING = 'warning';
    /**
     * 樣式顏色: 黃色
     * @var string
     */
    const THEME_COLOR_YELLOW = 'warning';
    /**
     * 樣式顏色: 紅色
     * @var string
     */
    const THEME_COLOR_DANGER = 'danger';
    /**
     * 樣式顏色: 紅色
     * @var string
     */
    const THEME_COLOR_RED = 'danger';
    /**
     * 樣式顏色: 黑色
     * @var string
     */
    const THEME_COLOR_DARK = 'dark';
    /**
     * 樣式顏色: 灰色
     * @var string
     */
    const THEME_COLOR_SECONDARY = 'secondary';

    /**
     * 樣式: 標題著色
     * @var string
     */
    const THEME_DEFAULT = 'default';
    /**
     * 樣式: 標題上方的線著色
     * @var string
     */
    const THEME_OUTLINE = 'outline';
    /**
     * 樣式: 卡片著色
     * @var string
     */
    const THEME_BACKGROUND_COLOR = 'background-color';
    /**
     * 樣式: 卡片漸層著色
     * @var string
     */
    const THEME_GRADIENT_BACKGROUND_COLOR = 'gradient-background-color';

    /** @var string 主題顏色 */
    public $themeColor = self::THEME_COLOR_INFO;
    /** @var string 主題樣式 */
    public $theme = self::THEME_DEFAULT;

    /** @var array 卡片外框 HTML 屬性 */
    public $cardOptions = ['class' => ['card']];

    /** @var string 卡片標題 */
    public $title = '';
    /** @var bool 卡片標題是否編碼 */
    public $titleEncode = true;
    /** @var array 卡片標題 HTML 屬性 */
    public $titleOptions = ['class' => ['card-header', 'fw-bold', 'text-center']];

    /** @var array 卡片內容 HTML 屬性 */
    public $bodyOptions = ['class' => ['card-body']];
    /** @var array 卡片footer HTML 屬性 */
    public $footerOptions = ['class' => ['card-footer']];
    /** @var string 卡片footer HTML 屬性 */
    public $footer = '';

    /** @var bool 是否需要載入樣式 */
    public $loading = true;
    /** @var string|null 是否指定加載文字，若為 null 則使用預設 icon */
    public $loadingHtml = null;
    /** @var array 卡片內容 HTML 屬性 */
    public $loadingOptions = ['class' => ['overlay', 'card-loading']];

    /**
     * Initializes the widget.
     * This renders the form open tag.
     */
    public function init()
    {
        parent::init();
        if (!isset($this->cardOptions['id']))
        {
            $this->cardOptions['id'] = $this->getId();
        }
        switch($this->theme)
        {
            case self::THEME_DEFAULT:
                Html::addCssClass($this->cardOptions, "card-{$this->themeColor}");
                break;
            case self::THEME_OUTLINE:
                Html::addCssClass($this->cardOptions, "card-outline card-{$this->themeColor}");
                break;
            case self::THEME_BACKGROUND_COLOR:
                Html::addCssClass($this->cardOptions, "bg-{$this->themeColor}");
                break;
            case self::THEME_GRADIENT_BACKGROUND_COLOR:
                Html::addCssClass($this->cardOptions, "bg-gradient-{$this->themeColor}");
                break;
            default:
                break;
        }
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * Renders the detail view.
     * This is the main entry of the whole detail view rendering.
     */
    public function run()
    {
        $content = ob_get_clean();
        $html = Html::beginTag('div', $this->cardOptions);// 卡片框
        $html .= Html::tag('h5', ($this->titleEncode)?Html::encode($this->title):$this->title, $this->titleOptions);// 標題
        $html .= Html::tag('div', $content, $this->bodyOptions);// 卡片內容

        if ($this->loading)
        {
            // 是否指定加載文字
            if($this->loadingHtml === null)
            {
                // 若為 null 則使用預設 icon
                $loadingHtml = FAS::icon('sync-alt')->spin()->size(FAS::SIZE_2X);
            }
            else
            {
                $loadingHtml = $this->loadingHtml;
            }
            $html .= Html::tag('div', $loadingHtml, $this->loadingOptions);

            // 隱藏現職加載動畫
            $this->getView()->registerJs("$('.card-loading').addClass('d-none');");
        }
        if (trim($this->footer) != '')
        {
            $html .= Html::tag('div', Html::encode($this->footer), $this->footerOptions); // footer
        }
        $html .= Html::endTag('div');// 卡片框
        return $html;
    }
}

