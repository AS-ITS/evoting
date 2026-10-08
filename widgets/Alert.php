<?php

namespace app\widgets;

use Yii;

/**
 * Alert 小部件用於從會話快閃中渲染消息。所有快閃消息按照設置順序顯示，
 * 可以通過以下方式設置消息：
 *
 * ```php
 * Yii::$app->session->setFlash('error', '這是一條消息');
 * Yii::$app->session->setFlash('success', '這是一條消息');
 * Yii::$app->session->setFlash('info', '這是一條消息');
 * ```
 *
 * 可以如下設置多條消息：
 *
 * ```php
 * Yii::$app->session->setFlash('error', ['錯誤 1', '錯誤 2']);
 * ```
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @author Alexander Makarov <sam@rmcreative.ru>
 */
class Alert extends \yii\bootstrap5\Widget
{
    /**
     * @var array 快閃消息的警報類型配置。
     * 此陣列設置為 $key => $value，其中：
     * - key: 會話快閃變數的名稱
     * - value: bootstrap 警報類型（例如 danger, success, info, warning）
     */
    public $alertTypes = [
        'error'   => 'alert-danger',
        'danger'  => 'alert-danger',
        'success' => 'alert-success',
        'info'    => 'alert-info',
        'warning' => 'alert-warning'
    ];
    /**
     * @var array 渲染關閉按鈕標籤的選項。
     * 陣列將傳遞給 [[\yii\bootstrap\Alert::closeButton]]。
     */
    public $closeButton = [];


    /**
     * 執行小部件。
     *
     * @return void 該方法不返回任何內容。
     */
    public function run()
    {
        $session = Yii::$app->session;
        $flashes = $session->getAllFlashes();
        $appendClass = isset($this->options['class']) ? ' ' . $this->options['class'] : '';

        foreach ($flashes as $type => $flash)
        {
            if (!isset($this->alertTypes[$type]))
            {
                continue;
            }

            foreach ((array) $flash as $i => $message)
            {
                echo \yii\bootstrap5\Alert::widget([
                    'body' => nl2br($message),
                    'closeButton' => $this->closeButton,
                    'options' => array_merge($this->options, [
                        'id' => $this->getId() . '-' . $type . '-' . $i,
                        'class' => $this->alertTypes[$type] . $appendClass,
                    ]),
                ]);
            }

            $session->removeFlash($type);
        }
    }
}
