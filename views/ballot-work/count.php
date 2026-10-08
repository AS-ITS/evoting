<?php

use yii\helpers\Url;
use yii\helpers\Html;

$title = Yii::t('app', '計票');
$this->title = $title;
$this->params['secNavType'] = 'ballotWork'; // 啟用共用之 vote 二級導航

$sortUrl = Url::to(['ballot-work/count', 'voteID' => Yii::$app->request->get('voteID')]);
echo $this->render('../count/_count', compact(
    'title', 'model', 'ballotList', 'ballotCountSort', 'ballotCountAry',
    'passwordList', 'showExportModal', 'sortUrl'
));

echo Html::tag('div', 
    Html::a(
        '→ '.Yii::t('app', '開票結果'), 
        ['ballot-work/result', 'voteID' => $model->voteInfo->voteID], 
        ['class' => 'btn btn-primary hide-print']
    )
, ['class' => 'text-end']);
