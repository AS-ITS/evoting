<?php

namespace app\tests\unit\models;

use app\models\Ballots;
use app\models\FormPasswords;
use app\models\Passwords;
use app\models\Votes;
use app\tests\fixtures\BallotsFixture;
use app\tests\fixtures\ConfigFixture;
use app\tests\fixtures\PartiesFixture;
use app\tests\fixtures\PasswordsFixture;
use app\tests\fixtures\QuestionsFixture;
use app\tests\fixtures\RbacFixture;
use app\tests\fixtures\RoundFixture;
use app\tests\fixtures\UsersFixture;
use app\tests\fixtures\VotesFixture;

class FormPasswordsVotedFilterTest extends \Codeception\Test\Unit
{
    protected $tester;

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
            'passwords' => PasswordsFixture::class,
            'ballots' => BallotsFixture::class,
        ]);
    }

    public function testResolvePasswordContextForNormalVote()
    {
        $voteInfo = Votes::findOne('AnonPartyTest');
        $ctx = FormPasswords::resolvePasswordContext($voteInfo);

        $this->assertSame('AnonPartyTest', $ctx['passwordVoteID']);
        $this->assertSame('AnonPartyTest', $ctx['ballotVoteID']);
        $this->assertSame(1, $ctx['round']);
    }

    public function testGetVotedPasswordIdsFromBallots()
    {
        $ids = FormPasswords::getVotedPasswordIds('AnonPartyTest', 1);
        $this->assertNotEmpty($ids);
        $this->assertContains('1', array_map('strval', $ids));
    }

    public function testSearchVotedYesFiltersByBallotsNotPasswordsColumn()
    {
        Passwords::updateAll(['voted' => '0'], ['voteID' => 'AnonPartyTest', 'id' => 1]);

        $model = new FormPasswords();
        $query = $model->search('AnonPartyTest', [
            'FormPasswords' => ['voted' => '1'],
        ], 'AnonPartyTest', 1);
        $ids = array_map(static fn($row) => (string) $row->id, $query->all());

        $this->assertContains('1', $ids);
        $this->assertNotContains('50', $ids);
    }

    public function testSearchVotedNoExcludesBallotCreators()
    {
        $model = new FormPasswords();
        $query = $model->search('AnonPartyTest', [
            'FormPasswords' => ['voted' => '0'],
        ], 'AnonPartyTest', 1);
        $ids = array_map(static fn($row) => (string) $row->id, $query->all());

        $this->assertNotContains('1', $ids);
        $this->assertContains('50', $ids);
    }

    public function testBuildListQueryVotedFilter()
    {
        $model = new FormPasswords();
        $query = $model->buildListQuery('AnonPartyTest', ['voted' => '1'], 'AnonPartyTest', 1);
        $count = (int) $query->count();

        $this->assertSame(40, $count);
    }

    public function testSetPasswdStatusWithVotedConditionOnlyChangesStatus()
    {
        Passwords::updateAll(['status' => '1'], ['voteID' => 'AnonPartyTest', 'id' => 1]);
        Passwords::updateAll(['voted' => '0'], ['voteID' => 'AnonPartyTest', 'id' => 1]);

        $model = new Passwords();
        $count = $model->setPasswdStatus('AnonPartyTest', [
            'voted' => '1',
            'status' => '0',
        ], 'AnonPartyTest', 1);

        $this->assertGreaterThan(0, $count);
        $updated = Passwords::findOne(1);
        $this->assertSame('0', $updated->status);
        $this->assertSame('0', $updated->voted, 'setPasswdStatus must not touch passwords.voted');

        Passwords::updateAll(['status' => '1'], ['id' => 1]);
    }
}
