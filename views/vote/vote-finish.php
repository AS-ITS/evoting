<?php

use yii\helpers\Url;
use app\models\Votes;

$this->title = app\components\Model::i18n($voteInfo->NameE, $voteInfo->Name);

// 如果略過圈選檢查，登出
if ($voteInfo->skipCheck == Votes::TYPE_ANON) {
    $type = $voteInfo->type == Votes::TYPE_ANON ? 'anon' : 'voter';
    if (!Yii::$app->$type->isGuest) {
        Yii::$app->$type->identity->logout(false);
    }
    $redirect = Url::to($model->finishPage, 'https');
}
else {
    $redirect = Url::to(['vote/check-ballot', 'voteID' => $voteInfo->voteID], 'https');
}
// 導向頁面
$this->registerJs("
    setTimeout(function() {
        window.location.href = '$redirect';
    }, 60000); // 60秒後跳轉
", $this::POS_READY); 

// 背景色
$bgColor = !empty($voteInfo->themeColor) ? $voteInfo->themeColor : '#ffffff';
$this->registerCss(<<<CSS
    #finish {
        background-color: $bgColor;
    }
CSS
);

?>

<div id="<?=Yii::$app->language == 'en-US' ? 'enTitle' : 'voteTitle' ?>" class="text-center mb-4">
    <?=$voteInfo->voteName?>
</div>
<div id="finish" style="height: 70vh;" class="d-flex align-items-center justify-content-center rounded border-md border-dark">
    <h1 class="display-2 fw-bolder"><?=Yii::t('app', $text); ?></h1>
</div>
