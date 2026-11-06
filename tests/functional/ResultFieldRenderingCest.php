<?php

use app\models\CandiConfig;
use app\models\CandiData;
use app\models\FormManageVote;
use app\models\FormResults;
use app\models\ResultsConfig;
use Codeception\Example;
use PHPUnit\Framework\Assert;
use yii\data\ActiveDataProvider;

class ResultFieldRenderingCest
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
        $I->amLoggedInAsAdmin('sa');
    }

    protected function pages(): array
    {
        $cases = [];
        foreach (['zh-TW', 'en-US'] as $language) {
            foreach (['ballot-work/result', 'result/index', 'vote/result',
                'vote/vote-detail', 'ballot/edit', 'selected'] as $page) {
                $cases[] = ['page' => $page, 'language' => $language, 'encoded' => false];
            }
            // Existing encoded data must stay literal, rather than being decoded into markup.
            $cases[] = ['page' => 'vote/result', 'language' => $language, 'encoded' => true];
        }
        return $cases;
    }

    /** @dataProvider pages */
    public function testRendering(FunctionalTester $I, Example $case): void
    {
        $voteID = 'AnonPartyTest';
        $transaction = Yii::$app->db->beginTransaction();
        $originalLanguage = Yii::$app->language;
        $sessionLanguage = Yii::$app->session->get('language');
        try {
            $config = CandiConfig::findOne(['voteID' => $voteID]);
            $candidate = CandiData::find()->where(['voteID' => $voteID])->orderBy('id')->one();
            $resultConfig = ResultsConfig::findOne(['voteID' => $voteID]);
            Assert::assertNotNull($config);
            Assert::assertNotNull($candidate);
            Assert::assertNotNull($resultConfig);
            $fields = array_merge(['Name', 'instName', 'title'], array_map(fn ($c) => 'otherCol'.$c, range('A', 'F')));
            $labels = ['Name' => 'R&D', 'NameE' => 'R&D'];
            $values = [];
            foreach (range('A', 'F') as $letter) {
                foreach (['' => 'Zh', 'E' => 'En'] as $suffix => $locale) {
                    $labels['otherColName'.$letter.$suffix] = 'L'.$letter.$locale.'<br><b>R&D</b><script>p16attack()</script>';
                    $values['otherCol'.$letter.$suffix] = 'D'.$letter.$locale.'<br><b onclick="p16attack()">R&D</b>';
                }
            }
            foreach (['instName', 'title'] as $field) {
                foreach (['', 'E'] as $suffix) {
                    $values[$field.$suffix] = 'R&D<br>';
                }
            }
            $values['Name'] = 'NZ<br><b onclick="p16attack()">R&D</b>';
            $values['NameE'] = 'NE<br><b onclick="p16attack()">R&D</b>';
            if ($case['encoded']) {
                $values['otherColA'] = $values['otherColAE'] = 'Literal&lt;br&gt;R&amp;D';
            }
            $config->updateAttributes(array_merge($labels, [
                'showFieldSort' => json_encode(array_map(fn ($id) => ['id' => $id, 'children' => []], $fields)),
                'useBeforeHeader' => 0,
            ]));
            $candidate->updateAttributes($values);
            $resultConfig->updateAttributes(['showFieldSort' => implode(',', $fields).',ballotCounts,elected']);
            $results = new FormResults();
            $results->deleteResults($voteID, 1);
            Assert::assertTrue($results->createResults($voteID));
            $before = FormResults::find()->where(['voteID' => $voteID])->orderBy('candID')->asArray()->all();
            Yii::$app->language = $case['language'];
            Yii::$app->session->set('language', $case['language']);
            if ($case['page'] === 'selected') {
                $I->amOnPage('/vote/result?voteID='.$voteID.'&hl='.$case['language']);
                $html = Yii::$app->view->renderFile(Yii::getAlias('@app/views/vote/_ballot-selected.php'), [
                    'model' => new FormManageVote($voteID, '0'),
                    'gridID' => 'selected-rendering',
                    'ballotCandi' => new ActiveDataProvider(['query' => FormResults::find()->where(['voteID' => $voteID])]),
                    'candiConfig' => (new \app\models\FormCandiConfig())->getConfigWithVoteID($voteID),
                    'questions' => [],
                ], Yii::$app->controller);
            } else {
                $extra = $case['page'] === 'ballot/edit' ? '&party=0&ballotID=1' : '';
                $I->amOnPage('/'.$case['page'].'?voteID='.$voteID.$extra.'&hl='.$case['language']);
                $I->seeResponseCodeIs(200);
                $html = $I->grabPageSource();
            }
            $dom = new DOMDocument();
            $previous = libxml_use_internal_errors(true);
            try {
                $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
            $xpath = new DOMXPath($dom);
            Assert::assertSame(0, $xpath->query('//script[contains(., "p16attack()")] | //*[@onclick="p16attack()" or @onerror="p16attack()"]')->length);
            $locale = $case['language'] === 'zh-TW' ? 'Zh' : 'En';
            foreach (range('A', 'F') as $letter) {
                $headers = $xpath->query('//th[contains(., "L'.$letter.$locale.'")]');
                Assert::assertGreaterThan(0, $headers->length, 'Custom header is present');
                Assert::assertGreaterThan(0, $xpath->query('.//br', $headers->item(0))->length, 'Header has a real break');
                Assert::assertSame('R&D', $xpath->query('.//b', $headers->item(0))->item(0)->textContent);
                if ($letter === 'A' && $case['encoded']) {
                    $cells = $xpath->query('//td[contains(., "Literal")]');
                    Assert::assertGreaterThan(0, $cells->length);
                    Assert::assertSame('Literal<br>R&D', trim($cells->item(0)->textContent));
                    Assert::assertSame(0, $xpath->query('.//br', $cells->item(0))->length, 'Stored entities remain literal');
                    continue;
                }
                $cells = $xpath->query('//td[contains(., "D'.$letter.$locale.'")]');
                Assert::assertGreaterThan(0, $cells->length, 'Custom value is present');
                $cell = $cells->item(0);
                if ($letter === 'A') {
                    Assert::assertGreaterThan(0, $xpath->query('.//br', $cell)->length, 'Field A has a real break');
                    Assert::assertSame('R&D', $xpath->query('.//b', $cell)->item(0)->textContent);
                } else {
                    Assert::assertSame(0, $xpath->query('.//br | .//b', $cell)->length, 'B-F stay plain text');
                    Assert::assertSame($values['otherCol'.$letter.($locale === 'Zh' ? '' : 'E')], trim($cell->textContent));
                }
            }
            Assert::assertGreaterThan(0, $xpath->query('//td[normalize-space(.)="R&D<br>"]')->length, 'Plain text is encoded once');
            Assert::assertGreaterThan(0, $xpath->query('//th[normalize-space(.)="R&D"]')->length, 'Name label is encoded once');
            $names = $xpath->query('//td[contains(., "'.($locale === 'Zh' ? 'NZ' : 'NE').'")]');
            Assert::assertGreaterThan(0, $names->length);
            $nameCell = $names->item(0);
            if (in_array($case['page'], ['ballot-work/result', 'result/index', 'vote/result', 'selected'])) {
                Assert::assertGreaterThan(0, $xpath->query('.//br', $nameCell)->length, 'Already purified names render once');
                Assert::assertSame('R&D', $xpath->query('.//b', $nameCell)->item(0)->textContent);
            } else {
                Assert::assertSame(0, $xpath->query('.//br | .//b', $nameCell)->length);
                Assert::assertSame($values['Name'.($locale === 'Zh' ? '' : 'E')], trim($nameCell->textContent));
            }
            Assert::assertSame($before, FormResults::find()->where(['voteID' => $voteID])->orderBy('candID')->asArray()->all(), 'Rendering leaves counts, ranks and elected status unchanged');
        } finally {
            Yii::$app->language = $originalLanguage;
            if ($sessionLanguage === null) {
                Yii::$app->session->remove('language');
            } else {
                Yii::$app->session->set('language', $sessionLanguage);
            }
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
        }
    }
}
