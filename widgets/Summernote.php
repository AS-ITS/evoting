<?php
namespace app\widgets;

use Yii;

use yii\helpers\Json;
use app\assets\SummernoteAsset;

class Summernote extends \yii\base\Widget
{
	public $lang = 'zh-TW';

    public $elements = '.summernote';// class
    public $functionName = 'summernote';
    public $attributes = [];

	/**
	 * Initializes the detail view.
	 * This method will initialize required property values.
	 */
	public function init()
	{
        SummernoteAsset::register(Yii::$app->view);
	}

	/**
	 * Renders the detail view.
	 * This is the main entry of the whole detail view rendering.
	 */
	public function run()
	{
        $view = Yii::$app->view;
        $this->attributes['lang'] = $this->lang;
        $view->registerJs(Yii::$app->i18n->format(
            "$('{elements}').{functionName}({attributes});", [
                'elements' => $this->elements,
                'functionName' => $this->functionName,
                'attributes' => Json::encode($this->attributes),
            ], Yii::$app->language), $view::POS_LOAD
        );
	}
}
