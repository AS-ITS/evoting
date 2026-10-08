<?php
namespace app\models;

use Yii;
use yii\base\BaseObject;
use app\interfaces\HandleSwitchInterface;

/**
 * 處理切換事件
 * 
 */
class HandleSwitch extends BaseObject implements HandleSwitchInterface
{
    /**
     * 語言切換後須處理事件
     *
     * @param string $lang 使用者切換後的語言
     * 
     * @return void
     */
    public function switchLang($lang)
    {
        Yii::debug('HandleSwitch->switchLang:'.$lang, __METHOD__);
        $referrer = Yii::$app->request->getReferrer();
        if(!is_null($referrer)) {
            $urlParts = parse_url($referrer);
            $path = explode('/', $urlParts['path']);
            // 處理投票頁面自定義欄位排序
            if (end($path) == 'start-vote') {
                parse_str($urlParts['query'], $queryParams);
                $columns = ['otherColA', 'otherColB', 'otherColC', 'otherColD', 'otherColE', 'otherColF'];
                foreach ($queryParams as $key => $value) {
                    foreach ($columns as $column) {
                        if ($lang == 'en-US' && ($value == $column || $value == '-'.$column)) {
                            $queryParams[$key] = $value.'E';
                        } elseif ($lang == 'zh-TW' && ($value == $column.'E' || $value == '-'.$column.'E')) {
                            $queryParams[$key] = str_replace('E', '', $value);
                        }
                    }
                }
                $urlParts['query'] = http_build_query($queryParams);
                $newUrl = $urlParts['scheme'].'://'.$urlParts['host'].$urlParts['path'].'?'.$urlParts['query'];
                Yii::$app->request->headers->set('Referer', $newUrl);
            }
        }
    }

    /**
     * 角色切換後須處理事件
     *
     * @param string $type 配置登入的 User flag
     * @param string $role 切換的角色
     * 
     * @return void
     */
    public function switchRole($type, $role)
    {
        Yii::debug('HandleSwitch->switchRole:'.$type.':::'.$role, __METHOD__);

        $auth = Yii::$app->authManager;
        $userId = Yii::$app->user->identity->getId();
        $rbacRole = $auth->getRole($role);

        if (!empty($rbacRole)) {
            $auth->revokeAll($userId);
            $auth->assign($rbacRole, $userId);
        }
        else {
            throw new \yii\web\HttpException(403, "No such RBAC role as '$role'" );
        }
    }

    /**
     * 頁數切換後須處理事件
     *
     * @param string $page 切換後的頁數
     * 
     * @return void
     */
    public function switchPages($page)
    {
        Yii::debug('HandleSwitch->switchPages:'.$page, __METHOD__);
    }
}