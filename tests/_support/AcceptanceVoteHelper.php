<?php

use app\models\CandiData;
use app\models\Questions;
use app\models\Votes;

/**
 * Acceptance 投票圈選輔助（PhpBrowser 不執行 JS，改以 form POST 送出圈選）
 */
class AcceptanceVoteHelper
{
    public static function submitBallotFromStartVotePage(AcceptanceTester $I, string $voteID): void
    {
        $selection = self::buildSelectionForVote($voteID);

        $I->submitForm('form', [
            'selection' => $selection,
        ]);
    }

    /**
     * @return string[]
     */
    public static function buildSelectionForVote(string $voteID): array
    {
        $vote = Votes::findOne(['voteID' => $voteID]);
        $round = (int)($vote->round ?? 1);
        $questions = Questions::find()
            ->where(['voteID' => $voteID, 'round' => $round])
            ->orderBy(['questionID' => SORT_ASC])
            ->all();
        $total = count($questions);
        $selection = [];

        foreach ($questions as $index => $question) {
            $isLast = ($index === $total - 1);
            $need = self::requiredSelections($question, $isLast);
            if ($need <= 0) {
                continue;
            }
            $ids = CandiData::find()
                ->select('id')
                ->where(['voteID' => $voteID, 'questionID' => $question->questionID])
                ->orderBy(['id' => SORT_ASC])
                ->limit($need)
                ->column();
            foreach ($ids as $id) {
                $selection[] = (string) $id;
            }
        }

        return $selection;
    }

    private static function requiredSelections(Questions $question, bool $isLast): int
    {
        $least = (int)($question->leastNumBallots ?? 0);
        if ($least > 0) {
            return $least;
        }
        if (!$isLast) {
            return 0;
        }
        $numBallots = (int)($question->numBallots ?? 0);

        return $numBallots > 0 ? $numBallots : 1;
    }
}
