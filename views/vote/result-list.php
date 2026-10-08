<?php
use yii\helpers\Html;
use app\models\Votes;

$this->title = '首頁';
if($dataCount == 0)
{
    echo Html::tag('h3', Yii::t('app', '公開的投票結果之項目'), ['class' => 'text-center']);
    echo Html::tag('hr');
    echo Html::tag('div', '目前沒有公開的投票結果',['class'=>'alert alert-warning text-center','role'=>'alert']);
}
else
{
    echo Html::tag('h3', Yii::t('app', '公開的投票結果之項目'), ['class' => 'text-center']);
    echo Html::tag('hr');
    echo \yii\widgets\ListView::widget([
        'dataProvider' => $dataProvider,
        'layout' => '{items}',//{pager}
        'itemView' => function ($model, $key, $index, $widget)
        {
            $voteName = $model->voteName;
            $html = Html::tag('strong', Yii::t('app', '投票名稱').'：');
            $html .= Html::a($voteName, ['vote/result', 'voteID' => $model->voteID]);
            $footer = '';
            if($model->type == Votes::TYPE_ANON) {
                $footer .= Yii::t('app', '匿名投票').'，';
            }
            $timeFormat = function($time) {
                return str_replace([date('Y-'), '-'], ['', '/'], mb_substr($time, 0, -3, "utf-8"));
            };
            $footer .= sprintf(Yii::t('app', '投票時間').'：%s ~ %s', $timeFormat($model->openStart), $timeFormat($model->openEnd));
            $html .= Html::tag('footer', Html::tag('small',$footer), ['class' => 'blockquote-footer mb-0']);
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
}

