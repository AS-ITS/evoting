<?php
use yii\helpers\Html;
use app\components\Model;
use app\models\Votes;
use yii\widgets\DetailView;

// 統一時間格式
$timeFormat = function($time)
{
    $srA = [date('Y-'), '-'];
    $srB = ['', '/'];
    return str_replace($srA,$srB,mb_substr( $time,0,-3,"utf-8"));
};

echo DetailView::widget([
    'model'      => $voteInfo,
    'attributes' => [
        [
            'label'  => Yii::t('app', '投票時間'),
            'value'  => function($model) use ($timeFormat) {
                return sprintf('%s ~ %s', $timeFormat($model->openStart), $timeFormat($model->openEnd));
            },
            'captionOptions' => ['class' => 'align-middle text-end'],
            'contentOptions' => ['class' => 'align-middle'],
        ],
        [
            'label'  => Yii::t('app','驗證時間'),
            'value'  => function($model) use ($timeFormat) {
                return sprintf('%s ~ %s', $timeFormat($model->verifyStart), $timeFormat($model->verifyEnd));
            },
            'captionOptions' => ['class' => 'align-middle text-end'],
            'contentOptions' => ['class' => 'align-middle'],
        ],
        [
            'label'  => Yii::t('app', '主辦單位'),
            'value'  => function($model) {
                return Model::i18n($model->hostedE,$model->hosted);
            },
            'captionOptions' => ['class' => 'align-middle text-end'],
            'contentOptions' => ['class' => 'align-middle'],
        ],
        [
            'label'  => Yii::t('app', '聯絡人'),
            'attribute' => 'contact',
            'value'  => function($model) {
                if(empty($model->contact) && empty($model->contact))
                    return '-';
                return Model::i18n($model->contactE,$model->contact);
            },
            'captionOptions' => ['class' => 'align-middle text-end'],
            'contentOptions' => ['class' => 'align-middle'],
        ],
        [
            'label'  => Yii::t('app', '聯絡電話'),
            'value'  => function($model) {
                if(empty($model->tel))
                    return '-';
                return $model->tel;
            },
            'captionOptions' => ['class' => 'align-middle text-end'],
            'contentOptions' => ['class' => 'align-middle'],
        ],
        [
            'label'  => Yii::t('app', '聯絡信箱'),
            'value'  => function($model) {
                if(empty($model->email))
                    return '-';
                return $model->email;
            },
            'captionOptions' => ['class' => 'align-middle text-end'],
            'contentOptions' => ['class' => 'align-middle'],
        ],
    ]
]);
