<?php

use Codeception\Scenario;
use app\models\Config;

class VoteListCest
{
    public function _before(FunctionalTester $I, Scenario $scenario)
    {
        $I->amOnPage('/vote/index');
    }

    /**
     * 確認首頁開放中之投票項目正常顯示
     */
    public function openVoteIndexPage(FunctionalTester $I)
    {
        // 取出使用者資料
        $user = Config::find()->one();
        if ($user && $user->homeLayout == 'meeting') {
            $I->seeResponseCodeIs(200);
        } else {
            $I->seeResponseCodeIs(200);
            $I->see('開放中之投票項目');
        }
    }
}
