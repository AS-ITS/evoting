<?php

// rules/voteManag.php

namespace app\rules;

use Yii;
use yii\rbac\Rule;

use app\models\FormVotes;
use app\models\FormGroupMember;

class voteManagRule extends Rule
{
    /**
     * @inheritdoc
     */
    public $name = 'voteManag';

    /**
     * @inheritdoc
     */
    public function execute($user, $item, $params)
    {
        if(Yii::$app->user->can('sa'))
            return true;
        if (empty($params['voteID'])) {
            return false;
        }
        // 基本資料
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($params['voteID']);
        if ($voteInfo === null) {
            return false;
        }
        $memberHasGroups = FormGroupMember::find()->where(['groupId' => $voteInfo->groupId, 'cn' => Yii::$app->user->id])->asArray()->one();
        if(($voteInfo->creator != Yii::$app->user->id) && (empty($memberHasGroups) || $memberHasGroups['isWrite'] == 'N')) {
            return false;
        }
        else {
            return true;
        }
    }
}
