<?php

namespace app\models;

use Yii;
use yii\base\BaseObject;
use yii\web\IdentityInterface;
use app\interfaces\UserIdentityInterface;

/**
 * This is the model class for UserIdentity.
 */
class UserIdentity extends BaseObject implements IdentityInterface, UserIdentityInterface
{
    /** @var array $authData 使用者授權資料 */
    public $authData;
    /** @var array $role 當前角色 */
    public $role;
    /** @var array $roles 所有可用角色資料 */
    public $roles;

    /**
     * 同步所有使用者數值
     *
     * @return true
     */
    public function syncAttr()
    {
        $this->authData = $this->getAuthData();
        $this->role  = $this->getRole();
        $this->roles = $this->getRoles();
        // 不把 Identity 物件寫入 session（易導致 session 檔損壞／遺失）
        return true;
    }

    /**
     * 作為 Yii2 授權識別欄位，此值將作為 Yii2 授權欄位的flag
     *
     * @return string
     */
    public function getUnique()
    {
        return 'id';
    }

    /**
     * 取得"Identity"
     *
     * @param string $id 使用者識別碼
     *
     * @return IdentityInterface 取得 Identity 物件
     */
    public function getIdentity($id=null)
    {
        $authData = $this->getAuthData();
        if ($authData === [] || $authData === null) {
            return null;
        }
        if ($id !== null && ($authData[$this->getUnique()] ?? null) != $id) {
            return null;
        }
        $this->authData = $authData;
        $this->role = $this->getRole();
        $this->roles = $this->getRoles();
        return $this;
    }

    /**
     * 設定"Identity"
     *
     * @param IdentityInterface|null $identity 要存入 session 的身分；省略則存 $this
     *
     * @return bool
     */
    public function setIdentity($identity = null)
    {
        // BC no-op：身分以 AuthData/Role/Roles 陣列持久化，不再序列化物件
        if ($identity instanceof self) {
            $auth = $identity->getAuthData();
            if ($auth) {
                $this->setAttr(self::ATTR_AUTH_DATA, $auth);
            }
        }
        // 清掉歷史遺留的物件，避免下次 session_start 解碼失敗
        $key = $this->getKey(self::ATTR_IDENTITY);
        if (Yii::$app->session->has($key)) {
            Yii::$app->session->remove($key);
        }
        return true;
    }
    
    /**
     * 取得使用者授權資料
     *
     * @return void
     */
    public function getAuthData($key=null)
    {
        $authData = $this->getAttr(self::ATTR_AUTH_DATA) ?? [];
        if ($key === null) {
            return $authData;
        }

        return $authData[$key] ?? null;
    }
    
    /**
     * 設定使用者授權資料
     *
     * @param  mixed $payload
     * @return void
     */
    public function setAuthData($payload)
    {
        $this->setAttr(self::ATTR_AUTH_DATA, $payload);
        return $this->syncAttr();
    }
    
    /**
     * 取得當前角色
     *
     * @return string
     */
    public function getRole()
    {
        return $this->getAttr(self::ATTR_ROLE);
    }

    /**
     * 配置"使用者當前角色"
     *
     * @param string $role 特定角色
     *
     * @return bool 是否執行成功
     */
    public function setRole(string $role)
    {
        $this->setAttr(static::ATTR_ROLE, $role);
        return $this->syncAttr();
    }
    
    /**
     * 取得"使用者所有可用角色"
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->getAttr(self::ATTR_ROLES);
    }
    
    /**
     * 配置"使用者所有可用角色"
     *
     * @param  array $roles
     * @return void
     */
    public function setRoles(array $roles)
    {
        $this->setAttr(self::ATTR_ROLES, $roles);
        return $this->syncAttr();
    }

    /**
     * 是否具備多角色
     *
     * @return bool 是否具備多角色
     */
    public function isSwitchRoles()
    {
        $roles = $this->getRoles();
        if (empty($roles)) {
            return false;
        }

        return (count($roles) > 1);
    }
    
    /**
     * 是否擁有指定角色
     *
     * @param  mixed $checkRoles 要檢查的角色（字串或陣列）
     * @return bool 是否擁有該角色
     */
    public function inUserRole($checkRoles)
    {
        $role = $this->getRole();

        // 如果沒有角色，返回 false
        if (empty($role)) {
            return false;
        }

        if (is_string($checkRoles)) {
            return $role == $checkRoles;
        }
        elseif (is_array($checkRoles)) {
            return in_array($role, $checkRoles);
        }
        else {
            return false;
        }
    }

    /**
     * 取得使用者物件內容
     *
     * @param string $key 鍵值，預設為 self::$flag.'.'.$key
     *
     * @return mixed
     */
    public function getAttr($key)
    {
        return Yii::$app->session->get($this->getKey($key));
    }

    /**
     * 配置使用者物件內容
     *
     * @param mixed $key 存入的鍵值
     * @param mixed $value 存入的內容
     *
     * @return $this
     */
    public function setAttr($key, $value)
    {
        Yii::$app->session->set($this->getKey($key), $value);

        return $this;
    }

    /**
     * 取得紀錄之使用者物件名
     *
     * @param string $name 鍵值名稱
     *
     * @return string
     */
    public function getKey($name)
    {
        return $this->getFlag().'.'.$name;
    }

    /**
     * 取得登入之使用者物件名
     *
     * @return string
     */
    public function getFlag()
    {
        $className = basename(str_replace('\\', '/', static::class));
        $classNameWithoutIdentity = preg_replace('/Identity$/', '', $className);
        return $classNameWithoutIdentity;
    }

    /**
     * @return int|string 當前用户ID
     */
    public function getId()
    {
        $id = $this->getAuthData($this->getUnique());
        // logout 時 session attr 可能已清，避免回傳 array 觸發 "Array to string conversion"
        return is_array($id) ? null : $id;
    }

    /**
     * 取得使用者帳號 (cn)
     *
     * @return string|null
     */
    public function getCn()
    {
        return $this->getAuthData('cn');
    }

    /**
     * 取得使用者姓名 (name)
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getAuthData('name');
    }

    /**
     * 根據给到的ID查詢身分。
     *
     * @param string|integer $id 被查詢的ID
     * @return IdentityInterface|null 通過ID比對到的身分對象
     */
    public static function findIdentity($id)
    {
        return (new static)->getIdentity($id);
    }

    /**
     * 根據 token 查詢身份。
     *
     * @param string $token 被查詢的 token
     * @return IdentityInterface|null 通過 token 得到的身分對象
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        // 
    }

    /**
     * @return string 當前用戶的（cookie）認證金鑰
     */
    public function getAuthKey()
    {
        // 
    }

    /**
     * @param string $authKey
     * @return boolean if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        // 
    }

    /**
     * 使用者登出
     *
     * 此方法用於登出使用者，並根據 $destroySession 決定是否銷毀 session 資料。
     *
     * @param bool $destroySession 是否銷毀 session，預設為 true
     * @param string|null $flag 用於取得應用程式組件的旗標（flag），預設為 lcfirst($this->getFlag())
     * @return bool 登出是否成功
     *
     * @see \yii\helpers\StringHelper::startsWith
     * @link yii\web\User::logout() https://www.yiiframework.com/doc/api/2.0/yii-web-user#logout()-detail
     * @uses Yii::$app->session 由 session 取得語言設定，並重新設定
     */
    public function logout($destroySession = true, $flag = null)
    {
        // 若未指定旗標，則使用預設值
        if ($flag === null)
        {
            $flag = lcfirst($this->getFlag());
        }

        $language = Yii::$app->language;

        // 使用參數 $flag 取得對應的應用程式組件並執行登出
        Yii::$app->{$flag}->logout($destroySession);

        // 若不銷毀 session，則清除符合特定條件的 session 資料
        if($destroySession === false)
        {
            $session = Yii::$app->session;
            foreach ($session as $key => $value)
            {
                // 檢查 $key 是否以 $this->getKey('') 為開頭
                if(\yii\helpers\StringHelper::startsWith( $key, $this->getKey('')))
                {
                    $session->remove($key);
                }
            }
        }

        $lang = Yii::$app->lang;
        // 將語言資訊存回 session
        Yii::$app->session->set($lang::SESSION_NAME, $language);

        return true;
    }

}
