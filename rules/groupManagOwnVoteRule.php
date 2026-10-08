<?php

// rules/voteManag.php

namespace app\rules;

use Yii;
use yii\rbac\Rule;

use app\models\FormVotes;
use app\models\FormGroupMember;

/**
 * 只能對自己的群組做投票管理動作的規則
 */
class groupManagOwnVoteRule extends Rule
{
    /**
     * @inheritdoc
     */
    public $name = 'manageOwnVote';

    /**
     * @inheritdoc
     */
    public function execute($user, $item, $params)
    {
    // 先檢查參數是否存在
    if (!isset($params['voteID']) || empty($params['voteID'])) {
        return false; // 參數不齊全時直接返回 false
    }

        $FormVotes = new FormVotes;
        $voteInfo = $FormVotes->getVoteInfo($params['voteID']);
        
    // 加入 null 檢查
    if (is_null($voteInfo)) {
        return false; // 或 throw new \Exception('投票不存在');
    }

        $memberHasGroups = FormGroupMember::find()->where(['groupId' => $voteInfo->groupId, 'cn' => Yii::$app->user->id])->asArray()->one();
        // ga(群組管理員)只能看到有設定群組的投票，gm(群組成員)只能看到所在群組的投票
        if (Yii::$app->user->can('ga') && !empty($voteInfo->groupId)) {
            return true;
        }
        elseif(empty($memberHasGroups) || $memberHasGroups['isWrite'] == 'N') {
            return false;
        }
        else {
            return true;
        }
    }
}
