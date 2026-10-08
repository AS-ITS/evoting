<?php
// use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use yii\bootstrap5\Html;

use yii\bootstrap5\ActiveForm;

$title = '選票建立者';

$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

echo \app\widgets\Alert::widget();

Pjax::begin([
    'id' => 'myGrid'
]);
$form = ActiveForm::begin();
echo Html::tag('div',
    Html::tag(
        'div',
        Html::tag('label', '單位', ['for'=>'labelInstCode']).
        Html::dropDownList(
            $filterItem['instCode'], 
            $searchModel['instCode'] == ''? null:$searchModel['instCode'], 
            $instSelectAry,
            ['class'=>'form-select', 'id'=>'labelInstCode', 'prompt' => '']
        ),
        ['class'=>'col']
    ).Html::tag(
        'div',
        Html::tag('label', '職稱', ['for'=>'labeltCode']).
        Html::dropDownList(
            $filterItem['tCode'], 
            $searchModel['tCode'] == ''? null:$searchModel['tCode'], 
            $payTitleAry,
            ['class'=>'form-select', 'id'=>'labeltCode', 'prompt' => '']
        ),
        ['class'=>'col']
    ).Html::tag(
        'div',
        Html::tag('label', '在職狀態', ['for'=>'labelOnJob']).
        Html::dropDownList(
            $filterItem['onJob'],
            $searchModel['onJob'] == ''? null:$searchModel['onJob'], 
            $onJobAry, 
            ['class'=>'form-select', 'id'=>'labelOnJob', 'prompt' => '']
        ),
        ['class'=>'col']
    ).Html::tag(
        'div',
        Html::tag('label', '名字', ['for'=>'labelChName']).
        Html::input('text', $filterItem['chName'], $searchModel['chName'], ['class'=>'form-control', 'id'=>'labelChName']),
        ['class'=>'col']
    )
    , ['class'=>'row mb-4']
);
echo Html::tag('div',
    Html::submitButton( '查詢', ['class' => 'btn btn-primary me-2', 'style' => 'width: auto'] ).
    (
        Yii::$app->session->has(\app\models\FormBallotsCreator::$sessionKey)?
            Html::a('清除查詢', ['select-clear', 'voteID'=>$voteID], ['class' => 'btn btn-info me-2', 'style' => 'width: auto']):
            ''
    ).
    Html::a('返回列表', ['index', 'voteID'=>$voteID], ['class' => 'btn btn-secondary', 'style' => 'width: auto'])
    , ['class'=>'row justify-content-center mb-4']
);
ActiveForm::end();

if($showTable)
{
    $sexAry = ['M'=>'男','F'=>'女'];
    echo GridView::widget([
        'id' => 'myGrid',
        'dataProvider' => $dataProvider,
        'summary' => Yii::$app->tablePag->getSummaryText('myGrid'),
        'columns' => [
            // ['class' => 'yii\grid\SerialColumn'],
            [
                'label' => '單位',
                'attribute' => 'instCode',
                'value' => function ($model, $key, $index, $column) use ($instAry) {
                    if(!isset($instAry[$model['instCode']]))
                        return $model['instCode'];
                    return $instAry[$model['instCode']]['instName'];
                },
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-center font-monospace'],
            ],
            [
                'label' => '職稱',
                'attribute' => 'tCode',
                'value' => function ($model, $key, $index, $column) use ($payTitle) {
                    if(!isset($payTitle[$model['tCode']]))
                        return $model['tCode'];
                    return $payTitle[$model['tCode']]['title'];
                },
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-center font-monospace'],
            ],
            [
                'label' => '在職狀態',
                'attribute' => 'onJob',
                'value' => function ($model, $key, $index, $column) use ($onJobAry) {
                    if(!isset($onJobAry[$model['onJob']]))
                        return $model['onJob'];
                    return $onJobAry[$model['onJob']];
                },
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-center font-monospace'],
            ],
            [
                'format' => 'raw',
                'label' => '名字',
                'attribute' => 'chName',
                'value' => function ($model, $key, $index, $column) use ($voteID, $instAry, $voteInfo, $parties) {
                    if(trim($model['chName']) == '') {
                        return '(空)';
                    }
                    if($voteInfo->partyOrNot == '0') {
                        $party = \app\models\Parties::DEF_PARTY;
                    }
                    else {
                        $partyName = Yii::$app->params['ct.division1Ary'][$instAry[$model['instCode']]['division']];
                        $party = $parties[$partyName];
                    }
                    
                    return Html::a(
                        Html::encode(trim($model['chName'])),
                        ['creator', 'voteID' => $voteID, 'party' => $party, 'sysId' => $model['sysId']],
                        ['target'=>'_blank']
                    );
                },
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-center font-monospace'],
            ],
            [
                'label' => '性別',
                'attribute' => 'sex',
                'value' => function ($model, $key, $index, $column) use ($sexAry) {
                    return $sexAry[$model['sex']];
                },
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions'=> ['class' => 'align-middle text-center font-monospace'],
            ],
        ],
    ]);
}
Pjax::end();
