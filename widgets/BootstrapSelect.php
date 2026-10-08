<?php
/**
 * bootstrap-select v1.13.18
 *
 * @see https://developer.snapappointments.com/bootstrap-select/
 */
namespace app\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * 客製化JQ選擇框小工具
 */
class BootstrapSelect extends Widget
{
    /** 標籤名 */

    /** @var string $selectName 物件標籤名 */
    public $selectName = 'select';
    /** @var string $selectName 選項標籤名 */
    public $optionTagName = 'option';
    /** @var string $selectName 群組標籤名 */
    public $groupTagName = 'optgroup';

    /** @var bool[] $cssClass 物件預設之class */
    public $cssClass = [
        'selectpicker' => true,
        'show-tick'    => true,
        'form-control' => false,
        'mb-2'         => false,
    ];
    /** @var array $options 屬性 */
    public $options;
    /** @var string|array $onchange 改變後(轉址處理) */
    public $onchange = null;
    /** @var string $id 物件ID */
    public $id = null;
    /** @var string $name 表單的欄位名稱 */
    public $name = null;
    /** @var mixed $value 預設選擇 */
    public $value = null;
    /** @var array $items 項目 */
    public $items = [];

    /**
     * 初始化
     *
     * @return void
     */
    public function init()
    {
        $this->registerJs();
    }

    /**
     * 主程式
     *
     * @return string the result of widget execution to be outputted.
     */
    public function run()
    {
        $html = '';
        if(isset($this->onchange) && !is_null($this->onchange))
            $options['onchange'] = call_user_func_array( [$this,'processOnChange'], $this->onchange);
        if(isset($this->name) && !is_null($this->name))
            $options['name'] = $this->name;
        if(isset($this->id) && !is_null($this->id))
            $options['id'] = $this->id;
        foreach($this->cssClass as $css => $status)
        {
            if($status) $options['class'][] = $css;
        }
        foreach($this->items as $index => $item)
        {
            if(is_array($item))
            {
                $disabled = (isset($item['disabled']) && $item['disabled'])?true:false; // Disabled option groups
                if(isset($item['items'])) // Select boxes with optgroups
                {
                    $htm = '';
                    foreach($item['items'] as $in => $it)
                    {
                        if(is_array($it))
                        {
                            $htm .= Html::tag( $this->optionTagName, $it['label'],
                                array_merge_recursive(
                                    $this->getValueOption($it['value']), isset($it['option']) ? $this->processOption($it['option']) : []
                                )
                            );
                        }
                        else
                        {
                            $htm .= Html::tag( $this->optionTagName, $it, $this->getValueOption($in));
                        }
                    }
                    $option = array_merge_recursive(
                        ['label'=>$item['label'],'disabled'=>$disabled], isset($item['option']) ? $this->processOption($item['option']) : []
                    );
                    $html .= Html::tag($this->groupTagName, $htm, $option);
                    continue;
                }
                $option = array_merge_recursive(
                    $this->getValueOption($item['value']), array_merge_recursive(
                        ['disabled'=>$disabled], isset($item['option']) ? $this->processOption($item['option']) : []
                    )
                );
                $html .= Html::tag( $this->optionTagName, $item['label'], $option);
                continue;
            }
            $html .= Html::tag( $this->optionTagName, $item, $this->getValueOption($index));
        }
        if($this->options)
            return Html::tag($this->selectName,$html,array_merge_recursive( $this->processOption($this->options), $options));
        return Html::tag($this->selectName,$html,$options);
    }

    /**
     * 取得選項數值
     *
     * @param mixed $value 選項數值
     *
     * @return array
     */
    protected function getValueOption($value)
    {
        return [ 'value' => $value, 'selected' => $this->isSelected($value)];
    }

    /**
     * 處理選擇後轉向
     *
     * @param mixed $value 選項數值
     *
     * @return bool 是否選擇該項目
     */
    protected function isSelected($value)
    {
        if(!is_null($this->value))
        {
            if(is_array($this->value) && in_array($value,$this->value))
                return true;
            else if($this->value == $value)
                return true;
        }
        return false;
    }

    /**
     * 取得設定之屬性
     *
     * @param array $options 屬性
     *
     * @return array
     *
     * @link 官方範例 https://developer.snapappointments.com/bootstrap-select/examples/
     */
    protected function processOption($options)
    {
        $result = [];
        foreach($options as $optKey => $optVal)
        {
            if(is_null($optVal)) continue;
            switch($optKey)
            {
                case 'search':
                case 'live-search':
                case 'data-live-search':
                    $result['data-live-search'] = 'true';
                    continue;
                case 'max-options':
                case 'data-max-options':
                    $result['data-max-options'] = $optVal;
                    continue;
                case 'tokens':// Option USE
                case 'data-tokens':
                    $result['data-tokens'] = $optVal;
                    continue;
                case 'text-format':
                case 'selected-text-format':
                case 'data-selected-text-format':
                    $result['data-selected-text-format'] = $optVal;
                    continue;
                case 'style':
                case 'data-style':
                    $result['data-style'] = $optVal;
                    continue;
                case 'icon':// Option USE
                case 'data-icon':
                    $result['data-icon'] = $optVal;
                    continue;
                case 'content':// Option USE
                case 'data-content':
                    $result['data-content'] = $optVal;
                    continue;
                case 'subtext':// Option USE
                case 'data-subtext':
                    $result['data-subtext'] = $optVal;
                    continue;
                case 'show-subtext':// Option USE
                case 'data-show-subtext':
                    $result['data-show-subtext'] = 'true';
                    continue;
                case 'size':
                case 'data-size':
                    $result['data-size'] = $optVal;
                    continue;
                case 'actions-box':
                case 'data-actions-box':
                    $result['data-actions-box'] = 'true';
                    continue;
                case 'divider':// Option USE
                case 'data-divider':
                    $result['data-divider'] = 'true';
                    continue;
                case 'header':
                case 'data-header':
                    $result['data-header'] = $optVal;
                    continue;
                case 'container':
                case 'data-container':
                    $result['data-container'] = $optVal;
                    continue;
                case 'dropup-auto':
                case 'data-dropup-auto':
                    $result['data-dropup-auto'] = $optVal;
                    continue;
                case 'width':
                case 'data-width':
                    $result['data-width'] = $optVal;
                    continue;

                default:
                case 'title':// Option/Select USE
                case 'multiple':
                case 'disabled':// Disabled
                    $result[$optKey] = $optVal;
                    continue;
            }
        }
        return $result;
    }

    /**
     * [客製化] 處理選擇後轉向
     *
     * @param string $oneSelf GET 的 KEY，value為select 的 this.value
     * @param null|array $keep 保留 GET 的 KEY
     * @param array|string $url YII2 URL
     *
     * @return string
     */
    protected function processOnChange( $oneSelf, $keep = null, $url = null)
    {
        if(is_null($url))
            $text = 'window.location = window.location.origin+window.location.pathname';
        else
            $text = sprintf('window.location = window.location.origin+"%s"',Url::to($url));
        $isQueMark = strpos($text,'?') === false;
        if(is_null($keep))
        {
            $text .= sprintf('+"%s%s="+this.value', $isQueMark?'?':'&', $oneSelf);
            return $text;
        }
        foreach($keep as $i => $k)
        {
            if(is_string($i)){
                $text .= sprintf(
                    '+"%1$s%2$s=%3$s"',
                    ($isQueMark && $i == 0)?'?':'&',
                    $i,$k
                );
            }else{
                $text .= sprintf(
                    '+"%2$s%1$s="+(new URLSearchParams(window.location.search)).get("%1$s")',
                    $k,
                    ($isQueMark && $i == 0)?'?':'&'
                );
            }
        }
        $text .= sprintf('+"%s%s="+this.value', (count($keep) == 0 && $isQueMark)?'?':'&', $oneSelf);
        return $text;
    }

    /**
     * 註冊JS、Assets
     *
     * @return void
     */
    protected function registerJs()
    {
        $view = $this->getView();
        \app\assets\BootstrapSelectAsset::register($view);
    }
}
