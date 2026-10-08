<?php

// rules/voteManag.php

namespace app\rules;

use Yii;
use yii\rbac\Rule;

use app\models\FormGroupMember;

/**
 * 是否可以對群組進行修改基本資料、成員新增刪除修改等動作的規則
 */
class groupManagByMemberRule extends Rule
{
    /**
     * @inheritdoc
     */
    public $name = 'manageByMember';

    /**
     * @inheritdoc
     */
    public function execute($user, $item, $params)
    {
        // 禁止編輯或刪除自己
        if (isset($params['cn']) && $params['cn'] === Yii::$app->user->id) {
            throw new \yii\web\HttpException(403, '禁止編輯或刪除自己');
        }

        $memberHasGroups = FormGroupMember::find()->where(['groupId' => $params['groupId'], 'cn' => Yii::$app->user->id])->asArray()->one();

        // ga(群組管理員)只能看到有設定群組的投票，gm(群組成員)只能看到所在群組的投票
        if (Yii::$app->user->can('ga')) {
            return true;
        }
        elseif(empty($memberHasGroups) || $memberHasGroups['isOwner'] == 'N') {
            return false;
        }
        else {
            return true;
        }
    }
}
