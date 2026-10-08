<?php

namespace app\traits;

use Yii;
use yii\helpers\Url;
use app\models\Logs;
use app\models\Users;
use yii\helpers\Json;
use app\components\SensitiveReauth;

/**
 * 密碼列表二次驗證後明文顯示（session 時效解鎖）
 */
trait PlaintextUnlockTrait
{
    protected function performUnlockPlaintext($voteID, string $defaultAction)
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return $this->redirect($this->resolvePlaintextReturnUrl(null, $voteID, $defaultAction));
        }

        $returnUrl = $this->resolvePlaintextReturnUrl(
            $request->post('returnUrl'),
            $voteID,
            $defaultAction
        );

        if (!SensitiveReauth::isRequired()) {
            SensitiveReauth::grantPlaintextUnlock();
            return $this->redirect($returnUrl);
        }

        $adminUser = Users::findOne(Yii::$app->user->id);
        if (!SensitiveReauth::verify(
            $request->post('adminPassword'),
            $request->post('totpCode')
        )) {
            Yii::$app->session->setFlash(
                'error',
                $adminUser
                    ? SensitiveReauth::failureMessage($adminUser)
                    : Yii::t('app', '二次驗證失敗。')
            );
            return $this->redirect($returnUrl);
        }

        SensitiveReauth::grantPlaintextUnlock();
        Logs::add(Logs::VOTE_PASSWORD_PLAINTEXT_UNLOCK, Json::encode([
            'voteID' => $voteID,
            'ttl' => SensitiveReauth::plaintextUnlockTtlSeconds(),
        ], 336), ['voteID' => $voteID]);
        Yii::$app->session->setFlash('success', '已解鎖密碼明文顯示。');

        return $this->redirect($returnUrl);
    }

    protected function performLockPlaintext($voteID, string $defaultAction)
    {
        $request = Yii::$app->request;
        $returnUrl = $this->resolvePlaintextReturnUrl(
            $request->post('returnUrl', $request->get('returnUrl')),
            $voteID,
            $defaultAction
        );

        if ($request->isPost) {
            SensitiveReauth::revokePlaintextUnlock();
            Logs::add(Logs::VOTE_PASSWORD_PLAINTEXT_LOCK, Json::encode(compact('voteID'), 336), ['voteID' => $voteID]);
            Yii::$app->session->setFlash('info', '已鎖定密碼明文顯示。');
        }

        return $this->redirect($returnUrl);
    }

    protected function resolvePlaintextReturnUrl(?string $returnUrl, $voteID, string $defaultAction): string
    {
        if ($returnUrl !== null && $returnUrl !== '') {
            $returnUrl = trim($returnUrl);
            if (str_starts_with($returnUrl, '/') && !str_starts_with($returnUrl, '//')) {
                return $returnUrl;
            }
        }

        return Url::to([$defaultAction, 'voteID' => $voteID]);
    }
}
