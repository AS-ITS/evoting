<?php

namespace app\interfaces;

interface LogInterface
{
    /**
     * Log type
     */
    /** 投票建立 */
    const VOTE_CREATE = '100';
    /** 投票建立失敗 */
    const VOTE_CREATE_FAIL = '101';
    /** 投票編輯 */
    const VOTE_EDIT = '102';
    /** 投票編輯失敗 */
    const VOTE_EDIT_FAIL = '103';
    /** 投票另存 */
    const VOTE_SAVE_AS = '104';
    /** 投票另存失敗 */
    const VOTE_SAVE_AS_FAIL = '105';
    /** 投票重啟 */
    const VOTE_RESET = '106';
    /** 投票重啟失敗 */
    const VOTE_RESET_FAIL = '107';
    /** 投票刪除 */
    const VOTE_DELETE = '108';
    /** 投票基本資料編輯 */
    const VOTE_DETAIL_EDIT = '110';
    /** 投票基本資料編輯失敗 */
    const VOTE_DETAIL_EDIT_FAIL = '111';
    /** 投票短網址更新 */
    const VOTE_SHORT_URL_EDIT = '112';
    /** 投票短網址更新失敗 */
    const VOTE_SHORT_URL_EDIT_FAIL = '113';
    /** 投票分組編輯 */ 
    const VOTE_PARTY_EDIT = '120';
    /** 投票分組編輯失敗 */
    const VOTE_PARTY_EDIT_FAIL = '121';
    /** 投票者限制編輯 */
    const VOTE_VOTER_EDIT = '130';
    /** 投票者限制編輯失敗 */
    const VOTE_VOTER_EDIT_FAIL = '131';
    /** 投票附件上傳 */
    const VOTE_FILE_UPLOAD = '140';
    /** 投票附件上傳失敗 */
    const VOTE_FILE_UPLOAD_FAIL = '141';
    /** 投票附件刪除 */
    const VOTE_FILE_DELETE = '142';
    /** 投票附件刪除失敗 */
    const VOTE_FILE_DELETE_FAIL = '143';
    /** 候選人配置建立 */
    const VOTE_CANDI_CONFIG_CREATE = '150';
    /** 候選人配置建立失敗 */
    const VOTE_CANDI_CONFIG_CREATE_FAIL = '151';
    /** 候選人配置編輯 */
    const VOTE_CANDI_CONFIG_EDIT = '152';
    /** 候選人配置編輯失敗 */
    const VOTE_CANDI_CONFIG_EDIT_FAIL = '153';
    /** 候選人手動新增 */
    const VOTE_CANDI_MANUAL_CREATE = '154';
    /** 候選人手動新增失敗 */
    const VOTE_CANDI_MANUAL_CREATE_FAIL = '155';
    /** 候選人編輯 */
    const VOTE_CANDI_MANUAL_EDIT = '156';
    /** 候選人編輯失敗 */
    const VOTE_CANDI_MANUAL_EDIT_FAIL = '157';
    /** 候選人條件方式新增 */
    const VOTE_CANDI_CONDITION_CREATE = '158';
    /** 候選人條件方式新增失敗 */
    const VOTE_CANDI_CONDITION_CREATE_FAIL = '159';
    /** 候選人匯入方式新增 */
    const VOTE_CANDI_CSV_CREATE = '15A';
    /** 候選人匯入方式新增失敗 */
    const VOTE_CANDI_CSV_CREATE_FAIL = '15B';
    /** 候選人刪除 */
    const VOTE_CANDI_DELETE = '15C';
    /** 候選人刪除失敗 */
    const VOTE_CANDI_DELETE_FAIL = '15D';
    /** 候選人輪次導入 */
    const VOTE_CANDI_ROUND = '15E';
    /** 候選人輪次導入失敗 */
    const VOTE_CANDI_ROUND_FAIL = '15F';
    /** 密碼產生 */
    const VOTE_PASSWORD_GENERATE = '160';
    /** 密碼產生失敗 */
    const VOTE_PASSWORD_GENERATE_FAIL = '161';
    /** 密碼刪除 */
    const VOTE_PASSWORD_DELETE = '162';
    /** 密碼刪除失敗 */
    const VOTE_PASSWORD_DELETE_FAIL = '163';
    /** 密碼設定狀態 */
    const VOTE_PASSWORD_SET_STATUS = '164';
    /** 密碼設定狀態失敗 */
    const VOTE_PASSWORD_SET_STATUS_FAIL = '165';
    /** 密碼匯出 */
    const VOTE_PASSWORD_EXPORT = '166';
    /** 密碼列表明文解鎖 */
    const VOTE_PASSWORD_PLAINTEXT_UNLOCK = '167';
    /** 密碼列表明文鎖定 */
    const VOTE_PASSWORD_PLAINTEXT_LOCK = '168';
    /** 選票建立 */
    const VOTE_BALLOT_CREATE = '170';
    /** 選票建立失敗 */
    const VOTE_BALLOT_CREATE_FAIL = '171';
    /** 選票編輯 */
    const VOTE_BALLOT_EDIT = '172';
    /** 選票編輯失敗 */
    const VOTE_BALLOT_EDIT_FAIL = '173';
    /** 選票刪除 */
    const VOTE_BALLOT_DELETE = '174';
    /** 選票刪除失敗 */
    const VOTE_BALLOT_DELETE_FAIL = '175';
    /** 選票匯出 */
    const VOTE_BALLOT_EXPORT = '176';
    /** 選票匯入 */
    const VOTE_BALLOT_IMPORT = '177';
    /** 選票匯入失敗 */
    const VOTE_BALLOT_IMPORT_FAIL = '178';
    /** 開票設定建立 */
    const VOTE_COUNT_CONFIG_CREATE = '180';
    /** 開票設定建立失敗 */
    const VOTE_COUNT_CONFIG_CREATE_FAIL = '181';
    /** 開票設定編輯 */
    const VOTE_COUNT_CONFIG_EDIT = '182';
    /** 開票設定編輯失敗 */
    const VOTE_COUNT_CONFIG_EDIT_FAIL = '183';
    /** 計票單匯出 */
    const VOTE_COUNT_EXPORT = '184';
    /** 開票 */
    const VOTE_RESULT_MAKE = '190';
    /** 開票失敗 */
    const VOTE_RESULT_MAKE_FAIL = '191';
    /** 重新開票 */
    const VOTE_RESULT_REMAKE = '192';
    /** 重新開票失敗 */
    const VOTE_RESULT_REMAKE_FAIL = '193';
    /** 刪除開票 */
    const VOTE_RESULT_DELETE = '194';
    /** 刪除開票失敗 */
    const VOTE_RESULT_DELETE_FAIL = '195';
    /** 問題新增 */
    const QUESTION_CREATE = '200';
    /** 問題新增失敗 */
    const QUESTION_CREATE_FAIL = '201';
    /** 問題編輯 */
    const QUESTION_EDIT = '202';
    /** 問題編輯失敗 */
    const QUESTION_EDIT_FAIL = '203';
    /** 問題刪除 */
    const QUESTION_DELETE = '204';
    /** 問題刪除失敗 */
    const QUESTION_DELETE_FAIL = '205';
    /** 問題全部刪除 */
    const QUESTION_DELETE_ALL = '206';
    /** 問題匯入 */
    const QUESTION_IMPORT = '207';
    /** 問題匯入失敗 */
    const QUESTION_IMPORT_FAIL = '208';
    /** 群組新增 */ 
    const GROUP_CREATE = '300';
    /** 群組新增失敗 */
    const GROUP_CREATE_FAIL = '301';
    /** 群組編輯 */
    const GROUP_EDIT = '302';
    /** 群組編輯失敗 */
    const GROUP_EDIT_FAIL = '303';
    /** 群組成員建立 */
    const GROUP_MEMBER_CREATE = '310';
    /** 群組成員建立失敗 */
    const GROUP_MEMBER_CREATE_FAIL = '311';
    /** 群組成員編輯 */
    const GROUP_MEMBER_EDIT = '312';
    /** 群組成員編輯失敗 */
    const GROUP_MEMBER_EDIT_FAIL = '313';
    /** 群組成員刪除 */
    const GROUP_MEMBER_DELETE = '314';
    /** 群組成員刪除失敗 */
    const GROUP_MEMBER_DELETE_FAIL = '315';
    /** 網站設定修改 */
    const MANAGE_CONFIG_EDIT = '400';
    /** 網站設定修改失敗 */
    const MANAGE_CONFIG_EDIT_FAIL = '401';
    /** 使用者建立 */
    const USERS_CREATE = '410';
    /** 使用者建立失敗 */
    const USERS_CREATE_FAIL = '411';
    /** 使用者編輯 */
    const USERS_EDIT = '412';
    /** 使用者編輯失敗 */
    const USERS_EDIT_FAIL = '413';
    /** 使用者刪除 */
    const USERS_DELETE = '414';
    /** 使用者刪除失敗 */
    const USERS_DELETE_FAIL = '415';
    /** 管理員登入 */
    const LOGIN_ADMIN = '416';
    /** 管理員登入失敗 */
    const LOGIN_ADMIN_FAIL = '417';
    /** 管理員修改密碼 */
    const LOGIN_CHANGE_PASSWORD = '418';
    /** 資料表編輯器 SQL 查詢（僅 SELECT） */
    const SYSTEM_SQL_QUERY = '419';
    /** Console 危險命令已阻擋（正式環境） */
    const SYSTEM_CONSOLE_BLOCKED = '420';
    /** Console 危險命令覆寫允許（正式環境） */
    const SYSTEM_CONSOLE_OVERRIDE = '421';
    /** 管理員啟用 TOTP */
    const USERS_TOTP_ENABLE = '422';
    /** 管理員停用 TOTP */
    const USERS_TOTP_DISABLE = '423';
    /** 系統管理員同步 RBAC 權限 */
    const SYSTEM_RBAC_SYNC = '424';
    /** 匿名密碼登入 */
    const LOGIN_PASSWORD = '500';
    /** 匿名密碼登入失敗 */
    const LOGIN_PASSWORD_FAIL = '501';
    /** 記名密碼登入 */
    const LOGIN_VOTER = '502';
    /** 記名密碼登入失敗 */
    const LOGIN_VOTER_FAIL = '503';
    /** 建立新一輪投票 */
    const VOTE_ROUND_CREATE = '601';
    /** 建立新一輪投票失敗 */
    const VOTE_ROUND_CREATE_FAIL = '602';
    /** 輪次編輯 */
    const VOTE_ROUND_EDIT = '603';
    /** 輪次編輯失敗 */
    const VOTE_ROUND_EDIT_FAIL = '604';
    /** 輪次切換 */
    const VOTE_ROUND_SWITCH = '605';
    /** 輪次切換失敗 */
    const VOTE_ROUND_SWITCH_FAIL = '606';
    /** 問題特殊規則新增 */
    const QUESTIONS_GROUP_RULE_CREATE = '701';
    /** 問題特殊規則新增失敗 */
    const QUESTIONS_GROUP_RULE_CREATE_FAIL = '702';
    /** 問題特殊規則修改 */
    const QUESTIONS_GROUP_RULE_UPDATE = '703';
    /** 問題特殊規則修改失敗 */
    const QUESTIONS_GROUP_RULE_UPDATE_FAIL = '704';
    /** 樣板新增 */
    const TEMPLATE_CREATE = '801';
    /** 樣板新增失敗 */
    const TEMPLATE_CREATE_FAIL = '802';
    /** 樣板修改 */
    const TEMPLATE_UPDATE = '803';
    /** 樣板修改失敗 */
    const TEMPLATE_UPDATE_FAIL = '804';
    /** 樣板刪除 */
    const TEMPLATE_DELETE = '805';
    /** 樣板刪除失敗 */
    const TEMPLATE_DELETE_FAIL = '806';
}