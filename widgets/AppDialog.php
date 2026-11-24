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
// BootstrapDialog 的 updateZIndex 固定用 Bootstrap 3 的 1050，且只計算自己開的對話框；
// 從已開啟的 Bootstrap 5 對話框（z-index 1055，如附件上傳）內再開確認框時，確認框會被蓋住、按不到。
// 改為疊在所有已開啟的對話框之上（遮罩也一併提高，蓋住下層對話框）。
if (window.BootstrapDialog && BootstrapDialog.prototype.updateZIndex) {
    var origUpdateZIndex = BootstrapDialog.prototype.updateZIndex;
    BootstrapDialog.prototype.updateZIndex = function () {
        origUpdateZIndex.apply(this, arguments);
        if (!this.isOpened()) {
            return this;
        }
        var $modal = this.getModal();
        var top = 0;
        $('.modal.show').not($modal).each(function () {
            top = Math.max(top, parseInt($(this).css('z-index'), 10) || 0);
        });
        if (top && top >= (parseInt($modal.css('z-index'), 10) || 0)) {
            $modal.css('z-index', top + 10);
            $('.modal-backdrop').last().css('z-index', top + 5);
        }
        return this;
    };
}

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
