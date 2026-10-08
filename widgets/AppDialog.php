<?php
namespace app\widgets;

use kartik\dialog\Dialog;
use yii\base\Widget;
use yii\web\View;

/**
 * 全站訊息框：confirm / 提示 / 警告 / 錯誤。
 * 於 layout 註冊一次，覆蓋 Kartik Grid 內建 krajeeDialog 預設。
 */
class AppDialog extends Widget
{
    public function run()
    {
        echo Dialog::widget([
            'libName' => 'krajeeDialog',
            'overrideYiiConfirm' => true,
            'options' => [
                'size' => Dialog::SIZE_LARGE,
            ],
            'dialogDefaults' => [
                Dialog::DIALOG_ALERT => [
                    'type' => Dialog::TYPE_INFO,
                    'title' => '提示',
                    'size' => Dialog::SIZE_LARGE,
                ],
                Dialog::DIALOG_CONFIRM => [
                    'type' => Dialog::TYPE_WARNING,
                    'title' => '確認提示',
                    'size' => Dialog::SIZE_LARGE,
                    'btnOKClass' => 'btn-warning',
                ],
            ],
        ]);

        $this->view->registerJs(<<<'JS'
window.appDialog = {
    info: function (message, title) {
        if (!window.BootstrapDialog) {
            window.alert(message);
            return;
        }
        if (title) {
            BootstrapDialog.alert({
                size: BootstrapDialog.SIZE_LARGE,
                type: BootstrapDialog.TYPE_INFO,
                title: title,
                message: message,
                buttonLabel: '確定',
                closable: false
            });
            return;
        }
        krajeeDialog.alert(message);
    },
    warn: function (message) {
        if (!window.BootstrapDialog) {
            window.alert(message);
            return;
        }
        BootstrapDialog.alert({
            size: BootstrapDialog.SIZE_LARGE,
            type: BootstrapDialog.TYPE_WARNING,
            title: '提示',
            message: message,
            buttonLabel: '確定',
            closable: false
        });
    },
    error: function (message) {
        if (!window.BootstrapDialog) {
            window.alert(message);
            return;
        }
        BootstrapDialog.alert({
            size: BootstrapDialog.SIZE_LARGE,
            type: BootstrapDialog.TYPE_DANGER,
            title: '錯誤',
            message: message,
            buttonLabel: '確定',
            closable: false
        });
    },
    confirm: function (message, callback) {
        if (window.krajeeDialog) {
            krajeeDialog.confirm(message, callback);
            return;
        }
        callback(window.confirm(message));
    },
    infoWide: function (message, title) {
        if (!window.BootstrapDialog) {
            window.alert(message);
            return;
        }
        BootstrapDialog.alert({
            size: BootstrapDialog.SIZE_WIDE,
            type: BootstrapDialog.TYPE_INFO,
            title: title || '圈選須知參數',
            message: message,
            buttonLabel: '確定',
            closable: false
        });
    }
};
JS
        , View::POS_READY);
    }
}
