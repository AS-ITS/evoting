<?php

use Codeception\Example;
use app\models\FormResults;
use app\models\FormVotes;
use app\models\Votes;

/**
 * 投票流程 CSV 驅動 E2E（P4）
 *
 * 前置：
 * php -S localhost:8080 -t web web/router-test.php
 * echo yes | php yii fixture/load "Votes,Parties,Round,Questions,CandiData,Passwords,CandiConfig,Config,Users,Rbac,Results,ResultsConfig" --appconfig=config/console-test.php
 */
class VotingFlowCest
{
    private string $voteID = 'AnonPartyTest';
    private string $passwordSelf = 'testN1';
    private string $passwordOther = 'testdef1';

    public function _before(\AcceptanceTester $I)
    {
        if (!\Yii::$app->anon->isGuest) {
            \Yii::$app->anon->logout(false);
        }
    }

    /**
     * @dataProvider votingDataProvider
     */
    public function votingFlowTest(\AcceptanceTester $I, Example $example)
    {
        $model = new FormVotes();
        $vote = $model->getVoteInfo($this->voteID);
        if (!$vote) {
            $I->comment("未找到 voteID：{$this->voteID} 的測試資料");
            return;
        }

        $active = trim($example['active']);
        $skipDetail = trim($example['skipDetail']);
        $authBeforeDetail = trim($example['authBeforeDetail']);
        $isShow = trim($example['isShow']);
        $isLogin = trim($example['isLogin']);
        $loginStatus = trim($example['loginStatus']);
        $entryType = trim($example['entryType']);
        $TC = trim($example['TC']);

        $vote->active = $active;
        $vote->skipDetail = $skipDetail;
        $vote->authBeforeDetail = $authBeforeDetail;

        $resultsConfig = $vote->resultsConfig;
        if ($resultsConfig === null) {
            $resultsConfig = new \app\models\FormResultsConfig();
            $resultsConfig->voteID = $this->voteID;
            $resultsConfig->isParty = '0';
            $resultsConfig->sort = '0';
            $resultsConfig->showFieldSort = \app\models\FormResultsConfig::$defFieldSort;
        }
        $resultsConfig->isShow = $isShow;
        $resultsConfig->isLogin = $isLogin;

        $now = new \DateTime();
        if ($active == '2') {
            $verifyEnd = (clone $now)->modify('-1 hour');
            $verifyStart = (clone $verifyEnd)->modify('-1 hour');
            $openEnd = (clone $verifyStart)->modify('-1 day');
            $openStart = (clone $openEnd)->modify('-1 day');
        } else {
            $openStart = (clone $now)->modify('-1 day');
            $openEnd = (clone $openStart)->modify('+2 day');
            $verifyStart = (clone $openEnd)->modify('+1 hour');
            $verifyEnd = (clone $verifyStart)->modify('+1 hour');
        }

        $dateFormat = 'Y-m-d H:i:s';
        $vote->openStart = $openStart->format($dateFormat);
        $vote->openEnd = $openEnd->format($dateFormat);
        $vote->verifyStart = $verifyStart->format($dateFormat);
        $vote->verifyEnd = $verifyEnd->format($dateFormat);

        $resultsConfig->save(false);
        $vote->save(false);

        if ($active == '2' && $isShow == '1') {
            $formResults = new FormResults();
            if (!$formResults->isResults($this->voteID, (int)$vote->round)) {
                $formResults->createResults($this->voteID);
            }
        }

        $shortUrl = Votes::findOne(['voteID' => $this->voteID])->shortUrl;

        if ($loginStatus === '已登入(其他)') {
            $I->amOnPage($this->page('site/password?voteID=AnonNoPartyTest'));
            $I->fillField('FormAnon[password]', $this->passwordOther);
            $I->click('登入');
            $I->comment($TC);
            if ($TC === 'link') {
                $I->amOnPage($this->page($active == '2' ? 'vote/result-list' : 'vote/index'));
            } else {
                $I->amOnPage($this->shortUrlPage($shortUrl));
            }
        } elseif ($loginStatus === '已登入(自己)') {
            $I->amOnPage($this->page('site/password?voteID=' . $this->voteID));
            $I->fillField('FormAnon[password]', $this->passwordSelf);
            $I->click('登入');
            if ($TC === 'link') {
                $I->amOnPage($this->page($active == '2' ? 'vote/result-list' : 'vote/index'));
            } else {
                $I->amOnPage($this->shortUrlPage($shortUrl));
            }
        } elseif ($loginStatus === '未登入') {
            if ($TC === 'link') {
                $I->amOnPage($this->page($active == '2' ? 'vote/result-list' : 'vote/index'));
            } else {
                $I->amOnPage($this->shortUrlPage($shortUrl));
            }
        }

        $steps = explode(';', $entryType);
        foreach ($steps as $step) {
            $step = trim($step);
            if ($step === '' || $step === '不顯示') {
                continue;
            }

            if (preg_match('/^([a-zA-Z]+):(.*)$/', $step, $matches)) {
                $action = $matches[1];
                $params = trim($matches[2]);

                switch ($action) {
                    case 'amOnPage':
                        if (strpos($params, 'voteID=') === false && strpos($params, '/') !== false) {
                            $I->amOnPage($this->page($params . '&voteID=' . $this->voteID));
                        } else {
                            $I->amOnPage($this->page($params));
                        }
                        break;
                    case 'see':
                        $I->see($params);
                        break;
                    case 'dontSee':
                        $I->dontSee($params);
                        break;
                    case 'click':
                        if (preg_match('/^\\[(css|xpath)=(.+)\\]$/', $params, $m)) {
                            $I->click([$m[1] => trim($m[2])]);
                        } else {
                            $I->click($params);
                        }
                        break;
                    case 'fill':
                        list($field, $value) = explode('=', $params, 2);
                        if (trim($field) === 'password') {
                            $I->fillField('FormAnon[password]', $this->passwordSelf);
                        } else {
                            $I->fillField(trim($field), trim($value));
                        }
                        break;
                    case 'seeInCurrentUrl':
                        $I->seeInCurrentUrl($params);
                        break;
                    default:
                        $I->comment("無法辨識的指令: {$action}");
                }
            } else {
                $I->comment("無法解析的步驟: {$step}");
            }
        }
    }

    protected function votingDataProvider()
    {
        $file = codecept_data_dir() . 'voting_cases.csv';
        $rows = array_map('str_getcsv', file($file));
        $header = array_shift($rows);
        $data = [];
        foreach ($rows as $row) {
            if (count($header) !== count($row)) {
                continue;
            }
            $case = array_combine($header, $row);

            if ($case['link'] != '不顯示') {
                $linkCase = $case;
                $linkCase['entryType'] = $case['link'];
                $linkCase['TC'] = 'link';
                $data[] = $linkCase;
            }

            if (!empty($case['url'])) {
                $urlCase = $case;
                $urlCase['entryType'] = str_replace('passwordl', 'password', $case['url']);
                $urlCase['TC'] = 'url';
                $data[] = $urlCase;
            }
        }
        return $data;
    }

    private function page(string $route): string
    {
        $route = ltrim($route, '/');
        if (str_starts_with($route, 'index-test.php/')) {
            return '/' . $route;
        }
        return '/index-test.php/' . $route;
    }

    private function shortUrlPage(string $shortUrl): string
    {
        return '/index-test.php/' . ltrim($shortUrl, '/');
    }
}
