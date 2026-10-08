<?php

namespace app\tests\interfaces;

interface VoteInterfaces
{
    /** 投票辨識碼 */
    const ANON_PARTY_VOTEID = 'AnonPartyTest';

    /** 投票辨識碼 */
    const ANON_NO_PARTY_VOTEID = 'AnonNoPartyTest';

    /** 場次碼 */
    const VOTE_SESSION = '3345678';

    /** 預設候選人配置顯示欄位 */
    const CANDI_CONFIG_DEF_FIELD_SORT = '[{"id":"num"},{"id":"Name"},{"id":"instName"},{"id":"title"}]';
}