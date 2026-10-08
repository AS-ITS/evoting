<?php

namespace app\components;

use Yii;

/**
 * 可從無法讀取的 session 復原的 Session。
 *
 * 注意：失敗時只換新 session id，不 unlink 舊檔。
 * Debug module 在 shutdown flush 時可能在 session_write_close 之後又觸發 open；
 * 若此時 open 失敗就刪檔，會把剛寫入的登入狀態清掉（表現成下一頁被登出）。
 */
class RecoverableSession extends \yii\web\Session
{
    public function open()
    {
        if ($this->getIsActive()) {
            return;
        }

        parent::open();

        if ($this->getIsActive()) {
            return;
        }

        $oldId = session_id();
        Yii::warning(
            'Session failed to start; starting a new session id without deleting old file'
            . ($oldId !== '' ? ' (old=' . substr($oldId, 0, 8) . '***)' : ''),
            __METHOD__
        );

        // 只丟棄無法使用的 id，讓 GC / 手動清除處理舊檔；禁止 unlink 當前檔
        $this->setHasSessionId(false);
        if (session_id() !== '') {
            session_id('');
        }

        parent::open();

        if (!$this->getIsActive()) {
            Yii::error('Session recovery failed; session remains inactive', __METHOD__);
        }
    }
}
