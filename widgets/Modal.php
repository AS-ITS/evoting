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
    protected function renderCloseButton(): ?string
    {
        if (($closeButton = $this->closeButton) !== false)
        {
            $tag = ArrayHelper::remove($closeButton, 'tag', 'button');
            // Bootstrap 5 的 .btn-close 以背景圖示呈現 X，不需文字
            $label = ArrayHelper::remove($closeButton, 'label', '');
            if ($tag === 'button' && !isset($closeButton['type'])) {
                $closeButton['type'] = 'button';
            }

            $closeButtonHtml = Html::tag($tag, $label, $closeButton);
            $redoButtonHtml = Html::tag('button', FAS::icon('redo')->size(FAS::SIZE_XS), [
                'type' => 'button', 'class' => 'btn btn-sm btn-link text-secondary p-0', 'id' => 'refresh', 'aria-label' => 'Refresh'
            ]);

            return Html::tag('div', $redoButtonHtml.$closeButtonHtml, ['class' => 'modal-header-right ms-auto d-flex align-items-center gap-3']);
        }
        else
        {
            return null;
        }
    }
}