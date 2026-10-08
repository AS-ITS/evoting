<?php

// rules/voteManag.php

namespace app\rules;

use Yii;
use yii\rbac\Rule;

use app\models\FormVotes;
use app\models\FormGroupMember;

/**
 * 開票作業的規則
 */
class ballotWorkRule extends Rule
{
    /**
     * @inheritdoc
     */
    public $name = 'ballotWorkManage';

    /**
     * @inheritdoc
     */
    public function execute($user, $item, $params)
    {
        // 系統管理員pass
        if(Yii::$app->user->can('sa'))
            return true;
        // 基本資料
        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($params['voteID']);
        // 投票建立者pass
        if (Yii::$app->user->can('va') && $voteInfo->creator == Yii::$app->user->id) {
            return true;
        }
         $memberHasGroups = FormGroupMember::find()->where(['groupId' => $voteInfo->groupId, 'cn' => Yii::$app->user->id])->asArray()->one();
        // 不是群組管理員也不是群組成員，不pass
        if((empty($memberHasGroups) || $memberHasGroups['isWrite'] == 'N')) {
            return false;
        }
        else {
            return true;
        }
    }
}
