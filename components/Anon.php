<?php
namespace app\components;

use Yii;
use app\models\Logins;


/**
 * 匿名投票
 * 
 * @link 英文縮寫參考 https://www.abbreviations.com/term/72495
 */
class Anon extends \app\models\UserIdentity
{
    public static $flag = 'Anon';

    /**
     * 取得唯一的鍵值
     * 
     * @return string
     */
    public function getUnique()
    {
        return 'id';
    }

    /**
     * 登入後事件處理
     *
     * @param  mixed $identity
     * @return void
     */
    public function afterLogin($identity)
    {
        $auth = is_array($identity->authData ?? null) ? $identity->authData : [];
        $creator = $auth['id'] ?? $identity->getId();
        if ($creator === null || $creator === '') {
            return;
        }

        $model = Logins::findOne(['creator' => $creator]);
        if (empty($model)) {
            $model = new Logins;
            $model->voteID = $auth['voteID'] ?? null;
            $model->party = $auth['party'] ?? null;
            $model->creator = $creator;
            $model->session = Yii::$app->session->id;
        }
        else {
            if (!empty($model->session)) {
                SessionInvalidator::destroyById((string) $model->session);
            }
            $model->session = Yii::$app->session->id;
        }

        $model->save(false);
    }
    
    /**
     * 登出後事件處理
     *
     * @param  mixed $identity
     * @return void
     */
    public function afterLogout($identity)
    {
        if ($identity === null) {
            return;
        }

        $auth = is_array($identity->authData ?? null) ? $identity->authData : [];
        $creator = $auth['id'] ?? $identity->getId();
        if ($creator === null || $creator === '' || is_array($creator)) {
            return;
        }

        $model = Logins::findOne(['creator' => $creator]);
        if (!empty($model)) {
            $model->delete();
        }
    }
}
