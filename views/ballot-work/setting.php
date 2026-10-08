<?php
use yii\helpers\Html;
use app\components\Model;
use yii\bootstrap5\ActiveForm;

$title = Yii::t('app', '開票');
$this->title = $title;
$this->params['secNavType'] = 'ballotWork'; // 啟用共用之 vote 二級導航

echo \app\widgets\Alert::widget();

echo Html::tag('div', Html::tag('h2', Yii::t('app', $title)), ['class'=>'text-center']);
echo Html::tag('hr');
echo Html::tag('h3', $voteInfo->voteName, ['class' => 'text-center my-2 fw-bold']);
$sortAry = (strtolower(Yii::$app->language) == 'en-us') ? Yii::$app->params['ct.result.sortAryE'] : Yii::$app->params['ct.result.sortAry'];
// 顯示結果投票狀態
$model->showElectedStatus = explode(',', $model->showElectedStatus);

if($isConfig)
{
    $form = ActiveForm::begin(['enableClientValidation' => false]);
    if($isResults)
    {
        // echo Html::tag( 'div',
        //     Html::button('已完成開票', [
        //         'class'=>'btn btn-primary mr-2', 'disabled' => true
        //     ])
        //     , ['class'=>'form-row justify-content-center m-0']
        // );
    }
    else
    {
        echo Html::tag( 'div',
            Html::submitButton(Yii::t('app', '開票'), [
                'class'=>'btn btn-primary', 'name'=>'action', 'value'=>'make', 'style' => 'width: auto'
            ])
            , ['class'=>'row justify-content-center m-0']
        );
    }
?>
<div class="form-row d-flex justify-content-center">
    <div class="col-md-3">
        <?= $form->field($model, 'sort')
            ->label(Yii::t('app', '開票結果排序'))
            ->dropDownList($sortAry) 
        ?>
    </div>
</div>
<?php
    ActiveForm::end();
}
else {
    echo Html::tag('div', '請先完成開票設定', ['class' => 'alert alert-warning text-center']);
}
?>
