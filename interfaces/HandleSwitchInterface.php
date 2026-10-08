<?php
namespace app\interfaces;

/**
 * 這是處理開關切換相關事件的介面(Interface)
 *
 * 例如，以下代碼展示如何應用
 *
 * ```php
 *  class HandleSwitch implements HandleSwitchInterface
 *  {
 *      public function switchLang($lang) // 語言切換後須處理事件
 *      {
 *          // ...
 *      }
 *
 *      public function switchRole($type, $role) // 角色切換後須處理事件
 *      {
 *          // ...
 *      }
 *
 *      public function switchPages($page) // 頁數切換後須處理事件
 *      {
 *          // ...
 *      }
 *  }
 * ```
 */
interface HandleSwitchInterface
{
    /**
     * 語言切換後須處理事件
     *
     * @param string $lang 使用者切換後的語言
     *
     * @return void
     */
    public function switchLang($lang);

    /**
     * 角色切換後須處理事件
     *
     * @param string $type 配置登入的 User flag
     * @param string $role 切換的角色
     *
     * @return void
     */
    public function switchRole($type, $role);

    /**
     * 頁數切換後須處理事件
     *
     * @param string $page 切換後的頁數
     *
     * @return void
     */
    public function switchPages($page);
}