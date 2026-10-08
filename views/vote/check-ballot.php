<?php
use yii\helpers\Url;
use yii\helpers\Html;
use app\models\Votes;

$this->title = \app\components\Branding::siteTitle();

echo Html::tag('h2', $model->votesInfo->voteName, ['class'=>'fw-bold text-center']);
echo Html::tag('h3', Yii::t('app', '您已完成此次投票作業'), ['class'=>'fw-bold text-danger text-center']);
echo Html::tag('h3', Yii::t('app', '您的投票結果如下').':', ['class'=>'fw-bold text-primary text-center']);

$DataProvider = new \app\models\DataProvider;
// 根據問題顯示圈選結果
if ($model->voteInfo->candiConfig == Votes::CANDI_CONFIG_BY_Q) {
    foreach ($questions as $questionID => $Title) {
        $ballotCandi = $DataProvider->getBasicDataProvider(
            $model->getBallotCandi()->andWhere(['ballotsSelected.questionID' => $questionID])
        );
        if ($ballotCandi->totalCount != 0) {
            $candiConfig = $model->getCandidateConfig($model->voteID, $questionID);
            $gridID = 'myGrid'.$questionID;
            echo Html::tag('h5', $Title.':', ['class'=>'fw-bold text-dark text-start']);
            echo $this->render('_ballot-selected', compact('model', 'gridID', 'ballotCandi', 'candiConfig', 'questions'));
        }
    }
}
else {
    $ballotCandi = $DataProvider->getBasicDataProvider($model->getBallotCandi());
    $candiConfig = $model->getCandidateConfig($model->voteID);
    $gridID = 'myGrid';
    echo $this->render('_ballot-selected', compact('model', 'gridID', 'ballotCandi', 'candiConfig', 'questions'));
}

echo Html::tag(
    'div',
    Html::a(Yii::t('app', '登出'), 
        Url::to(['site/logout', 'type' => $model->votesInfo->type == Votes::TYPE_ANON ? 'anon' : 'voter']), 
        ['class' => 'btn btn-primary me-2 js-csrf-sync-logout', 'data-method' => 'post']
    ),
    ['class'=>'text-primary mt-2 text-center']
);
