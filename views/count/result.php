<?php
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$title = '開票設定';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

echo \app\widgets\Alert::widget();

// 顯示結果投票狀態
$model->showElectedStatus = explode(',', $model->showElectedStatus);

if($isConfig)
{
    $configForm = ActiveForm::begin(['enableClientValidation' => false]);
    if($isResults)
    {
        echo Html::tag( 'div',
            Html::submitButton('重新開票', [
                'class'=>'btn btn-warning me-2', 'name'=>'action', 'value'=>'remake', 'style' => 'width: auto'
            ]).
            Html::submitButton('刪除開票', [
                'class'=>'btn btn-danger', 'name'=>'action', 'value'=>'delete', 'style' => 'width: auto'
            ])
            , ['class'=>'row justify-content-center m-0']
        );
    }
    else
    {
        echo Html::tag( 'div',
            Html::submitButton('開票', [
                'class'=>'btn btn-primary', 'name'=>'action', 'value'=>'make', 'style' => 'width: auto'
            ])
            , ['class'=>'row justify-content-center m-0']
        );
    }
    ActiveForm::end();
    echo '<hr>';
}

$form = ActiveForm::begin(['enableClientValidation'=>false]);
?>
<div class="row">
    <div class="col-md">
        <?=$form
            ->field($model, 'isShow')
            ->dropDownList(Yii::$app->params['ct.yesOrNoAry'])
            ->hint('* 是否將開票結果顯示於投票結果列表') ?>
    </div>
    <div class="col-md">
        <?=$form
            ->field($model, 'isLogin')
            ->dropDownList(Yii::$app->params['ct.yesOrNoAry'])
            ->hint('* 若選"是"則需登入才可查看投票結果') ?>
    </div>
    <?php
    if($voteInfo->isByParty): // 當不分組時，變成一排，且不顯示 "是否分組查看結果"
    ?>
</div>
<div class="row">
    <div class="col-md">
        <?=$form
            ->field($model, 'isParty')
            ->dropDownList(Yii::$app->params['ct.yesOrNoAry'])
            ->hint('* 各組投票者僅能看到自己組內的投票結果，不影響結果的預覽') ?>
    </div>
    <?php
    else:
        $model->isParty = 0; // 關閉分組看選票
        echo $form->field($model, 'isParty')->hiddenInput()->label(false);
    endif;
    ?>
    <div class="col-md">
        <?=$form
            ->field($model, 'sort')
            ->dropDownList(Yii::$app->params['ct.result.sortAry'])
            ->hint('* 得票結果顯示方式') ?>
    </div>
</div>
<div class="row">
    <div class="col-md">
        <?=$form
            ->field($model, 'showElectedStatus')
            ->inline(true)
            ->checkboxList(Yii::$app->params['ct.result.electedAry'], ['unselect' => null])
            ->hint('* 投票結果僅會顯示勾選的項目，皆未勾選則全部顯示') ?>
    </div>
</div>

<hr class="mb-3">
<h4 id="bs">欄位顯示、排序</h4>
<div class="row mb-1">
    <div class="col-md">
        *拖曳排序，【名稱】不得隱藏
    </div>
</div>
<div class="row">
    <div class="col-md-6 text-center h4"><span class="badge badge-primary">顯示的欄位</span></div>
    <div class="col-md-6 text-center h4"><span class="badge badge-primary">隱藏的欄位</span></div>
</div>
<div class="row mb-2">
    <div class="col-md">
        <ul class="list-group" id="sortListLeft">
            <?php
            $showFieldSort = explode(',', $model->showFieldSort);
            foreach($showFieldSort as $showFS)
            {
                if(isset(Yii::$app->params['ct.candi.fieldName'][$showFS]))
                {
                    echo Html::tag('li',
                        ($showFS == 'Name')?
                        Yii::$app->params['ct.candi.fieldName'][$showFS]."({$candiConfig->Name})":
                        Yii::$app->params['ct.candi.fieldName'][$showFS],
                        ['class' => 'list-group-item', 'data-id' => $showFS]
                    );
                }
                else if(isset(Yii::$app->params['ct.result.fieldName'][$showFS]))
                {
                    echo Html::tag('li',
                        Yii::$app->params['ct.result.fieldName'][$showFS],
                        ['class' => 'list-group-item', 'data-id' => $showFS]
                    );
                }
            }
            ?>
        </ul>
    </div>
    <div class="col-md">
        <ul class="list-group" id="sortListRight">
            <?php
            $hideFieldSort = array_diff(
                array_merge(
                    array_keys(Yii::$app->params['ct.candi.fieldName']),
                    array_keys(Yii::$app->params['ct.result.fieldName'])
                ),
                $showFieldSort
            );
            foreach($hideFieldSort as $hideFS)
            {     
                if(isset(Yii::$app->params['ct.candi.fieldName'][$hideFS]))
                {
                    echo Html::tag('li',
                        Yii::$app->params['ct.candi.fieldName'][$hideFS],
                        ['class'=>'list-group-item','data-id' => $hideFS]
                    );
                }
                else if(isset(Yii::$app->params['ct.result.fieldName'][$hideFS]))
                {
                    echo Html::tag('li',
                        Yii::$app->params['ct.result.fieldName'][$hideFS],
                        ['class'=>'list-group-item','data-id' => $hideFS]
                    );
                }
            }
            ?>
        </ul>
    </div>
</div>
<?php
echo Html::tag( 'div',
    $form->field($model, 'voteID')->hiddenInput()->label(false).
    $form->field($model, 'showFieldSort')->hiddenInput()->label(false).
    Html::submitButton($action == 'update'?'修改':'建立', [
        'class'=>'btn btn-primary', 'name'=>'action', 'value'=>'save', 'style' => 'width: auto'
    ]),
    ['class'=>'row justify-content-center m-0']
);
ActiveForm::end();

// 排序的套件
\app\assets\SortableAsset::register($this);

$this->registerJs(<<<JS
    $('#sortListLeft').sortable({
        group: 'shared',
        animation: 150,
        store: {
            /**
             * Save the order of elements. Called onEnd (when the item is dropped).
             * @param {Sortable}  sortable
             */
            set: function (sortable) {
                var order = sortable.toArray();
                // console.log('store.set: '+sortable.options.group.name);
                // console.log(order.join(','));
                $("#formresultsconfig-showfieldsort").val(order.join(','));
            }
        },
        onMove: function (/**Event*/evt, /**Event*/originalEvent) {
            // console.log('===================================');
            // console.log('sortListRight.onChange');
            // console.log(evt);
            // console.log(originalEvent);
            if(evt.to.id == 'sortListRight' && evt.dragged.dataset.id == 'Name')
            {
                return false;
            }
        }
    });
    $('#sortListRight').sortable({
        group: 'shared',
        animation: 150,
    });

    // 改變游標樣式
    $(".list-group-item").css("cursor", "move");
    $(".list-group-item").addClass("py-2");
JS
);
