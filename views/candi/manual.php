<?php

use yii\helpers\Url;
use yii\helpers\Html;
use app\models\FormVotes;
use app\components\helper\ArrayHelper;
use yii\bootstrap5\ActiveForm;
use rmrevin\yii\fontawesome\FAS;
use app\components\helper\FileLoader;

if($action == 'create') {
    $title = Yii::$app->params['ct.candi.genModeAry']['manual'] . '候選人';
}
elseif($action == 'update') {
    $title = '編輯候選人';
}
    
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
$this->params['showVoteInfo'] = true;
$this->params['title'] = $title;
$this->params['voteInfo'] = $voteInfo;

echo \app\widgets\Alert::widget();

// 需顯示的欄位、順序: 如果候選配置是依照問題，顯示所有欄位
if($action != 'create' || $voteInfo->candiConfig != FormVotes::CANDI_CONFIG_BY_Q) {
    $showFieldSort = $candiConfig->getShowFiled(json_decode($candiConfig->showFieldSort, true));
    $defAddField = ['party', 'questionID', 'instCode']; // 需在頭部新增的欄位
    if (in_array('title', $showFieldSort)) // 如果有職稱才出現人事法規職稱(可選)
        $defAddField[] = 'tCode';
    
    $showFieldSort = array_merge(
        array_merge(
            $defAddField,
            array_diff($showFieldSort, ['party', 'questionID', 'num']) // 排除 "編號" 跟 "分組"
        ),
        ['backgroundColor', 'orderNum', 'relateParty', 'specialHonor', 'other']
    );
}

// 分組資訊
$parties = $FormVotes->getVoteParty($voteInfo->voteID, Yii::$app->language, $voteInfo->partyOrNot);

$FileLoader = new FileLoader(Yii::getAlias('@filePool'));

$form = ActiveForm::begin([
    'enableClientValidation' => true,
    'fieldConfig' => function ($model, $attribute) {
        if ($attribute == 'photo') {
                return ['template' => '
                    <label class="mb-0 mt-2">照片</label>
                    <div class="input-group">
                        <input type="text" class="form-control bg-white" id="photo-display" readonly style="cursor: pointer;" value="' .
                        (empty($model->photo) ? '請選擇照片' : '已上傳，點擊預覽查看') . '">
                        <label class="btn border text-dark" for="formcandidata-photo" style="background-color: #e9ecef; cursor: pointer;">
                            瀏覽
                        </label>
                        {input}' .
                ((empty($model->photo)) ? '' :
                    '<button type="button" class="btn bg-white border" style="color: #28a745;" data-bs-toggle="modal" data-bs-target="#photo-view">
                        ' . FAS::icon('eye') . ' 預覽
                    </button>
                    <a id="delete-photo" href="'.Url::to(['delete-photo', 'id' => $model->id, 'voteID' => $model->voteID]).'" class="btn bg-white border text-danger">
                        ' . FAS::icon('times') . ' 刪除
                    </a>'
                ).
                    '</div>
                    <div class="invalid-feedback">{error}</div>
                    {hint}
            '];
            return [];
        }
        else {
            return [
                'options' => ['class' => 'mb-0'],
            ];
        }
    },
]);
$fileLabel = Html::tag('label', 'Select a File', ['class' => 'custom-file-label']);
$fieldItem = [ // 中文欄位
    'party' => $form->field($model, 'party')->dropDownList($parties, ['prompt' => '', 'onchange' => 'getQuestions(this.value)']),
    'questionID' => $form->field($model, 'questionID')->dropDownList($questions)->hint('* 選擇分組後此欄位會自動帶入選項'),
    'jobLctn' => $form->field($model, 'jobLctn')->dropDownList(Yii::$app->params['ct.candi.jobLctnAry']),
    'instCode' => $form
        ->field($model, 'instCode')
        ->dropDownList(['00' => '']+ArrayHelper::map(Yii::$app->session->get('Share.instAry'), 'instCode', 'instName'))
        ->hint('* 此欄位作為預設排序使用，投票者無法看到此欄位！'),
    'tCode' => $form
        ->field($model, 'tCode')
        ->dropDownList(ArrayHelper::map(Yii::$app->session->get('Share.payTitle'), 'tCode', 'title'),
            ['prompt' => '']
        )
        ->hint('* 若選擇此欄位會於職稱欄位自動帶入'),
    'instName' => $form->field($model, 'instName')->textInput()->hint('* 此為投票者看到的單位，不影響候選人排序'),
    'title' => $form->field($model, 'title')->textInput(),
    'Name' => $form->field($model, 'Name')->textInput()->hint('* 必填'),
    'sex' => $form
        ->field($model, 'sex')
        ->dropDownList(Yii::$app->params['ct.candi.sexAry'],
            ['prompt' => '']
        )
        ->hint('* 若為女性保留名額，請務必填寫！'),
    'orderNum' => $form->field($model, 'orderNum')->textInput()->hint('* 此欄位為候選人主要排序(依序由小到大)'),
    'photo' => $form->field($model, 'photo')
        ->fileInput(['accept' => '.png, .jpg, .jpeg', 'class' => 'd-none'])
        ->label(false)
        ->hint('* 僅限定圖片檔格式.jpg、.jpeg、.png'),
    'backgroundColor' => $form->field($model, 'backgroundColor')
        ->textInput(['type' => 'color', 'value' => !empty($model->backgroundColor) ? $model->backgroundColor : '#ffffff']),
    'otherColA' => $form->field($model, 'otherColA')->label('自定義1 - '.$candiConfig->otherColNameA)->textInput(),
    'otherColB' => $form->field($model, 'otherColB')->label('自定義2 - '.$candiConfig->otherColNameB)->textInput(),
    'otherColC' => $form->field($model, 'otherColC')->label('自定義3 - '.$candiConfig->otherColNameC)->textInput(),
    'otherColD' => $form->field($model, 'otherColD')->label('自定義4 - '.$candiConfig->otherColNameD)->textInput(),
    'otherColE' => $form->field($model, 'otherColE')->label('自定義5 - '.$candiConfig->otherColNameE)->textInput(),
    'otherColF' => $form->field($model, 'otherColF')->label('自定義6 - '.$candiConfig->otherColNameF)->textInput(),
    'relateParty' => $form->field($model, 'relateParty')
        ->label($model->getAttributeLabel('relateParty'))
        ->dropDownList($parties, ['prompt' => '']),
    'specialHonor' => $form->field($model, 'specialHonor')
        ->label($model->getAttributeLabel('specialHonor'))
        ->dropDownList(['1' => '是'], ['prompt' => '']),
    'other' => $form->field($model, 'other')->label(false)->hiddenInput(),
];
$fieldItemE = [ // 英文欄位
    'instName' => $form->field($model, 'instNameE')->textInput()->hint('* 若中文字與單位清單相符可留白，會使用預設翻譯'),
    'title' => $form->field($model, 'titleE')->textInput()->hint('* 若使用人事法規職稱可留白，將依照人事法規職稱翻譯'),
    'Name' => $form->field($model, 'NameE')->textInput()->hint('* 換行請使用 \\n '),
    'otherColA' => $form->field($model, 'otherColAE')->label('自定義1 - '.$candiConfig->otherColNameAE)->textInput(),
    'otherColB' => $form->field($model, 'otherColBE')->label('自定義2 - '.$candiConfig->otherColNameBE)->textInput(),
    'otherColC' => $form->field($model, 'otherColCE')->label('自定義3 - '.$candiConfig->otherColNameCE)->textInput(),
    'otherColD' => $form->field($model, 'otherColDE')->label('自定義4 - '.$candiConfig->otherColNameDE)->textInput(),
    'otherColE' => $form->field($model, 'otherColEE')->label('自定義5 - '.$candiConfig->otherColNameEE)->textInput(),
    'otherColF' => $form->field($model, 'otherColFE')->label('自定義6 - '.$candiConfig->otherColNameFE)->textInput(),
];
?>
<!-- 照片預覽 -->
<div class="modal" tabindex="-1" id="photo-view" data-bs-backdrop="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">照片預覽</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <?=Html::img(['candi/view-photo', 'voteID' => $model->voteID, 'file' => $model->photo], ['width'=>'250', 'class'=>'img-fluid'])?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">關閉</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 text-center h4"><span class="badge badge-primary">中文介面顯示</span></div>
    <div class="col-md-6 text-center h4"><span class="badge badge-primary">英文介面顯示</span></div>
</div>
<?php if($action == 'create' && $voteInfo->candiConfig == FormVotes::CANDI_CONFIG_BY_Q): ?>
    <?php foreach ($fieldItem as $col => $field): ?>
        <div class="row">
            <div class="col-md-6"><?= $field ?></div>
            <div class="col-md-6"><?php if (isset($fieldItemE[$col])) echo $fieldItemE[$col]; ?></div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <?php foreach ($showFieldSort as $showFS): ?>
        <div class="row">
            <div class="col-md-6"><?= $fieldItem[$showFS] ?></div>
            <div class="col-md-6"><?php if (isset($fieldItemE[$showFS])) echo $fieldItemE[$showFS]; ?></div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php
// 已達門檻
if ($voteInfo->round > 1) {
    echo Html::tag('div',
        Html::tag('div', 
            $form->field($model, 'isReachThreshold')->dropDownList(['1' => '是'], ['prompt' => ''])
        , ['class' => 'col-md-6'])
    , ['class' => 'row']);
}
else {
    echo $form->field($model, 'isReachThreshold')->hiddenInput()->label(false);
}
echo $form->field($model, 'voteID')->hiddenInput()->label(false);
echo $form->field($model, 'genMode')->hiddenInput()->label(false);

if ($action == 'create') {
    echo Html::tag(
        'div',
        Html::submitButton('新增後編輯', ['class' => 'btn btn-primary me-2', 'name' => 'callback', 'value' => 'update', 'style' => 'width: auto']) .
        Html::submitButton('新增後另存', ['class' => 'btn btn-primary me-2', 'name' => 'callback', 'value' => 'createAs', 'style' => 'width: auto']) .
        Html::submitButton('繼續新增', ['class' => 'btn btn-primary me-2', 'name' => 'callback', 'value' => 'create', 'style' => 'width: auto']) .
        Html::submitButton('新增後回列表', ['class' => 'btn btn-primary me-2', 'name' => 'callback', 'value' => 'list', 'style' => 'width: auto']),
        ['class' => 'row justify-content-center my-2']
    );
    echo Html::tag(
        'div',
        Html::a('返回列表', ['candi/data', 'voteID' => $voteInfo->voteID], ['class' => 'btn btn-secondary', 'style' => 'width: auto']),
        ['class' => 'row justify-content-center mb-2']
    );
} else if ($action == 'update') {
    echo $form->field($model, 'id')->hiddenInput()->label(false);
    echo Html::tag(
        'div',
        Html::submitButton('修改', ['class' => 'btn btn-primary me-2', 'style' => 'width: auto']) .
            Html::a('另存', ['candi/manual', 'voteID' => $voteInfo->voteID, 'action' => 'create', 'id' => $model->id], ['class' => 'btn btn-primary', 'style' => 'width: auto']),
        ['class' => 'row justify-content-center my-2']
    );
    echo Html::tag(
        'div',
        Html::a('刪除候選人', ['candi/delete', 'voteID' => $voteInfo->voteID, 'id' => $model->id], [
            'class' => 'btn btn-danger me-2', 'style' => 'width: auto',
            'data' => ['bs-confirm' => "確定要刪除此候選人？\n* 此操作將無法還原！"],
        ]) .
            Html::a('返回列表', ['candi/data', 'voteID' => $voteInfo->voteID], ['class' => 'btn btn-secondary me-3', 'style' => 'width: auto']),
        ['class' => 'row justify-content-center mb-2']
    );
}
ActiveForm::end();

$round = $voteInfo->round;
$url = Url::to(['question/get-party-questions', 'voteID' => $voteInfo->voteID, 'round' => $round]);
$this->registerJs(<<<JS
    // 取得問題
    function getQuestions(element) {
        // 是否顯示關聯組別
        showRelateParty(element);
        // 取得組別問題
        $.ajax({
            type: "POST",
            url: '$url',
            data: {'party': element},
            success: function(result) {
                let questions = JSON.parse(result);
                $("#formcandidata-questionid").empty().append(new Option('請選擇', ''));
                questions.forEach(function(element){
                    $("#formcandidata-questionid").append(new Option(element.title, element.questionID));
                })
            }, 
            error: function(result) {
                console.log(result);
            }
        });
    }
    // 是否顯示關聯組別
    showRelateParty($('#formcandidata-party').val());
    function showRelateParty(party)
    {
        console.log(party);
        if (party == 'N') {
            $('.field-formcandidata-relateparty').show();
        }
        else {
            $('.field-formcandidata-relateparty').hide();
        }
    }
    // 點擊 photo-display 觸發檔案選擇
    $('#photo-display').on('click', function() {
        $('#formcandidata-photo').click();
    });

    // 檔案選擇時更新顯示文字
    $('#formcandidata-photo').on('change', function() {
        var fileName = $(this).val().split('\\\\').pop();
        if (fileName) {
            $('#photo-display').val(fileName);
        } else {
            $('#photo-display').val('請選擇照片');
        }
    });

    // 刪除照片
    $('#delete-photo').click(function(e){
        e.preventDefault();
        var href = $(this).attr('href');
        appDialog.confirm("確定刪除照片?", function(ok) {
            if (ok) {
                window.location.href = href;
            }
        });
    });
    document.getElementById('formcandidata-instcode').addEventListener('change', (event) => {
        if($("#formcandidata-instname").val() == '')
        {
            $("#formcandidata-instname").val(event.target.options[event.target.selectedIndex].text);
        }
    });
JS
, $this::POS_END);
