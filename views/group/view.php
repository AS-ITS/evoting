<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\DetailView;

$title = '群組資訊';
$this->title = $title;

echo Html::tag('div', Html::tag('h2', $title), ['class'=>'text-center']);
?>
<div class="row">
    <div class="col-md">
        <?php
        echo Html::tag('div', Html::tag('h4','群組基本資料'), ['class'=>'text-center']);
        echo DetailView::widget([
            'model' => $groupInfo->query->one(),
            'attributes' => [
                [
                    'attribute' => 'groupId',
                    'captionOptions' => ['class'=>'text-end'],
                ],
                [
                    'attribute' => 'groupName',
                    'captionOptions' => ['class'=>'text-end'],
                ],
                [
                    'attribute' => 'isRoutine',
                    'value' => function ($model, $widget) {
                        return Yii::$app->params['ct.group.routineAry'][$model->isRoutine];
                    },
                    'captionOptions' => ['class'=>'text-end'],
                ],
            ],
        ]);
        ?>
    </div>
    <div class="col-md">
        <?php
        echo Html::tag('div', Html::tag('h4','群組成員資料'), ['class'=>'text-center']);
        $groupMemberList->sort = false; // 關閉排序
        $groupMemberList->pagination = false; // 關閉排序
        echo GridView::widget([
            'dataProvider' => $groupMemberList,
            'summary' => '',
            'columns' => [
                [
                    'attribute' => 'sysId',
                    'format' => 'raw',
                    'value' => function ($model, $key, $index, $column)
                    {
                        return Html::a(Html::encode($model->sysId), ['group/view','groupId'=>$model->groupId]);
                    },
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => ['class' => 'align-middle text-center'],
                ],
                [
                    'attribute' => 'isWrite',
                    'value' => function ($model, $key, $index, $column)
                    {
                        return $model->isWrite;
                    },
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 6rem'],
                ],
                [
                    'attribute' => 'isOwner',
                    'value' => function ($model, $key, $index, $column)
                    {
                        return $model->isOwner;
                    },
                    'headerOptions'  => ['class' => 'align-middle text-center'],
                    'contentOptions' => ['class' => 'align-middle text-center', 'style' => 'width: 6rem'],
                ],
            ],
        ]);
        ?>
    </div>
</div>