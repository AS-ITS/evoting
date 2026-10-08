<?php
use yii\helpers\Html;
use yii\helpers\VarDumper;
use yii\bootstrap5\ActiveForm;
use app\actions\DataTableModifierAction;

/** @var yii\web\View $this */
/** @var yii\db\ActiveRecord $model */
/** @var yii\bootstrap5\ActiveForm $form */

$tableSchema = $model->getTableSchema();
switch($action)
{
    case DataTableModifierAction::ACTION_SEARCH:
        $submitName = '查詢';
        $this->params['navbar_left_items'] = [
            [
                'label' => '返回資料表條列',
                'url' => $linkIndex,
            ],
            [
                'label' => '資料建立',
                'url' => $linkCreate,
                'linkOptions' => [
                    'class' => 'text-success',
                ],
            ],
        ];
        break;
    case DataTableModifierAction::ACTION_CREATE:
        $submitName = '建立';
        $this->params['navbar_left_items'] = [
            [
                'label' => '返回查詢結果',
                'url' => $linkResult,
                'visible' => !($linkResult === false),
            ],
            [
                'label' => '資料查詢',
                'url' => $linkSearch,
                'visible' => $linkResult === false,
            ],
        ];
        break;
    case DataTableModifierAction::ACTION_UPDATE:
        $submitName = '修改';
        $this->params['navbar_left_items'] = [
            [
                'label' => '返回查詢結果',
                'url' => $linkResult,
                'visible' => !($linkResult === false),
            ],
            [
                'label' => '資料查詢',
                'url' => $linkSearch,
                'visible' => $linkResult === false,
            ],
            [
                'label' => '資料刪除',
                'url' => $linkDelete,
                'linkOptions' => [
                    'class' => 'text-danger',
                    'data' => [
                        'confirm' => '請再次確認要刪除該筆資料？',
                        'method' => 'post',
                    ],
                ],
            ],
            [
                'label' => '資料建立',
                'url' => $linkCreate,
                'visible' => !($linkCreate === false),
                'linkOptions' => [
                    'class' => 'text-success',
                ],
            ],
        ];
        break;
}
$this->title = "{$tableSchema->name} 資料{$submitName}";

echo \app\widgets\Alert::widget();
?>
<div class="data-table-modifier-form mb-4">
    <?php $form = ActiveForm::begin([
        'id' => 'data-table-modifier-form',
        'enableClientValidation' => false,
        'enableAjaxValidation' => false,
        'fieldConfig' => [
            'options' => ['class' => 'mb-0'],
            'labelOptions' => ['class' => 'mb-0 mt-2'],
        ],
    ]); ?>
    <?= Html::hiddenInput('action', $action) ?>
    <div class="row mt-2">
        <div class="col-md">
            <?= Html::submitButton($submitName, ['class' => 'btn btn-primary']) ?>
        </div>
    </div>
    <?php foreach($tableSchema->columns as $columnName => $tableSchemaColumn) { ?>
        <div class="row">
            <div class="col-md">
                <?php
                    // hint 組合
                    $hint = '* ';
                    $hint .= '<code>'.$columnName.'</code>';
                    $hint .= ' (';
                    $hint .= 'type: <code>'.VarDumper::dumpAsString($tableSchemaColumn->type).'</code>';
                    $hint .= ', ';
                    $hint .= 'isPrimaryKey: <code>'.VarDumper::dumpAsString($tableSchemaColumn->isPrimaryKey).'</code>';
                    $hint .= ', ';
                    $hint .= 'autoIncrement: <code>'.VarDumper::dumpAsString($tableSchemaColumn->autoIncrement).'</code>';
                    $hint .= ', ';
                    $hint .= 'allowNull: <code>'.VarDumper::dumpAsString($tableSchemaColumn->allowNull).'</code>';
                    $hint .= ')';
                    if(trim($tableSchemaColumn->comment) != '')
                    {
                        $hint .= ': '.$tableSchemaColumn->comment;
                    }
                    if($action != DataTableModifierAction::ACTION_SEARCH && !is_null($tableSchemaColumn->enumValues))
                    {
                        // $select2Options = [
                        //     'data' => array_combine($tableSchemaColumn->enumValues, $tableSchemaColumn->enumValues),
                        // ];
                        // if($tableSchemaColumn->allowNull)
                        // {
                        //     $select2Options['options'] = ['placeholder' => ''];
                        //     $select2Options['pluginOptions'] = ['allowClear' => true];
                        // }
                        $data = array_combine($tableSchemaColumn->enumValues, $tableSchemaColumn->enumValues);
                        if($tableSchemaColumn->allowNull)
                        {
                            $data = array_merge([''=>''], $data);
                        }
                        // 未使用 SELECT 是因為該元件會有整個畫面縮小的問題
                        echo $form
                            ->field($model, $columnName)
                            ->dropdownList(array_combine($tableSchemaColumn->enumValues, $tableSchemaColumn->enumValues))
                            ->hint($hint);
                    }
                    else
                    {
                        $inputOptions = ['style'=>'font-family:monospace;'];
                        if($action != DataTableModifierAction::ACTION_SEARCH)
                        {
                            $inputOptions['maxlength'] = true;
                        }
                        echo $form
                            ->field($model, $columnName)
                            ->textInput($inputOptions)
                            ->hint($hint);
                    }
                ?>
            </div>
        </div>
    <?php } ?>
    <?php ActiveForm::end(); ?>
</div>
