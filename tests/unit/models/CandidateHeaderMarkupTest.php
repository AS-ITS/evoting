<?php

namespace app\tests\unit\models;

use app\models\FormCandiConfig;
use Codeception\Test\Unit;

class CandidateHeaderMarkupTest extends Unit
{
    public function testFirstPrintedHeaderSegmentIsSanitized()
    {
        $this->assertSafeSegment(0, 'First');
    }

    public function testSecondPrintedHeaderSegmentIsSanitized()
    {
        $this->assertSafeSegment(1, 'Second');
    }

    private function assertSafeSegment(int $key, string $marker): void
    {
        $config = new FormCandiConfig();
        $config->columnNum = 2;
        $config->showFieldSort = json_encode([
            ['id' => 'headerA', 'children' => [['id' => 'Name']]],
        ]);
        foreach (['beforeHeaderA', 'beforeHeaderAE'] as $field) {
            $config->$field = 'First<br><b onclick="p2attack()">R&amp;D</b><script>p2attack()</script>,'
                .'Second<br><b onclick="p2attack()">R&amp;D</b><script>p2attack()</script>';
        }

        $header = $config->getBeforeHeader(false, [], [], false, true, $key);
        $html = $header[0]['columns'][0]['content'];
        $this->assertStringContainsString($marker.'<br', $html);
        $this->assertStringContainsString('<b>R&amp;D</b>', $html);
        $this->assertStringNotContainsString('p2attack', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }
}
