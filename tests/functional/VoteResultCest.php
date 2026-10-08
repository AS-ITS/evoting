<?php
/**
 * 執行 php vendor/bin/codecept run functional tests/functional/VoteResultCest.php
*/

use Codeception\Scenario;

class VoteResultCest
{
    // 儲存每個測試的交易物件
    protected $transaction;

    public function _before(FunctionalTester $I, Scenario $scenario)
    {
        // 關閉 Warning 顯示
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        
        // 開始交易以避免對資料庫造成變更
        $this->transaction = Yii::$app->db->beginTransaction();

        $I->amOnPage('/vote/result-list');
    }

    public function _after(FunctionalTester $I)
    {
        // 測試結束後回滾交易
        if ($this->transaction && $this->transaction->isActive) {
            $this->transaction->rollBack();
        }
    }

    /**
     * 確認投票結果頁面正常顯示
     */
    public function openVoteResultPage(FunctionalTester $I)
    {
        $I->see('投票結果', 'h3');
    }
}
