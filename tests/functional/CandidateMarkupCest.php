<?php

use app\models\CandiConfig;
use app\models\CandiData;
use app\models\Votes;
use PHPUnit\Framework\Assert;

class CandidateMarkupCest
{
    public function _fixtures(): array
    {
        $fixtures = [];
        foreach (['Votes', 'Parties', 'Round', 'Questions', 'CandiData', 'Passwords',
            'CandiConfig', 'Config', 'Users', 'Rbac', 'Group', 'GroupMember',
            'Ballots', 'BallotsSelected', 'ResultsConfig'] as $name) {
            $fixtures[lcfirst($name)] = 'app\\tests\\fixtures\\'.$name.'Fixture';
        }
        return $fixtures;
    }

    public function _before(FunctionalTester $I): void
    {
        $I->haveFixtures($this->_fixtures());
        Yii::$app->user->logout();
        Yii::$app->anon->logout();
    }

    public function testVotingPageChinese(FunctionalTester $I): void
    {
        $this->checkPage($I, 'start-vote', 'zh-TW');
    }

    public function testVotingPageEnglish(FunctionalTester $I): void
    {
        $this->checkPage($I, 'start-vote', 'en-US');
    }

    public function testCandidateListChinese(FunctionalTester $I): void
    {
        $this->checkPage($I, 'vote-detail', 'zh-TW');
    }

    public function testCandidateListEnglish(FunctionalTester $I): void
    {
        $this->checkPage($I, 'vote-detail', 'en-US');
    }

    public function testPrintedBallotChinese(FunctionalTester $I): void
    {
        $this->checkPage($I, 'print', 'zh-TW');
    }

    public function testPrintedBallotEnglish(FunctionalTester $I): void
    {
        $this->checkPage($I, 'print', 'en-US');
    }

    private function markup(string $marker): string
    {
        return $marker.'<br><b>R&D</b><script>p2attack()</script>';
    }

    private function checkPage(FunctionalTester $I, string $page, string $language): void
    {
        $vote = Votes::findOne(['voteID' => 'AnonNoPartyTest']);
        $config = CandiConfig::findOne(['voteID' => $vote->voteID]);
        $candidate = CandiData::find()->where(['voteID' => $vote->voteID])->orderBy('id')->one();
        Assert::assertNotNull($config);
        Assert::assertNotNull($candidate);
        $original = [$vote->getAttributes(), $config->getAttributes(), $candidate->getAttributes()];
        $originalLanguage = Yii::$app->language;
        $sessionLanguage = Yii::$app->session->get('language');
        try {
            // Exercise the voter routes without changing shared fixtures or requiring an anonymous login.
            $vote->updateAttributes(['type' => '0']);
            $fields = [['id' => 'Name', 'children' => []]];
            $labels = [];
            $values = [];
            foreach (array_merge(['Name'], array_map(fn ($c) => 'otherCol'.$c, range('A', 'F'))) as $field) {
                $label = $field === 'Name' ? 'Name' : str_replace('otherCol', 'otherColName', $field);
                $token = $field === 'Name' ? 'N' : substr($field, -1);
                foreach (['' => 'Zh', 'E' => 'En'] as $suffix => $marker) {
                    $labels[$label.$suffix] = $this->markup('L'.$token.$marker);
                    $values[$field.$suffix] = $this->markup('D'.$token.$marker);
                }
                if ($field !== 'Name') {
                    $fields[] = ['id' => $field, 'children' => []];
                }
            }
            $config->updateAttributes(array_merge($labels, [
                // The Chinese name-label column is only 20 characters wide.
                'Name' => 'NZ<br><b>R&D</b>',
                'showFieldSort' => json_encode($fields), 'useBeforeHeader' => 0, 'columnNum' => 1,
            ]));
            $candidate->updateAttributes($values);
            if ($page === 'print') {
                $I->amLoggedInAsAdmin('sa');
                $url = '/ballot/print?voteID='.$vote->voteID.'&questionID='.$candidate->questionID;
            } else {
                $url = '/vote/'.$page.'?voteID='.$vote->voteID;
            }
            Yii::$app->language = $language;
            Yii::$app->session->set('language', $language);
            $I->amOnPage($url.'&hl='.$language);
            $I->seeResponseCodeIs(200);
            $html = $I->grabPageSource();
            $dom = new DOMDocument();
            $previous = libxml_use_internal_errors(true);
            try {
                $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
            $xpath = new DOMXPath($dom);
            Assert::assertSame(0, $xpath->query('//svg[@onload] | //script[contains(., "p2attack")] | //*[@onclick[contains(., "p2attack")] or @onerror[contains(., "p2attack")]]')->length);
            $markers = ['DA'.($language === 'zh-TW' ? 'Zh' : 'En')];
            if ($page === 'start-vote') {
                foreach (range('A', 'F') as $field) {
                    $markers[] = 'L'.$field.($language === 'zh-TW' ? 'Zh' : 'En');
                }
            } elseif ($page === 'print') {
                $markers = [];
                foreach (array_merge(['Name'], array_map(fn ($c) => 'otherCol'.$c, range('A', 'F'))) as $field) {
                    $token = $field === 'Name' ? 'N' : substr($field, -1);
                    foreach (['Zh', 'En'] as $suffix) {
                        $markers[] = $field === 'Name' && $suffix === 'Zh' ? 'NZ' : 'L'.$token.$suffix;
                        $markers[] = 'D'.$token.$suffix;
                    }
                }
            }
            foreach ($markers as $marker) {
                Assert::assertGreaterThan(0, $xpath->query('//*[contains(text(), "'.$marker.'")]/br')->length, $marker.' keeps a line break');
                $bold = $xpath->query('//*[contains(text(), "'.$marker.'")]/b');
                Assert::assertGreaterThan(0, $bold->length, $marker.' keeps safe formatting');
                Assert::assertSame('R&D', $bold->item(0)->textContent, $marker.' is not double encoded');
            }
        } finally {
            Yii::$app->language = $originalLanguage;
            if ($sessionLanguage === null) {
                Yii::$app->session->remove('language');
            } else {
                Yii::$app->session->set('language', $sessionLanguage);
            }
            foreach ([$vote, $config, $candidate] as $index => $record) {
                $record->updateAttributes($original[$index]);
            }
        }
    }
}
