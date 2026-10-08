<?php
namespace app\interfaces;

/**
 * 使用者Yii2授權的Interface
 */
interface UserIdentityInterface
{
    /**
     * Yii2 的授權物件 (session name: User.Identity)
     * @var string
     **/
    const ATTR_IDENTITY = 'Identity';

    /**
     * 使用者認證資料 (session name: User.AuthNData)
     * @var string
     **/
    const ATTR_AUTH_DATA = 'AuthData';

    /**
     * 所有可用角色資料 (session name: User.Roles)
     * @var string
     **/
    const ATTR_ROLES = 'Roles';
    
    /**
     * 當前角色 (session name: User.Role)
     * @var string
     **/
    const ATTR_ROLE = 'Role';

    /**
     * 作為 Yii 使用者識別欄位，此值將作為 Yii 識別用戶的 flag
     *
     * @return string
     */
    public function getUnique();

    /**
     * 取得"Identity"
     *
     * @param string $id 使用者識別碼
     *
     * @return IdentityInterface 取得 Identity 物件
     */
    public function getIdentity($id=null);

    /**
     * 取得"使用者認證資料"
     *
     * @param null|string $key 是否取得特定認證資料的項目，如不用則回傳全部
     *
     * @return array|string 回傳"所有"或"特定項目"的使用者認證資料
     */
    public function getAuthData($key=null);
}