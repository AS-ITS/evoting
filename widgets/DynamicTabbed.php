<?php
namespace app\widgets;

use Yii;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use app\components\helper\ArrayHelper;

class DynamicTabbed extends Widget
{
    static public $requiredFields = [
        'tag', 'name', 'html'
    ];

	public $active;
	public $attributes;
	public $navAlign = 'left';

	/**
	 * Initializes the detail view.
	 * This method will initialize required property values.
	 */
	public function init()
	{
		if (is_null($this->active)) {
            throw new InvalidConfigException('Please specify the "active" property.');
        }
        foreach($this->attributes as $a => $attr)
        {
            foreach(self::$requiredFields as $rF)
            {
                if(!ArrayHelper::keyExists($rF,$attr))
                    throw new InvalidConfigException('Please specify the "attributes['.$a.']['.$rF.']" property.');
            }
        }
	}

	/**
	 * Renders the detail view.
	 * This is the main entry of the whole detail view rendering.
	 */
	public function run()
	{
		echo Html::tag('ul',  $this->getNav(),     ['class'=>'nav nav-tabs justify-content-'.$this->navAlign.' mb-3','role'=>'tablist']);
		echo Html::tag('div', $this->getContent(), ['class'=>'tab-content']);
	}

    /**
     * 導航欄
     */
    public function getNav($html = '')
	{
        foreach($this->attributes as $key => $tl)
        {
            $show = $this->active == $tl['tag'];
            $html .= Html::beginTag('li', ['class'=>'nav-item','role'=>'presentation']);
            $html .= Html::a($tl['name'], '#'.$tl['tag'], [
                'class' => 'nav-link'.($show?' active':''),
                'data-bs-toggle'  => 'tab',
                'aria-controls'=> $tl['tag'],
                'aria-selected'=> ($show?'true':'false'),
            ]);
            $html .= Html::endTag('li');
        }
		return $html;
	}

    /**
     * 內文
     */
	public function getContent($html = '')
	{
		foreach($this->attributes as $key => $tl)
        {
            $show = $this->active == $tl['tag'];
            $html .= Html::tag(
                'div', $tl['html'],
                [
                    'class'=> 'tab-pane fade'.($show?' show active':''),
                    'id'   => $tl['tag'],
                    'role' => 'tabpanel',
                    'aria-labelledby' => $tl['tag'].'-tab'
                ]
            );
        }
		return $html;
	}
}

