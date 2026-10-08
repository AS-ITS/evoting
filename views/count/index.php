<?php

use app\models\FormManageCount;
use yii\helpers\Url;

$title = Yii::t('app', '計票單');
$sort = is_null(Yii::$app->request->get('sort')) ? FormManageCount::COUNT_BY_BALLOTS : Yii::$app->request->get('sort');
$this->title = $title.'_'.Yii::t('vote', Yii::$app->params['ct.result.sortPrintText'][$sort]);
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $model->voteInfo;

$sortUrl = Url::to(['count/index', 'voteID' => Yii::$app->request->get('voteID')]);
echo $this->render('_count', compact(
    'title', 'model', 'ballotList', 'ballotCountSort', 'ballotCountAry', 
    'passwordList', 'showExportModal', 'sortUrl'
));
