<?php

use yii\bootstrap5\Html;
use yii\helpers\Json;
use yii\widgets\DetailView;

echo DetailView::widget([
    'model' => $model,
    'attributes' => [
        [
            'label' => '類型',
            'value' => Yii::$app->params['log.type'][$model->type] ?? $model->type,
        ],
        'ip',
        'browser',
        [
            'label' => '內文',
            'value' => is_string($model->context) && is_array(json_decode($model->context, true)) ? 
                Html::tag('pre', Json::encode(Json::decode($model->context), 464)) : 
                $model->context, 
            'format' => 'html'
        ],
        'created_at',
    ],
]);