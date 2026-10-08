<?php

// rules/voteManag.php

namespace app\rules;

use Yii;
use yii\rbac\Rule;

use app\models\FormGroupMember;

/**
 * 只能對自己的群組做檢視動作的規則
 */
class groupViewOwnRule extends Rule
{
    /**
     * @inheritdoc
     */
    public $name = 'viewOwnGroup';

    /**
     * @inheritdoc
     */
    public function execute($user, $item, $params)
    {
    // 檢查參數是否存在且不為 null
    if (!isset($params['groupId']) || is_null($params['groupId'])) {
        return false; // 或 throw new \yii\web\BadRequestHttpException('參數不齊全：groupId');
    }

        $memberHasGroups = FormGroupMember::find()->where(['groupId' => $params['groupId'], 'cn' => Yii::$app->user->id])->asArray()->all();
        if(empty($memberHasGroups))
            return false;
        return true;
    }
}
