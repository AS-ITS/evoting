<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$dateFormat = function ($d) {
    if(trim($d) == '')
        return '';
    return date('Y-m-d\TH:i',strtotime($d));
};

$title = '群組基本資料';
$this->title = $title;
$this->params['secNavType'] = 'group';

$model->scenario = 'update';
echo \app\widgets\Alert::widget();

echo Html::tag('div', Html::tag('h2', $title), ['class'=>'text-center']);
echo Html::tag('hr');
$form = ActiveForm::begin([
    'action' => \yii\helpers\Url::to(['edit-base', 'groupId' => $model->groupId]),
    'enableClientValidation' => true
]);
?>
<div class="row">
    <div class="col-lg-8">
        <?=$form->field($model, 'groupName')->textInput(['disabled' => !Yii::$app->user->can('groupEditBase', ['groupId' => $model->groupId])]) ?>
    </div>
    <div class="col-lg-4">
        <?=$form->field($model, 'isRoutine')->dropDownList(Yii::$app->params['ct.group.routineAry'], ['disabled' => !Yii::$app->user->can('groupEditBase', ['groupId' => $model->groupId])]) ?>
    </div>
</div>
<?php
if (Yii::$app->user->can('groupEditBase', ['groupId' => $model->groupId])) {
    echo Html::tag(
        'div',
        Html::submitButton('更新', 
        ['class' => 'btn btn-primary me-2', 'style' => 'width: auto']),
        ['class'=>'row justify-content-center m-0']
    );
}

ActiveForm::end();
