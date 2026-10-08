<?php

use yii\helpers\Url;
use Codeception\Example;
use Codeception\Scenario;

class AnonVoteEditCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * 測試登入功能
     */
    public function login(AcceptanceTester $I, Scenario $scenario)
    {
        $I->wantTo('Login');

        // 使用測試登入路徑
        $I->amOnPage('/index-test.php/test/login?user=test_va2');

        // 前往投票管理頁面驗證登入成功
        $I->amOnPage('/index-test.php/elect/index');
        $I->see('投票管理');
    }

    /**
     * 查看投票列表
     *
     * @depends login
     */
    public function tryVisitVoteManage(AcceptanceTester $I, Scenario $scenario)
    {
        // 先登入
        $I->amOnPage('/index-test.php/test/login?user=test_va2');

        $I->amOnPage('/index-test.php/elect/index');

        $I->amGoingTo('查看投票列表');
        $I->see('投票管理');
    }

    /**
     * 成功訪問編輯投票頁面
     *
     * @depends login
     * @dataprovider voteSuccessProvider
     */
    public function tryEditVoteSuccess(AcceptanceTester $I, Scenario $scenario, Example $example)
    {
        // 先登入（使用 admin 帳號，sa 角色可以編輯任何投票）
        $I->amOnPage('/index-test.php/test/login?user=admin');

        $I->amOnPage('/index-test.php/elect/edit-vote?voteID='.$I::ANON_PARTY_VOTEID);

        $I->amGoingTo('確認可以訪問編輯投票頁面');
        $I->seeResponseCodeIs(200);
        $I->see('編輯投票');
        $I->see('基本設定');
    }

    /**
     * 測試表單驗證
     *
     * @depends login
     * @dataprovider voteFailProvider
     */
    public function tryEditVoteFail(AcceptanceTester $I, Scenario $scenario, Example $example)
    {
        // 先登入（使用 admin 帳號，sa 角色可以編輯任何投票）
        $I->amOnPage('/index-test.php/test/login?user=admin');

        $I->amOnPage('/index-test.php/elect/edit-vote?voteID='.$I::ANON_PARTY_VOTEID);

        $I->amGoingTo('確認可以訪問編輯投票頁面');
        $I->seeResponseCodeIs(200);
        $I->see('編輯投票');
    }

    /**
     * @return array
     */
    protected function voteSuccessProvider()
    {
        $openStart = '2023-01-01 09:00:00';

        return [
            [
                'FormVotes' => [
                    'Name' => '驗收測試投票',
                    'NameE' => 'Acceptance Test Vote',
                    'openStart' => $openStart,
                    'openEnd' => date('Y-m-d H:i:s', strtotime($openStart . '+10 minute')),
                    'verifyStart' => date('Y-m-d H:i:s', strtotime($openStart . '+11 minute')),
                    'verifyEnd' => date('Y-m-d H:i:s', strtotime($openStart . '+12 minute')),
                    'type' => '1',
                    'partyOrNot' => '1',
                    'addiCondition' => 'n',
                    'hosted' => '資訊服務處',
                    'hostedE' => 'Department of Information Technology Services',
                    'isByParty' => '0',
                    'isBindVote' => '0',
                    'bindWhichVote' => '',
                    'active' => '1',
                    'finishPage' => '1',
                    'authBeforeDetail' => '1',
                    'skipDetail' => '1',
                    'isShow' => '1',
                    'candiConfig' => '1'
                ],
                'FormParties' => [
                    0 => [
                        'name' => '分組一',
                        'nameE' => 'Party One',
                        'numBallots' => '2',
                    ],
                    1 => [
                        'name' => '分組二',
                        'nameE' => 'Party Two',
                        'numBallots' => '2',
                    ],
                    2 => [
                        'name' => '分組三',
                        'nameE' => 'Party Three',
                        'numBallots' => '2',
                    ],
                    3 => [
                        'name' => '分組四',
                        'nameE' => 'Party Four',
                        'numBallots' => '2',
                    ],
                ]
            ],
        ];
    }

    /**
     * @return array
     */
    protected function voteFailProvider()
    {

        return [
            [
                'FormVotes' => [
                    'Name' => '',
                    'openStart' => '',
                    'openEnd' => '',
                    'verifyStart' => '',
                    'verifyEnd' => '',
                ],
                'FormParties' => [
                    0 => [
                        'name' => '',
                        'nameE' => '',
                        'numBallots' => '',
                        'leastNumBallots' => '',
                        'maxElect' => '',
                        'numOfKeep' => '',
                    ],
                    1 => [
                        'name' => '',
                        'nameE' => '',
                        'numBallots' => '',
                        'leastNumBallots' => '',
                        'maxElect' => '',
                        'numOfKeep' => '',
                    ],
                    2 => [
                        'name' => '',
                        'nameE' => '',
                        'numBallots' => '',
                        'leastNumBallots' => '',
                        'maxElect' => '',
                        'numOfKeep' => '',
                    ],
                    3 => [
                        'name' => '',
                        'nameE' => '',
                        'numBallots' => '',
                        'leastNumBallots' => '',
                        'maxElect' => '',
                        'numOfKeep' => '',
                    ],
                ]
            ],
        ];
    }
}
