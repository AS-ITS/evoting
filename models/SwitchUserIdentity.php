<?php
namespace app\models;

use Yii;

/**
 * 用於使用者角色切換及授權資料修改
 */
class SwitchUserIdentity extends \yii\base\Component
{
    /** @var string $switchRoleAction 切換角色動作名稱 */
    public $switchRoleAction = '/site/switch-role';

    /**
     * 角色切換，用於共用 action
     *
     * @param string $chgRole 要切換的角色
     * @param string $type 配置登入的 User flag
     *
     * @return bool 是否成功切換角色
     */
    public function actionSwitchRole($chgRole, $type='user')
    {
        /** @var \app\models\UserIdentity $Identity 使用者授權元件 */
        $Identity = Yii::$app->{$type}->identity;

        /** @var bool $check 是否可切換角色 */
        $check = $this->checkSwitchRole($chgRole, $type);
        if($check === false) {
            return false;
        }

        // 設定角色
        $Identity->setRole($chgRole);
        Yii::debug("已設定角色為「{$chgRole}」。",__METHOD__);
        return true;
    }

    /**
     * 檢查角色是否有權限切換
     *
     * @param string $chgRole 要切換的角色
     * @param string $type 配置登入的 User flag
     *
     * @return bool 是否允許切換角色
     */
    public function checkSwitchRole($chgRole, $type='user')
    {
        /** @var \apps\models\UserIdentity $Identity 使用者授權元件 */
        $Identity = Yii::$app->{$type}->identity;

        // 未登入
        if(Yii::$app->{$type}->isGuest) {
            return false;
        }

        $roles = $Identity->getRoles(); // 取得所有角色

        if(in_array($chgRole, $roles)) {
            Yii::debug("允許設定角色為「{$chgRole}」。", __METHOD__);
            return true;
        }

        Yii::debug("請檢查角色及該使用者授權角色是否有問題。",__METHOD__);
        return false;
    }
}