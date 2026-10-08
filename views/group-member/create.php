<?php

use yii\widgets\Pjax;
use yii\grid\GridView;
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = '新增群組成員';

$model->scenario = 'create';

// 初始化可能未傳入的變數
$searchModel = $searchModel ?? ['cn' => '', 'name' => ''];
$dataProvider = $dataProvider ?? null;
$showTable = $showTable ?? false;

echo \app\widgets\Alert::widget();
Pjax::begin(['id' => 'personnel']);
$formSearch = ActiveForm::begin();
echo Html::tag(
    'div',
    Html::tag(
        'div',
        Html::tag('label', '帳號', ['for' => 'labelCn']) .
            Html::input('text', $filterItem['cn'], $searchModel['cn'], ['class' => 'form-control', 'id' => 'labelCn']),
        ['class' => 'col']
    ).
    Html::tag(
        'div',
        Html::tag('label', '名字', ['for' => 'labelName']) .
            Html::input('text', $filterItem['name'], $searchModel['name'], ['class' => 'form-control', 'id' => 'labelName']),
        ['class' => 'col']
    ),
    ['class' => 'row mb-4']
);
echo Html::tag(
    'div',
    Html::submitButton('查詢', ['class' => 'btn btn-primary me-2', 'style' => 'width: auto']) .
        (Yii::$app->session->has(\app\models\FormBallotsCreator::$sessionKey) ?
            Html::a('清除查詢', ['clear-search', 'groupId' => $groupId], ['class' => 'btn btn-info me-2', 'style' => 'width: auto']) :
            ''
        ) .
        Html::a('返回列表', ['index', 'groupId' => $groupId], ['class' => 'btn btn-secondary', 'style' => 'width: auto']),
    ['class' => 'row justify-content-center mb-4']
);
ActiveForm::end();
if ($showTable && $dataProvider !== null) {
    Yii::debug($dataProvider->allModels);
    echo GridView::widget([
        'id' => 'personnel',
        'dataProvider' => $dataProvider,
        'summary' => Yii::$app->tablePag->getSummaryText('personnel'),
        'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
        'columns' => [
            // ['class' => 'yii\grid\SerialColumn'],
            [
                'label' => '帳號',
                'attribute' => 'cn',
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center font-monospace'],
            ],
            [
                'format' => 'raw',
                'label' => '名字',
                'attribute' => 'name',
                'value' => function ($model, $key, $index, $column) use ($groupId, $instAry, $voteInfo, $parties) {
                    if (trim($model['name']) == '') {
                        return '(空)';
                    }
                    return Html::a(
                        Html::encode(trim($model['name'])),
                        '#creator',
                        [
                            'data-bs-toggle' => "modal", 
                            'data-bs-target' => "#createMember",
                            'data-groupid' => $groupId,
                            'data-cn' => $model['cn'],
                            'data-name' => $model['name'],
                        ]
                    );
                },
                'headerOptions' => ['class' => 'align-middle text-center'],
                'contentOptions' => ['class' => 'align-middle text-center font-monospace'],
            ],
        ],
    ]);
}
$this->registerJs(<<<EOT
    $('#createMember').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget)
        var groupId = button.data('groupid')
        var cn = button.data('cn')
        var name = button.data('name')
        var modal = $(this)
        modal.find('.modal-title').text('新增群組成員 - ' + name)
        modal.find('input[id=formgroupmember-groupid]').val(groupId)
        modal.find('input[id=formgroupmember-cn]').val(cn)
    })
EOT
);
$formCreate = ActiveForm::begin([
    'action' => 'store'
]);
?>

<div class="modal fade" id="createMember" data-bs-backdrop="false" tabindex="-1" aria-labelledby="createMemberLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createMemberLabel">新增群組成員</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <div class="row">
                <div class="col-lg">
                    <?=$formCreate->field($model, 'isWrite')->dropDownList(Yii::$app->params['ct.group.writeAry']) ?>
                </div>
                <div class="col-lg">
                    <?=$formCreate->field($model, 'isOwner')->dropDownList(Yii::$app->params['ct.group.ownerAry']) ?>
                </div>
                <?php
                    echo $formCreate->field($model, 'groupId')->hiddenInput()->label(false);
                    echo $formCreate->field($model, 'cn')->hiddenInput()->label(false);
                ?>
            </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">關閉</button>
                <?= Html::submitButton('新增', ['class' => 'btn btn-sm btn-primary']) ?>
            </div>
        </div>
    </div>
</div>

<?php
ActiveForm::end();
Pjax::end();