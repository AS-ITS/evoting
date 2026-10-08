<?php
use yii\helpers\Html;
use app\components\Model;
use kartik\form\ActiveForm;
use rmrevin\yii\fontawesome\FAS;
use yii\helpers\Url;

$this->title = strip_tags(Model::i18n($voteInfo->NameE, $voteInfo->Name, false));

$text = Yii::t('app', '登入');

$form = ActiveForm::begin([
    'id' => 'login-form',
    'enableClientValidation' => false,
    'enableAjaxValidation' => false,
    'validateOnSubmit' => false,
    'fieldConfig' => [
        'showRequiredIndicator' => false,
        'template' => '
            <div class="row mb-0">
                {label}
                <div class="col-md-6 col-lg-5 col-xl-3 ps-0">
                    {beginWrapper}
                    {input}{hint}{error}
                    {endWrapper}
                </div>
            </div>'
    ],
]);
?>
<div class="card" id="password-form">
    <div id="<?=Yii::$app->language == 'en-US' ? 'enTitle' : 'voteTitle' ?>" class="card-header text-center"><?=$voteInfo->voteName?></div>
    <div class="card-body pb-0">
        <?php
            echo Html::tag('p', $hint, ['id' => 'hint', 'class' => 'fw-bolder text-center']);
            echo Html::tag(
                'div',
                Html::tag(
                    'div',
                    (!empty($voteInfo->session) ?
                    $form->field($model, 'session', [
                        'options' => ['class' => 'mb-0'],
                        'labelOptions' => ['class' => 'col-md-6 col-lg-5 col-xl-5 col-form-label text-end pr-0'],
                    ])
                    ->label(Yii::t('app', '場次碼').'：')
                    ->textInput([
                        'class'=>'form-control border-dark',
                        'id' => 'session',
                        // 'placeholder' => Yii::t('app', '請在此輸入場次碼'),
                    ]) : '').
                    $form->field($model, 'password', [
                        'labelOptions' => ['class' => 'col-md-6 col-lg-5 col-xl-5 col-form-label text-end pr-0'],
                        'addon' => [
                            'append' => [
                                'content' => Html::button(FAS::icon('eye-slash'), [
                                    'id' => 'eye', 
                                    'class'=>'btn btn-outline-dark border-left-0 py-1 border-dark rounded-right eye',
                                ]), 
                                'asButton' => true,
                            ],
                        ],
                    ])
                    ->label(Yii::t('app', '密碼').'：')
                    ->passwordInput([
                        'class'=>'form-control border-right-0 border-dark',
                        'id' => 'passwd',
                        'autocomplete' => 'off',
                        // 'placeholder' => Yii::t('app', '請在此輸入投票密碼'),
                    ])
                    // ->hint(Yii::t('app', '點擊眼睛圖示可以查看輸入的密碼'))
                    , ['class' => 'col-md-8']
                ),
                ['id' => 'user_input', 'class' => 'd-flex justify-content-center']
            );
        ?>
    </div>
    <div class="card-footer">
        <?=
            ($isLock ?
                Html::tag('div', 
                    Html::tag('p', Yii::t('app','由於您密碼錯誤次數過多，請稍後再試！'), ['class'=>'text-danger m-0'])
                , ['class'=>'row justify-content-center m-0'])
                : ''
            ).
            Html::tag(
                'div',
                $form->field($model, 'voteID')->hiddenInput()->label(false)
                . Html::tag('div',
                    Html::submitButton(
                        Html::tag('span', Yii::t('app', '登入'), ['id' => 'click-button-text']),
                        ['class' => 'btn btn-secondary btn-md me-3', 'id' => 'click-button']
                    ),
                    ['class' => 'col-auto p-0']
                )
                . Html::tag('div',
                    Html::button(
                        Html::tag('span', Yii::t('app', '清空'), ['id' => 'reset-button-text']),
                        ['class' => 'btn btn-secondary btn-md', 'id' => 'reset-button']
                    ),
                    ['class' => 'col-auto p-0']
                ),
                ['class' => 'row justify-content-center m-0 align-items-center']
            );
        ?>
    </div>
</div>
<?php
ActiveForm::end();

$bgColor = !empty($voteInfo->themeColor) ? $voteInfo->themeColor : '#ffffff';
$this->registerCss(<<<CSS
    body {
        background-color: $bgColor;
    }
CSS
);
$url = Url::to(['site/vote-status', 'voteID' => $voteInfo->voteID]);
$csrfTokenName = Yii::$app->request->csrfParam;
$this->registerJs(<<<JS
    var canVote = '$canVote';
    voteStatus(canVote);

    // 每隔 5 秒更新提示訊息
    setInterval(function() {
        $.ajax({
            url: '$url',
            type: 'GET',
            success: function(response) {
                var data = $.parseJSON(response);
                $('#hint').text(data.hint);
                $('.card-header').html(data.voteTitle);
                voteStatus(data.canVote);
            }
        });
    }, 5000);

    // 投票狀態顯示
    function voteStatus(can) {
        if (can == '') {
            $('#user_input').removeClass('d-flex').addClass('d-none');
            $('.card-footer').addClass('d-none');
        }
        else {
            $('#user_input').removeClass('d-none').addClass('d-flex');
            $('.card-footer').removeClass('d-none');
        }
    }
JS
, \yii\web\View::POS_READY); 

$this->registerJs(<<<JS
    $('#eye').click(function(e) {
        var pswd = $('#passwd');
        pswd.attr("type", pswd.attr('type') == 'password' ? 'text' : 'password');
        this.children[0].classList.toggle('fa-eye');
        this.children[0].classList.toggle('fa-eye-slash');
    });

    // Prevent form resubmission when page is refreshed
    // https://stackoverflow.com/questions/6320113/how-to-prevent-form-resubmission-when-page-is-refreshed-f5-ctrlr
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    function paddedFormat(num) {
        return num < 10 ? "0" + num : num;
    }

    function startCountDown(duration, element) {
        let secondsRemaining = duration;
        let min = 0;
        let sec = 0;
        let countInterval = setInterval(function () {
            min = parseInt(secondsRemaining / 60);
            sec = parseInt(secondsRemaining % 60);
            element.textContent = `{$text} (\${paddedFormat(min)}:\${paddedFormat(sec)})`;
            secondsRemaining = secondsRemaining - 1;
            if (secondsRemaining < 0) {
                clearInterval(countInterval);
                element.textContent = `{$text}`;
                $('#click-button').prop('disabled', false);
            }
            // Prevent users from submitting a form by hitting Enter
            // https://stackoverflow.com/questions/895171/prevent-users-from-submitting-a-form-by-hitting-enter?page=1&tab=scoredesc#tab-top
            $(window).keydown(function(event){
                if(secondsRemaining > 0 && event.keyCode == 13) {
                    event.preventDefault();
                    return false;
                }
            });
        }, 1000);
    }

    let duration = {$waitTime};
    if(duration > 0)
    {
        let time_minutes = parseInt(duration / 60); // Value in minutes
        let time_seconds = parseInt(duration % 60); // Value in seconds
        element = document.querySelector('#click-button-text');
        element.textContent = `{$text} (\${paddedFormat(time_minutes)}:\${paddedFormat(time_seconds)})`;
        $('#click-button').prop('disabled', true);
        startCountDown(--duration, element);
    }
    else
    {
        $('#click-button').prop('disabled', false);
    }

    // 清空
    $('#reset-button').click(function(e){
        $('#session, #passwd').val('');
    });
JS
, \yii\web\View::POS_READY);