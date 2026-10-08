<?php

namespace app\tests\unit;

use app\models\Ballots;
use app\models\FormAnon;
use app\models\FormBallots;
use app\models\Logins;
use app\models\Logs;
use app\models\Passwords;
use app\models\PasswordDisplay;
use app\models\Results;
use app\models\Votes;
use app\tests\fixtures\BallotsFixture;
use app\tests\fixtures\BallotsSelectedFixture;
use app\tests\fixtures\CandiConfigFixture;
use app\tests\fixtures\CandiDataFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\VotesFixture;
use Yii;

/**
 * C6 匿名負向測試：系統內不得出現人↔票／人↔密碼。
 */
class AnonymityNegativeTest extends \Codeception\Test\Unit
{
    /** @var \UnitTester */
    protected $tester;

    /** @var string[] */
    private const FORBIDDEN_COLUMNS = [
        'name', 'email', '姓名', 'nationalid', 'idno', 'id_no',
        'voter', 'votername', 'realname', 'phone', 'mobile',
        'userid', 'user_id', 'employee', 'empno',
    ];

    protected function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->tester->haveFixtures([
            'users' => UsersFixture::class,
            'config' => ConfigFixture::class,
            'rbac' => RbacFixture::class,
            'votes' => VotesFixture::class,
            'parties' => PartiesFixture::class,
            'round' => RoundFixture::class,
            'questions' => QuestionsFixture::class,
            'candiData' => CandiDataFixture::class,
            'candiConfig' => CandiConfigFixture::class,
            'passwords' => PasswordsFixture::class,
            'ballots' => BallotsFixture::class,
            'ballotsSelected' => BallotsSelectedFixture::class,
        ]);
    }

    public function testSchemaHasNoVoterIdentityColumns()
    {
        foreach ([Passwords::class, Ballots::class, Logins::class, Results::class] as $modelClass) {
            $columns = array_map('strtolower', $modelClass::getTableSchema()->getColumnNames());
            foreach (self::FORBIDDEN_COLUMNS as $forbidden) {
                $this->assertNotContains(
                    $forbidden,
                    $columns,
                    $modelClass::tableName() . " must not have identity column {$forbidden}"
                );
            }
        }
        $this->assertContains('sn', array_map('strtolower', Passwords::getTableSchema()->getColumnNames()));
        $this->assertContains('creator', array_map('strtolower', Ballots::getTableSchema()->getColumnNames()));
        $this->assertContains('isadminadd', array_map('strtolower', Ballots::getTableSchema()->getColumnNames()));
        $resultColumns = array_map('strtolower', Results::getTableSchema()->getColumnNames());
        $this->assertNotContains('creator', $resultColumns);
        $this->assertNotContains('passwd', $resultColumns);
    }

    public function testVoteTypeHasNoNamedVoting()
    {
        $this->assertArrayNotHasKey('2', Yii::$app->params['ct.voteType']);
        $this->assertFalse(defined(Votes::class . '::TYPE_VOTER'));
    }

    public function testLoginLogContextOmitsPlaintextPassword()
    {
        $model = new FormAnon();
        $model->voteID = 'AnonPartyTest';
        $model->password = 'testN1-should-not-log';
        $model->addError('password', '您輸入的投票密碼不正確，請再試一次！');

        $fail = $model->buildLoginLogContext();
        $ok = $model->buildLoginLogContext(['id' => 9]);

        $this->assertArrayNotHasKey('password', $fail);
        $this->assertArrayNotHasKey('password', $ok);
        $this->assertSame(9, $ok['passwordId']);
        $this->assertStringNotContainsString('testN1-should-not-log', json_encode($fail, JSON_UNESCAPED_UNICODE));
        $this->assertStringNotContainsString('testN1-should-not-log', json_encode($ok, JSON_UNESCAPED_UNICODE));
    }

    public function testLogsAddRedactsPasswordKeys()
    {
        Logs::add(Logs::LOGIN_PASSWORD_FAIL, [
            'voteID' => 'AnonPartyTest',
            'password' => 'leaked-secret',
            'passwd' => 'also-secret',
        ], ['voteID' => 'AnonPartyTest']);

        $log = Logs::find()->where(['voteID' => 'AnonPartyTest', 'type' => Logs::LOGIN_PASSWORD_FAIL])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        $this->assertNotNull($log);
        $this->assertStringNotContainsString('leaked-secret', (string) $log->context);
        $this->assertStringNotContainsString('also-secret', (string) $log->context);
        $this->assertStringContainsString('[REDACTED]', (string) $log->context);
    }

    public function testAnonBallotVoterLabelIsPasswordMaskNotUserName()
    {
        $form = new FormBallots();
        $labels = $form->getSysidByBallots(
            $form->getBallotList('AnonPartyTest', 1),
            Votes::TYPE_ANON,
            false
        );
        $this->assertIsArray($labels);
        $joined = implode(' ', $labels);
        $this->assertStringNotContainsString('testN1', $joined);
        $this->assertDoesNotMatchRegularExpression('/VA Test|系統管理員|sa_test/u', $joined);
        $hasMask = false;
        foreach ($labels as $label) {
            if (is_string($label) && (str_starts_with($label, '密碼#') || str_starts_with($label, '密碼ID'))) {
                $hasMask = true;
                break;
            }
        }
        $this->assertTrue($hasMask, 'Anonymous voter labels should use 密碼#sn mask');
    }

    public function testPasswordGridMasksWhenLocked()
    {
        $this->assertSame(
            '密碼#3',
            PasswordDisplay::formatGridValue(['id' => 1, 'sn' => 3, 'passwd' => 'testN1'], false)
        );
        $this->assertStringNotContainsString(
            'testN1',
            PasswordDisplay::formatGridValue(['id' => 1, 'sn' => 3, 'passwd' => 'testN1'], false)
        );
    }

    public function testPublicResultViewOmitsCredentials()
    {
        $src = file_get_contents(Yii::getAlias('@app/views/vote/result.php'));
        $this->assertNotFalse($src);
        $this->assertDoesNotMatchRegularExpression('/\$model->creator|ballots\.creator|row\[.creator.\]/', $src);
        $this->assertStringNotContainsString('passwd', strtolower($src));
        $this->assertStringNotContainsString('password', strtolower($src));
    }
}
