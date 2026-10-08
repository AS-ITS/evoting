<?php

use Codeception\Example;
use yii\helpers\Url;
use Codeception\Scenario;

/**
 * 測試首頁開放中之投票項目及投票結果正常顯示
 */
class VoteCest
{
    /**
     * 在每個測試方法執行前初始化（確保投票狀態為進行中）
     */
    public function _before(AcceptanceTester $I)
    {
        // 重設投票狀態為進行中 (active=1) 和時間範圍
        $now = new \DateTime();
        $openStart = (clone $now)->modify('-1 day');
        $openEnd = (clone $now)->modify('+1 day');
        $verifyStart = (clone $openEnd)->modify('+1 hour');
        $verifyEnd = (clone $verifyStart)->modify('+1 hour');

        // 更新 AnonPartyTest 的狀態
        \Yii::$app->db->createCommand()->update('votes', [
            'active' => '1',  // 進行中
            'skipDetail' => '0',  // 不跳過詳情頁面
            'openStart' => $openStart->format('Y-m-d H:i:s'),
            'openEnd' => $openEnd->format('Y-m-d H:i:s'),
            'verifyStart' => $verifyStart->format('Y-m-d H:i:s'),
            'verifyEnd' => $verifyEnd->format('Y-m-d H:i:s'),
        ], ['voteID' => 'AnonPartyTest'])->execute();
    }

    /**
     * 確認首頁開放中之投票項目正常顯示
     *
     * @dataprovider voteProvider
     */
    public function ensureVoteHomePageWork(AcceptanceTester $I, Scenario $scenario, Example $example)
    {
        $I->amOnPage('index.php/vote/index');


        $I->amGoingTo('查看中文版投票場次');
        $I->see('匿名投票');
        $I->see($example['name']);

        $I->amGoingTo('查看英文版投票場次');
        $I->click('English Version');
        $I->see('Anon');
    }

    /**
     * 確認投票資訊頁面
     * 
     * @dataprovider voteProvider
     */
    public function ensureVoteDetailPageWork(AcceptanceTester $I, Scenario $scenario, Example $example)
    {
        $I->amOnPage('index.php/vote/vote-detail?voteID='.$example['voteID']);

        $I->amGoingTo('確認中文投票資訊');
        $I->see($example['name'], 'strong');
        $I->see('我要投票');

        $I->amGoingTo('確認英文投票資訊');
        $I->click('EN');
        $I->see($example['nameE'], 'strong');
        $I->see('I wish to cast my ballot');
    }

    /**
     * 確認投票結果頁面
     *
     * @dataprovider voteProvider
     */
    public function ensureVoteResultListPageWork(AcceptanceTester $I, Scenario $scenario, Example $example)
    {
        $I->amOnPage('index-test.php/vote/result-list');

        $I->amGoingTo('查看投票結果列表頁面');
        // 驗證頁面正常載入
        $I->seeResponseCodeIs(200);
    }

    /**
     * 投票資料
     * 
     * @return array
     */
    protected function voteProvider()
    {
        return [
            [
                'voteID' => 'AnonPartyTest',
                'name' => '測試分組匿名投票',
                'nameE' => 'Test Party Anon'
            ]
        ];
    }
}
    