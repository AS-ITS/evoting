<?php
namespace app\widgets;

use Yii;
use yii\helpers\ArrayHelper;
use yii\bootstrap5\Html;
use rmrevin\yii\fontawesome\FAS;

/**
 * 根據需要改造後的 bootstrap5 Modal 元件
 */
class Modal extends \yii\bootstrap5\Modal
{
    /**
     * Renders the close button.
     * @return string the rendering result
     */
    protected function renderCloseButton()
    {
        if (($closeButton = $this->closeButton) !== false)
        {
            $tag = ArrayHelper::remove($closeButton, 'tag', 'button');
            $label = ArrayHelper::remove($closeButton, 'label', Html::tag('span', '&times;', [
                'aria-hidden' => 'true'
            ]));

            $closeButtonHtml = Html::tag($tag, $label, $closeButton);
            $redoButtonHtml = Html::tag('button', FAS::icon('redo')->size(FAS::SIZE_XS), [
                'type'=>'button', 'class'=>'close', 'id'=>'refresh'
            ]);

            return Html::tag('div', $closeButtonHtml.$redoButtonHtml, ['class'=>'modal-header-right']);
        }
        else
        {
            return null;
        }
    }
}