<?php
/**
 * 修補 Bootstrap 5 Button 雙重初始化問題
 * 覆寫 yii\bootstrap5\Button，停用 registerPlugin，避免與 data-bs-toggle 自動初始化衝突
 * 如果要使用 ToggleButtonGroup 或需要按鈕的 toggle 功能，需要手動添加初始化程式碼。
 */

namespace app\components\bootstrap5;

use yii\bootstrap5\Button as BaseButton;

/**
 * Button 組件，修補 Bootstrap 5 雙重初始化問題
 *
 * 使用方式：
 * ```php
 * use app\components\bootstrap5\Button;
 *
 * echo Button::widget([
 *     'label' => 'Click me',
 *     'options' => ['class' => 'btn-primary'],
 * ]);
 * ```
 */
class Button extends BaseButton
{
    /**
     * @inheritdoc
     *
     * 覆寫 run() 方法，移除 registerPlugin('button') 調用
     * 因為 Bootstrap 5 會通過 data-bs-toggle 等屬性自動初始化
     */
    public function run(): string
    {
        // 不調用 registerPlugin('button')，避免雙重初始化
        // 原本的程式碼: $this->registerPlugin('button');

        return \yii\bootstrap5\Html::tag(
            $this->tagName,
            $this->encodeLabel ? \yii\bootstrap5\Html::encode($this->label) : $this->label,
            $this->options
        );
    }
}
