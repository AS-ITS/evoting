<?php

use yii\helpers\Html;
use yii\helpers\Json;
use app\models\Questions;
use kartik\select2\Select2;
use yii\bootstrap5\ActiveForm;
use yii\helpers\ArrayHelper;

$title = '候選名單配置';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

if ($action == 'update') {
    echo $this->render('_nav', [
        'voteID' => $voteID,
        'voteInfo' => $voteInfo,
        'questionID' => Yii::$app->request->get('questionID')
    ]);
}

echo \app\widgets\Alert::widget();

$form = ActiveForm::begin([
    'enableClientValidation' => false,
    'fieldConfig' => [
        'options' => ['class' => 'mb-0'],
        'labelOptions' => ['class' => 'mb-0 mt-2'],
    ],
]);
if ($voteInfo->candiConfig == \app\models\Votes::CANDI_CONFIG_BY_Q) {
    // 先檢查變數是否有效
    if ($voteInfo && $voteID) {
        try {
            $questions = \app\models\Questions::find()->where(['voteID' => $voteID, 'round' => $voteInfo->round])->asArray()->all();
        } catch (Exception $e) {
            $questions = [];
        }
    } else {
        $questions = [];
    }
    $questionID = Yii::$app->request->get('questionID');
    echo $form->field($model, 'questionID')
        ->dropDownList(
            ArrayHelper::map($questions ?: [], 'questionID', function($element) {
                return strip_tags($element['title']);
            }),
            ['prompt' => '請選擇問題', 'onchange' => 'getCandiConfig(this.value)', 'value' => $questionID]
        )
        ->hint('* 因為此投票候選名單配置是依照問題來建立，所以請選擇問題來建立候選名單配置。');
    echo Html::tag('hr');
}

// 預設排序的欄位清單
$sortDefaults = [];
foreach (Yii::$app->params['ct.candi.fieldSortName'] as $col => $name) {
    $sortDefaults['['.Json::encode([$col => SORT_ASC]).']'] = "$name - 升冪";
    $sortDefaults['['.Json::encode([$col => SORT_DESC]).']'] = "$name - 降冪";
}
?>
<?php if ($voteInfo->candiConfig != \app\models\Votes::CANDI_CONFIG_BY_Q || !empty($questionID)): ?>
<h4 id="bs">基本設定</h4>
<div class="row">
    <div class="col-md-3">
        <?=$form
            ->field($model, 'num')
            ->dropDownList(Yii::$app->params['ct.showNumAry'])
            ->hint('* 自動編號由系統生成流水號') ?>
    </div>
    <div class="col-md-3">
        <?= $form
            ->field($model, 'width')
            ->dropDownList(Yii::$app->params['ct.candi.width'])
            ->hint('* 投票頁面候選名單寬度') ?>
    </div>
    <div class="col-md-3">
        <?= $form
            ->field($model, 'columnNum')
            ->dropDownList(array_combine(range(1, 5), range(1, 5))) ?>
    </div>
    <div class="col-md-3">
        <?= $form
            ->field($model, 'useBeforeHeader')
            ->dropDownList(Yii::$app->params['ct.yesOrNoAry'])
            ->hint('* 欄位上方再加上表頭') ?>
    </div>
</div>
<div class="row">
    <div class="col-md-3">
        <?=$form
            ->field($model, 'Name')
            ->textInput() ?>
    </div>
    <div class="col-md-3">
        <?=$form
            ->field($model, 'NameE')
            ->textInput() ?>
    </div>
    <div class="col-md-3">
        <?=$form
            ->field($model, 'NameUnit')
            ->textInput() ?>
    </div>
    <div class="col-md-3">
        <?=$form
            ->field($model, 'NameUnitE')
            ->textInput() ?>
    </div>
</div>
<div class="row">
    <div class="col-md-3">
        <?= $form
            ->field($model, 'fontSize')
            ->textInput()
            ->hint('* 投票頁面候選名單文字大小') ?>
    </div>
    <div class="col-md-3">
        <?= $form
            ->field($model, 'fontSizeE')
            ->textInput()
            ->hint('* 投票頁面候選名單文字大小') ?>
    </div>
    <div class="col-md-3">
        <?= $form
            ->field($model, 'cellHeight')
            ->textInput()
            ->hint('* 投票頁面候選名單文字大小') ?>
    </div>
    <div class="col-md-3">
        <?= $form
            ->field($model, 'cellHeightE')
            ->textInput()
            ->hint('* 投票頁面候選名單文字大小') ?>
    </div>
</div>
<div class="row">
    <div class="col-md-3">
        <?= $form
            ->field($model, 'headerColor')
            ->textInput()
            ->hint('* 投票候選名單表格標頭顏色') ?>
    </div>
    <div class="col-md-3">
        <?= $form
            ->field($model, 'sort')
            ->dropDownList(Yii::$app->params['ct.yesOrNoAry'])
            ->hint('* 【投票時候選名單顯示列數】設定1有效，設定在最下面') ?>
    </div>
    <div class="col-md-3">
        <?= $form
            ->field($model, 'sortDefault')
            ->dropDownList($sortDefaults, ['prompt' => ''])
            ->hint('* 不選擇則按候選名單排序，只能選要排序的欄位') ?>
    </div>
</div>
<div class="row">
    <div class="col-md">
        <?= $form
            ->field($model, 'alignLeft')
            ->widget(Select2::classname(), [
                'data' => array_filter(Yii::$app->params['ct.candi.fieldName'], function($value) {
                    return strpos($value, '欄位表頭名稱') !== 0;
                }), 
                'options' => [
                    'placeholder' => '請選擇欄位',
                    'multiple' => true,
                ],
                'showToggleAll' => false,
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ])->hint('* 沒選擇的欄位文字置中') ?>
    </div>
</div>

<hr class="mb-3">
<h4 id="bs">自定義欄位</h4>
<div class="row">
    <div class="col-md-6 text-center h4"><span class="badge badge-primary">中文介面顯示</span></div>
    <div class="col-md-6 text-center h4"><span class="badge badge-primary">英文介面顯示</span></div>
</div>
<?php foreach (range('A', 'F') as $letter): ?>
    <div class="row">
        <div class="col-md">
            <?= $form->field($model, "otherColName{$letter}")->textInput() ?>
        </div>
        <div class="col-md">
            <?= $form->field($model, "otherColName{$letter}E")->textInput() ?>
        </div>
    </div>
<?php endforeach; ?>
<?php if ($model->useBeforeHeader): ?>
<hr class="mb-3">
<h4 id="bs">欄位表頭</h4>
<div class="row mb-1">
    <div id="ex-columnnums" class="col-md">
        * <a href="#formcandiconfig-columnnum" class="scroll-link" onclick="scrollUp(event)">候選名單顯示列數</a>
        大於1時，單一欄位表頭名稱以半形逗號分隔，表示不同表格的顯示文字。ex: 候選名單顯示列數等於3，欄位表頭名稱為「表頭一, 表頭二, 表頭三」，投票頁面呈現如下:
        <div class="row row-cols-3 text-center">
            <?php
                $exColumnNums = [
                    1 => ['th' => '表頭一', 'td' => 'AAA', 'color' => 'success'], 
                    2 => ['th' => '表頭二', 'td' => 'BBB', 'color' => 'danger'], 
                    3 => ['th' => '表頭三', 'td' => 'CCC', 'color' => 'warning']
                ];
                foreach ($exColumnNums as $num => $ColumnNum) {
                    echo Html::tag('table', 
                        Html::tag('thead', 
                            Html::tag('tr', Html::tag('th', $ColumnNum['th'], ['colspan' => 2]))
                        , ['class' => 'text-'.$ColumnNum['color']]).
                        Html::tag('tbody', 
                            Html::tag('tr', Html::tag('td', '排序').Html::tag('td', '姓名')).
                            Html::tag('tr', Html::tag('td', $num).Html::tag('td', $ColumnNum['td']))
                        ),
                        ['class' => 'table table-sm table-bordered']
                    );
                }
            ?>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6 text-center h4"><span class="badge badge-primary">中文介面顯示</span></div>
    <div class="col-md-6 text-center h4"><span class="badge badge-primary">英文介面顯示</span></div>
</div>
<?php foreach (range('A', 'C') as $letter): ?>
    <div class="row">
        <div class="col-md">
            <?= $form->field($model, "beforeHeader{$letter}")->textInput() ?>
        </div>
        <div class="col-md">
            <?= $form->field($model, "beforeHeader{$letter}E")->textInput() ?>
        </div>
    </div>
<?php endforeach; ?>
<?php endif; ?>

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
<div class="row">
    <div class="col-md list-group col" id="sortListLeft">
        <?php
        $showFieldSort = json_decode($model->showFieldSort, true);
        // 舊資料校正
        if ($showFieldSort == null) {
            $showFieldSort = $model->fixOldData($showFieldSort);
        }
        foreach ($showFieldSort as $showFS) {
            // 如果 $showFS['id'] 符合正規表達式 /^header[A-Z]/，則顯示一個可排序的 div 元素
            if (preg_match('/^header[A-Z]/', $showFS['id'])) {
                echo Html::beginTag('div', ['class' => 'list-group-item', 'data-id' => $showFS['id']]);
                echo Yii::$app->params['ct.candi.fieldName'][$showFS['id']];
                echo Html::beginTag('div', ['class' => 'list-group nested-sortable', 'data-group' => 'yes']);
                // 如果 $showFS['children'] 不為空且為陣列，則遍歷 $showFS['children'] 並顯示每個子元素
                if (!empty($showFS['children']) && is_array($showFS['children'])) {
                    foreach ($showFS['children'] as $children) {
                        // 顯示一個帶有 data-id 屬性和可排序類的 div 元素，並顯示子元素的名稱
                        echo Html::tag(
                            'div',
                            ($children['id'] == 'Name') ?
                                Yii::$app->params['ct.candi.fieldName'][$children['id']]."({$model->Name})" :
                                Yii::$app->params['ct.candi.fieldName'][$children['id']],
                            ['class' => 'list-group-item', 'data-id' => $children['id']]
                        );
                    }
                }
                echo Html::endTag('div');
                echo Html::endTag('div');
            }
            // 如果 $showFS['id'] 不符合正規表達式 /^header[A-Z]/，則顯示一個帶有 data-id 屬性的 div 元素
            else {
                echo Html::tag(
                    'div',
                    ($showFS['id'] == 'Name') ?
                        Yii::$app->params['ct.candi.fieldName'][$showFS['id']]."({$model->Name})" :
                        Yii::$app->params['ct.candi.fieldName'][$showFS['id']],
                    ['class' => 'list-group-item', 'data-id' => $showFS['id']]
                );
            }
        }
        ?>
    </div>
    <div class="col-md">
        <div class="list-group" id="sortListRight">
            <?php
            // 已經在左側顯示欄位的
            $showField = $model->getShowFiled($showFieldSort);
            $hideFieldSort = array_diff(array_keys(Yii::$app->params['ct.candi.fieldName']), $showField);
            // 刪除永不排序的欄位
            $hideFieldSort = array_filter($hideFieldSort, function ($value) {
                return !in_array($value, ['backgroundColor']);
            });
            foreach ($hideFieldSort as $hideFS) {
                if (preg_match('/^header[A-Z]/', $hideFS) && $model->useBeforeHeader) {
                    echo Html::tag(
                        'div',
                        Yii::$app->params['ct.candi.fieldName'][$hideFS].
                        Html::tag('div', '', ['class' => 'list-group nested-sortable', 'data-group' => 'yes']),
                        ['class' => 'list-group-item', 'data-id' => $hideFS]
                    );
                }
                elseif (!preg_match('/^header[A-Z]/', $hideFS)) {
                    echo Html::tag(
                        'div',
                        Yii::$app->params['ct.candi.fieldName'][$hideFS],
                        ['class' => 'list-group-item', 'data-id' => $hideFS]
                    );
                }
            }
            ?>
        </div>
    </div>
</div>
<div class="sort-area">
    <hr class="mb-3">
    <h4 id="bs">排序的欄位</h4>
    <div class="row mb-1">
        <div class="col-md">
            *僅排序<b>數字</b>的資料，預設排序自定義編號和候選名單順序
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 text-center h4"><span class="badge badge-primary">要排序的欄位</span></div>
        <div class="col-md-6 text-center h4"><span class="badge badge-primary">不排序的欄位</span></div>
    </div>
    <div class="row">
        <div class="col-md list-group col" id="sortColumnLeft">
            <?php
            $sortColumns = json_decode($model->sortColumns, true);
            foreach ($sortColumns ?? [] as $showFS) {
                // 如果 $showFS['id'] 不符合正規表達式 /^header[A-Z]/，則顯示一個帶有 data-id 屬性的 div 元素
                echo Html::tag(
                    'div',
                    Html::tag('span',
                        (($showFS['id'] == 'Name') ?
                            Yii::$app->params['ct.candi.fieldSortName'][$showFS['id']]."({$model->Name})" :
                            Yii::$app->params['ct.candi.fieldSortName'][$showFS['id']])
                        , ['class' => 'col-8 align-self-center']),
                    ['class' => 'list-group-item d-flex', 'data-id' => $showFS['id']]
                );
            }
            ?>
        </div>
        <div class="col-md">
            <div class="list-group" id="sortColumnRight">
                <?php
                // 已經在左側顯示欄位的
                $showField = $model->getShowFiled($sortColumns);
                $hideFieldSort = array_diff(array_keys(Yii::$app->params['ct.candi.fieldSortName']), $showField);
                // 刪除永不排序的欄位
                $hideFieldSort = array_filter($hideFieldSort, function ($value) {
                    return !preg_match('/^header[A-Z]/', $value) && !in_array($value, ['num', 'party', 'Name', 'backgroundColor', 'photo']);
                });
                foreach ($hideFieldSort as $hideFS) {
                    echo Html::tag(
                        'div',
                        Html::tag('span', Yii::$app->params['ct.candi.fieldSortName'][$hideFS], ['class' => 'col-8 align-self-center']),
                        ['class' => 'list-group-item d-flex', 'data-id' => $hideFS]
                    );
                }
                ?>
            </div>
        </div>
    </div>
</div>
<hr>
<?php
echo Html::tag(
    'div',
        $form->field($model, 'voteID')->hiddenInput()->label(false).
        $form->field($model, 'showFieldSort')->hiddenInput()->label(false).
        $form->field($model, 'sortColumns')->hiddenInput()->label(false).
        Html::submitButton($action == 'update' ? '修改' : '建立', ['class' => 'btn btn-primary', 'style' => 'width: auto']),
    ['class' => 'row justify-content-center m-0']
);
endif;
ActiveForm::end();

// 排序的套件
\app\assets\SortableAsset::register($this);

/**
 * @link https://github.com/SortableJS/Sortable
 */
$this->registerJs(<<<JS
    initPage();
    $('#formcandiconfig-columnnum').change(function() {
        checkSort($(this).val());
    });
JS
);
$this->registerJs(<<<JS
    // 直接跳轉到鏈接的目標
    function scrollUp(event) {
        event.preventDefault();

        // 直接跳轉到鏈接的目標
        window.location.hash = event.target.getAttribute('href');

        // 向上滾動
        OverlayScrollbars(document.body).scroll({top : 200}, 400);
    }
    // 取得候選名單配置
    function getCandiConfig(questionID) {
        window.location.href = updateURLParameter(window.location.href, "questionID", questionID);
    }
    /** 檢查是否可以選擇排序 */
    function checkSort(val) {
        if (val > 1) {
            $('#formcandiconfig-sort, #formcandiconfig-sortdefault').parent().addClass('d-none');
            $('.sort-area').addClass('d-none');
        }
        else {
            $('#formcandiconfig-sort, #formcandiconfig-sortdefault').parent().removeClass('d-none');
            $('.sort-area').removeClass('d-none');
        }
    }

    // 初始頁面
    function initPage() {
        // 檢查必要的元素是否存在（當選擇問題後才會顯示）
        var sortColumnLeft = document.getElementById("sortColumnLeft");
        var sortColumnRight = document.getElementById("sortColumnRight");
        var sortListLeft = document.getElementById("sortListLeft");
        var sortListRight = document.getElementById("sortListRight");

        if (!sortColumnLeft || !sortListLeft) {
            // 元素不存在，跳過初始化
            return;
        }

        // 要排序的欄位
        new Sortable(sortColumnLeft, {
            group: 'shared',
            animation: 150,
            onMove: function (/**Event*/evt, /**Event*/originalEvent) {
                if(evt.to.dataset.group === 'yes' && /^header[A-Z]/.test(evt.dragged.dataset.id)) {
                    return false;
                }
                if(evt.to.id == 'sortColumnsRight' && evt.dragged.dataset.id == 'Name') {
                    return false;
                }
            },
            store: {
                set: function (sortable) {
                    var root = document.getElementById('sortColumnLeft');
                    var order = JSON.stringify(serializeSort(root));
                    $("#formcandiconfig-sortcolumns").val(order);
                }
            },
        });
        // 不排序的欄位
        if (sortColumnRight) {
            new Sortable(sortColumnRight, {
                group: 'shared',
                animation: 150,
                onMove: function (/**Event*/evt, /**Event*/originalEvent) {
                    if(evt.to.dataset.group === 'yes' && /^header[A-Z]/.test(evt.dragged.dataset.id)) {
                        return false;
                    }
                },
            });
        }
        // 顯示的欄位
        new Sortable(sortListLeft, {
            group: 'shared',
            animation: 150,
            onMove: function (/**Event*/evt, /**Event*/originalEvent) {
                if(evt.to.dataset.group === 'yes' && /^header[A-Z]/.test(evt.dragged.dataset.id)) {
                    return false;
                }
                if(evt.to.id == 'sortListRight' && evt.dragged.dataset.id == 'Name') {
                    return false;
                }
            },
            store: {
                set: function (sortable) {
                    var root = document.getElementById('sortListLeft');
                    var order = JSON.stringify(serialize(root));
                    $("#formcandiconfig-showfieldsort").val(order);
                }
            },
        });
        // 巢狀排序
        var nestedSortables = [].slice.call(document.getElementsByClassName('nested-sortable'));
        nestedSortables.forEach(function (list) {
            new Sortable(list, {
                group: 'shared',
                pull: true,
                put: true,
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                onMove: function (/**Event*/evt, /**Event*/originalEvent) {
                    if(evt.to.id == 'sortListRight' && evt.dragged.dataset.id == 'Name') {
                        return false;
                    }
                },
                store: {
                    set: function (sortable) {
                        var root = document.getElementById('sortListLeft');
                        var order = serialize(root);
                        $("#formcandiconfig-showfieldsort").val(JSON.stringify(order));
                    }
                },
            });
        });
        // 隱藏的欄位
        if (sortListRight) {
            new Sortable(sortListRight, {
                group: 'shared',
                animation: 150,
                onMove: function (/**Event*/evt, /**Event*/originalEvent) {
                    if(evt.to.dataset.group === 'yes' && /^header[A-Z]/.test(evt.dragged.dataset.id)) {
                        return false;
                    }
                },
            });
        }
        document.getElementById('formcandiconfig-name').addEventListener('change', (event) => {
            switch ($(event.target).val() ) {
                case '0':
                    $('li[data-id="Name"]').text('名稱');
                    break;
                case '1':
                    $('li[data-id="Name"]').text('名字');
                    break;
            }
        });

        // 改變游標樣式
        $(".list-group-item").css("cursor", "move");
        $(".list-group-item").addClass("py-2");
        // 檢查是否可以排序
        checkSort($('#formcandiconfig-columnnum').val());
    }
JS
, $this::POS_END);
