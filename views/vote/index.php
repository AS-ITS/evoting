<?php
use app\models\Votes;
use yii\helpers\Html;

$this->title = '首頁';

echo \app\widgets\Alert::widget();
echo Html::tag('h3', Yii::t('app', '開放中之投票項目'), ['class' => 'text-center']);
echo Html::tag('hr');
echo \yii\widgets\ListView::widget([
    'dataProvider' => $dataProvider,
    'layout' => '{items}',//{pager}
    'itemView' => function ($model, $key, $index, $widget)
    {
        $voteName = $model->voteName;
        $html = Html::tag('strong', Yii::t('app', '投票名稱').'：');
        if (!$model->isVoteOpen($model->voteID) && $model->skipDetail) {
            $html .= Html::tag('span', $voteName);
        }
        else {
            $html .= Html::a($voteName, ['vote/vote-detail', 'voteID' => $model->voteID]);
        }
        $footer = '';
        if($model->type == Votes::TYPE_ANON) {
            $footer .= Yii::t('app', '匿名投票').'，';
        }
        $timeFormat = function($time){
            $srA = [date('Y-'), '-'];
            $srB = ['', '/'];
            return str_replace($srA, $srB, mb_substr($time, 0, -3, "utf-8"));
        };
        $toDate = strtotime(date('Y-m-d H:i:s')); // strtotime(date('Y-m-d H:i:s'))
        switch(true)
        {
            case ($toDate >= strtotime($model->openStart) && $toDate < strtotime($model->openEnd) || $toDate <= strtotime($model->openStart)):
                $footer .= sprintf(Yii::t('app', '投票時間').'：%s ~ %s', $timeFormat($model->openStart), $timeFormat($model->openEnd));
                break;

            case ($toDate >= strtotime($model->openEnd) && $toDate < strtotime($model->verifyStart)):
                $footer .= sprintf(Yii::t('app', '等待驗證').'：%s ~ %s', $timeFormat($model->openEnd), $timeFormat($model->verifyStart));
                break;

            case ($toDate >= strtotime($model->verifyStart) && $toDate < strtotime($model->verifyEnd)):
                $footer .= sprintf(Yii::t('app', '驗證時間').'：%s ~ %s', $timeFormat($model->verifyStart), $timeFormat($model->verifyEnd));
                break;
        }
        $html .= Html::tag('footer', Html::tag('small', $footer), ['class' => 'blockquote-footer mb-0']);
        return Html::tag('blockquote', $html, ['class' => 'blockquote mb-0']);
    },
    'itemOptions' => [
        'tag'   => 'li',
        'class' => 'list-group-item',
    ],
    'options' => [
        'tag' => 'ul',
        'class' => 'list-group list-group-flush mb-4',
        // 'style' => 'padding-inline-start:0',
    ],
]);
