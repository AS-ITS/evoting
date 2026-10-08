<?php
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\Pjax;
use app\components\Model;
use app\models\FormVotes;
use yii\bootstrap5\Modal;
use yii\helpers\FileHelper;
use yii\helpers\HtmlPurifier;
use rmrevin\yii\fontawesome\FAS;

$this->title = \app\components\Branding::siteTitle();

echo \app\widgets\Alert::widget();

$toDate = strtotime(date('Y-m-d H:i:s')); // strtotime(date('Y-m-d H:i:s'))

// 投票狀態不為進行
if(!$isVote && $voteInfo->active != FormVotes::STATUS_ACTIVE) {
    echo Html::tag('div', 
        sprintf(
            '無法投票，投票狀態為：<b>%s</b> ',
            Yii::$app->params['ct.activeAry'][$voteInfo->active]
        ),
        ['class' => 'alert alert-warning text-center', 'role' => 'alert']
    );
}
// 非投票時間
elseif ($voteInfo->active == FormVotes::STATUS_ACTIVE && $toDate < strtotime($voteInfo->openStart)) {
    echo Html::tag('div', 
        Yii::t('app', '目前非投票時間，投票將於 {datetime} 開始！', [
            'datetime' => Model::i18n(
                str_replace([':00'], [''], Yii::$app->formatter->asDatetime(strtotime($voteInfo->openStart), "h:mm a 'on' MMMM d, yyyy"))
                ,date('Y/m/d H:i', strtotime($voteInfo->openStart))
            )
        ]),
        ['class' => 'alert alert-info text-center', 'role' => 'alert']
    );
}
// 等待驗證
elseif ($voteInfo->active == FormVotes::STATUS_ACTIVE && $toDate >= strtotime($voteInfo->openEnd) && $toDate < strtotime($voteInfo->verifyStart)) {
    echo Html::tag('div', 
        Yii::t('app', '等待驗證'),
        ['class' => 'alert alert-info text-center', 'role' => 'alert']
    );
}

echo Html::tag('h2', Html::tag('strong', trim($voteInfo->voteName)));
// 投票或查看計票按鈕
if($isVote)
{
    if(is_null($candidateList)) {
        echo Html::a(Yii::t('app', '我要投票(無候選人無法投票)'), '#', [
            'class' => 'btn btn-warning js-need-candidates',
        ]);
    }
    else {
        echo Html::a(
            FAS::icon('check-square').'&nbsp;'.Yii::t('app', '我要投票'), 
            ['start-vote', 'voteID' => $voteID], 
            ['class' => 'btn btn-primary']
        );
    }
}
elseif ($voteInfo->active == FormVotes::STATUS_ACTIVE && $toDate >= strtotime($voteInfo->verifyStart) && $toDate < strtotime($voteInfo->verifyEnd)) {
    echo Html::a(
        FAS::icon('check-square').'&nbsp;'.Yii::t('app', '查詢計票'), 
        ['count', 'voteID' => $voteID],
        ['class' => 'btn btn-primary']
    );
}
// 附件檔案
$path = FileHelper::normalizePath(realpath(Yii::getAlias('@filePool').'/candidateFile/'.$voteInfo->voteID));
$files = !empty($path) ? scandir($path) : [];
foreach ($files as $key => $file) {
    if (in_array($file, ['.', '..'])) {
        unset($files[$key]);
    }
}
// 投票候選人名單
$candidateListName = Yii::t('app', '投票候選名單');
// 投票要點
$isPoints = strip_tags($notice = trim(Model::i18n($voteInfo->noticeE, $voteInfo->notice, false))) !== '';
// 圈選須知
$isInformation = strip_tags($information = trim(Model::i18n($voteInfo->informationE, $voteInfo->information, false))) !== '';
// 候選人備註
$isCandComment = strip_tags($candComment = trim(Model::i18n($voteInfo->candCommentE, $voteInfo->candComment, false))) !== '';
// 自定義資訊
$isOtherInfo = strip_tags($otherInfo = trim(Model::i18n($voteInfo->otherInfoE, $voteInfo->otherInfo, false))) !== '';
?>
<hr>
<div class="btn-group" role="group" aria-label="Basic example">
    <?=($isPoints || $isInformation)?Html::a(FAS::icon('info-circle', ['class' => 'me-2']).Yii::t('app', '投票要點(須知)'), '#points', ['class' => 'btn btn-sm btn-dark']) : ''?>
    <a href="#list" class="btn btn-sm btn-dark"><?=FAS::icon('users').'&nbsp;'.Yii::t('app', $candidateListName);?></a>
    <?=$isCandComment?Html::a(FAS::icon('sticky-note', ['class' => 'me-2']).Yii::t('app', $candidateListName.'備註'), '#remark', ['class' => 'btn btn-sm btn-dark']) : '' ?>
    <?=count($files)>0?'<a href="#files" class="btn btn-sm btn-dark">'.FAS::icon('file-alt').'&nbsp;'.Yii::t('app', '參考附件').'</a>':'';?>
    <?=$isOtherInfo ? Html::a(FAS::icon('list').'&nbsp;'.Model::i18n($voteInfo->otherInfoTitleE, $voteInfo->otherInfoTitle), '#otherInfo', ['class' => 'btn btn-sm btn-dark']) : '' ?>
</div>
<!-- 投票要點 -->
<?php if($isPoints || $isInformation): ?>
<div class="card mt-4" id="points">
    <div class="card-header bg-info text-white">
        <b>
            <?php
            echo Html::tag(
                'font',
                !$isInformation ? // 圈選須知
                '<i class="fas fa-info-circle me-2"></i>'.Yii::t('app', '投票要點(須知)'):
                sprintf(
                    '<i class="fas fa-info-circle me-2"></i>'.Yii::t('app', '投票要點(須知)').' (%s)',
                    Html::a(Yii::t('app', '點我查看圈選須知'), '#', ['class' => 'text-white', 'type'=>'link','data-bs-toggle'=>'modal','data-bs-target'=>'#Details'])
                )
            , ['size' => 5]);
            ?>
        </b>
    </div>
    <?php if($isPoints): ?>
    <div class="card-body">
        <?php
            echo !$isPoints ? Model::i18n('(Empty)', '(無)') : HtmlPurifier::process($notice);
        ?>
    </div>
    <?php endif ?>
</div>
<?php endif; ?>

<!-- 投票候選人名單 -->
<div class="card mt-4" id="list">
    <div class="card-header bg-info text-white">
        <b>
            <?php
                echo Html::tag('font', 
                    FAS::icon('users', ['class' => 'me-2']).Yii::t('app', $candidateListName),
                    ['size' => 5]
                );
            ?>
        </b>
    </div>
    <div class="card-body">
        <?php
            Pjax::begin(['id' => 'myGrid']);
            echo Html::tag(
                'details',
                Html::tag('summary', Yii::t('app', '展開 / 收起'), ['class' => 'text-center']).
                Html::dropDownList(
                    'question', 
                    is_null(Yii::$app->request->get('questionID')) ? '' : Yii::$app->request->get('questionID'), 
                    $questions, 
                    [
                        'prompt' => '請選擇', 
                        'class' => 'form-select col-md-2 float-start mb-2'.($voteInfo->candiConfig != FormVotes::CANDI_CONFIG_BY_Q ? ' d-none' : ''), 
                        'onchange' => 'getQuestionCandi(this.value)', 'style' => 'width: auto',
                    ]
                ).
                $this->render('_candi-list', [
                    'candiConfig' => $candiConfig,
                    'candidateList' => $candidateList,
                    'candidateListName' => $candidateListName,
                    'parties' => $model->getVoteParty($voteInfo->voteID, Yii::$app->language),
                    'questions' => $model->getVoteQuestion($voteInfo->voteID, $voteInfo->round, Yii::$app->language),
                ]),
                ['open' => true]
            );
            Pjax::end();
        ?>
    </div>
</div>

<!-- 候選人名單備註 -->
<?php if($isCandComment): ?>
<div class="card mt-4" id="remark">
    <div class="card-header bg-info text-white">
        <b>
            <?php
                echo Html::tag('font', 
                    FAS::icon('sticky-note', ['class' => 'me-2']).Yii::t('app', $candidateListName.'備註'),
                    ['size' => 5]
                );
            ?>
        </b>
    </div>
    <div class="card-body">
        <?php
            echo !$isCandComment ? Model::i18n('(Empty)', '(無)') : HtmlPurifier::process($candComment);
        ?>
    </div>
</div>
<?php endif; ?>

<!-- 附件檔案 -->
<?php if(count($files) > 0): ?>
    <div class="card mt-4" id="files">
        <div class="card-header bg-info text-white">
            <b>
                <?php
                    echo Html::tag('font', 
                        FAS::icon('file-alt', ['class' => 'me-2']).Yii::t('app', '參考附件'),
                        ['size' => 5]
                    );
                ?>
            </b>
        </div>
        <div class="card-body">
            <table class="table table-sm text-center">
                <thead>
                    <tr>
                        <th scope="col"><?=Yii::t('app', '附件名稱')?></th>
                        <th scope="col"><?=Yii::t('app', '下載')?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($files as $key => $file): ?>
                    <tr>
                        <td><?=$file?></td>
                        <td>
                            <?=
                                Html::a(
                                    FAS::icon('download', ['class' => 'text-primary']), 
                                    Url::to(['download-file', 'file' => $key, 'voteID' => $voteID])
                                )
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<!-- 自訂義資訊 -->
<?php if($isOtherInfo): ?>
<div class="card mt-4" id="otherInfo">
    <div class="card-header bg-info text-white">
        <b>
            <?php
                echo Html::tag('font', 
                    FAS::icon('list', ['class' => 'me-2']).Model::i18n($voteInfo->otherInfoTitleE, $voteInfo->otherInfoTitle),
                    ['size' => 5]
                );
            ?>
        </b>
    </div>
    <div class="card-body">
        <?php
            echo !$isOtherInfo ? Model::i18n('(Empty)', '(無)') : HtmlPurifier::process($otherInfo);
        ?>
    </div>
</div>
<?php endif; ?>
<?php
// 圈選須知
if(strip_tags($information) != '')
{
    Modal::begin([
        'id' => 'Details', 
        'dialogOptions' => ['class' => 'modal-xl modal-dialog-centered'],
        'options' => ['style' => 'background-color: rgba(0, 0, 0, 0.5); '],
        'clientOptions' => ['backdrop' => false],
        'title' => FAS::icon('info-circle', ['class' => 'me-2']).Yii::t('app', '圈選須知'),
        'titleOptions' => ['class' => 'fw-bolder'],
        'headerOptions' => ['class' => 'bg-info text-light'],
        'scrollable' => true, // 當內容過長是否可滾動
        'closeButton' => false, // 右上角 X
        'footer' => Html::tag('div', 
            Html::tag('span',
                Html::Button(FAS::icon('check', ['class' => 'me-2']).Yii::t('app', '確定'), ['class' => 'btn btn-info', 'data-bs-dismiss' => 'modal'])
            ),
            ['class'=>'text-center']
        ),
        'footerOptions' => ['class' => 'justify-content-center']
    ]);
    echo !$isInformation ? Model::i18n('(Empty)', '(無)') : HtmlPurifier::process($voteInfo->replaceQuestionRule($information));
    Modal::end();
}

$this->registerJs(<<<JS
    $(document).on('click', '.js-need-candidates', function(e) {
        e.preventDefault();
        appDialog.warn('請洽詢主辦單位，必須建立候選人，方可進行投票！');
    });
    // 取得候選人
    function getQuestionCandi(questionID) {
        $.pjax.reload({ container: '#myGrid', url: updateURLParameter(window.location.href, "questionID", questionID) });
    }
JS
, $this::POS_END);
