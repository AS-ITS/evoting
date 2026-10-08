<?php

use yii\helpers\Url;
use app\models\Votes;
use yii\helpers\Html;
use yii\helpers\Json;
use app\models\Parties;
use app\models\Questions;
use yii\bootstrap5\Modal;
use kartik\form\ActiveForm;
use yii\helpers\ArrayHelper;
use rmrevin\yii\fontawesome\FAS;

$groups = ArrayHelper::map($groups ?: [], 'groupId', 'groupName');

$dateFormat = function ($d) {
    if (trim($d) == '')
        return '';
    return date('Y-m-d\TH:i', strtotime($d));
};

if ($action == 'edit') {
    $title = '編輯投票';
    $this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航
    $this->params['showVoteInfo'] = true;
    $this->params['title'] = $title;
    $this->params['voteInfo'] = $voteInfo;
} else if ($action == 'create') {
    $title = '建立投票';
    echo Html::tag('div', Html::tag('h2', $title), ['class' => 'text-center']);
    echo Html::beginTag('hr');
}

$this->title = $title;
$partyLimit = (Yii::$app->session->get('System.config') ?? [])['partyLimit'] ?? null;

if ($action == 'edit') {
    // 附件上傳modal
    echo $this->render('_file', ['voteID' => $voteInfo->voteID, 'FormUploadFile' => $FormUploadFile]);
    $BasicDataTypeTrigger = "\tFuncSwitch();";
    $TriggerFunction = <<<EOT
    const patternId = 'formvotes-pattern';
    const partyornotId = 'formvotes-partyornot';
    const typeId = 'formvotes-type';
    const addiconditionId = 'formvotes-addicondition';

    const isbypartyId = 'formvotes-isbyparty';
    const isbindvoteId = 'formvotes-isbindvote';
    const bindwhichvoteId = 'formvotes-bindwhichvote';
    const sessionId = 'formvotes-session';
    const authbeforedetailId = 'formvotes-authbeforedetail';

    // 停用方法
    function deactivate(obj,status) {
        // console.log(obj);
        const flag = 'readonly';
        obj.attr(flag,status);
        if(status)
        {
            obj.css('pointer-events','none');
        }
        else
        {
            obj.css('pointer-events','');
        }
    }

    var Ids = [
        partyornotId, typeId, addiconditionId
    ];
    for (var id in Ids) {
        deactivate($('#'+Ids[id]), true);
    }

    if($('#'+partyornotId).val() == 0)
    {
        deactivate($('#'+isbypartyId), true);
    }

    if($('#'+typeId).val() == 1)
    {
        $('#'+isbindvoteId)[0].addEventListener('change', (event) => {
            switch ( $(event.target).val() ) {
                case '0':
                    $('#'+bindwhichvoteId).val('');
                    deactivate($('#'+bindwhichvoteId), true);
                    break;
                case '1':
                    $('#bind-vote-alert').modal('show');
                    deactivate($('#'+bindwhichvoteId), false);
                    break;
            }
        });
        switch ( $('#'+isbindvoteId).val() ) {
            case '0':
                deactivate($('#'+bindwhichvoteId), true);
                break;
            case '1':
                deactivate($('#'+bindwhichvoteId), false);
                break;
        }
    }
    else
    {
        deactivate($('#'+isbindvoteId), true);
        deactivate($('#'+bindwhichvoteId), true);
        deactivate($('#'+sessionId), true);
    }

EOT;
} else if ($action == 'create') {
    $BasicDataTypeTrigger = "\tFuncSwitch();\n\tVoterParty();";
    $division0Ary = Json::encode(Yii::$app->params['ct.division0Ary']);
    $division1Ary = Json::encode(Yii::$app->params['ct.division1Ary']);
    $division1EAry = Json::encode(Yii::$app->params['ct.division1EAry']);
    $division3Ary = Json::encode(Yii::$app->params['ct.division3Ary']);
    $division4Ary = Json::encode(Yii::$app->params['ct.division4Ary']);
    $TriggerFunction = <<<EOT

    /* ==================================================
        以下是判斷
            投票樣版、投票類型、保留名額、是否分組、
            是否共用投票密碼、共用密碼之投票、
            投票結果顯示
        是否可選、開關
       ==================================================*/

    const patternId = 'formvotes-pattern';
    const partyornotId = 'formvotes-partyornot';
    const isbypartyId = 'formvotes-isbyparty';
    const typeId = 'formvotes-type';
    const isbindvoteId = 'formvotes-isbindvote';
    const bindwhichvoteId = 'formvotes-bindwhichvote';
    const addiconditionId = 'formvotes-addicondition';
    const sessionId = 'formvotes-session';
    const authbeforedetailId = 'formvotes-authbeforedetail';
    // 設定 投票規則 區塊顯示
    function VoterPartyStatus(type = "def", title = "", status = false) {
        const partieColumn = [
            'numballots','leastnumballots','maxelect','numofkeep','numfemalekeep'
        ];

        partieColumn.forEach(function(column){
            $("#formparties-"+type+"-"+column).attr('disabled', !status);
        });

        if(status)
        {
            $("#vr-"+type+" > h5").text(title[type]);
            $("#vr-"+type).removeClass('d-none');
        }
        else
        {
            $("#vr-"+type+" > h5").text("");
            $("#vr-"+type).addClass('d-none');
        }
    }

    // 單獨設定 查看投票資訊是否驗證
    function VoteDetailValidateStatus(status = false) {
        if(status)
        {
            $(".vl-authBeforeDetail").removeClass('d-none');
        }
        else
        {
            $(".vl-authBeforeDetail").addClass('d-none');
        }
    }

    // 單獨設定 女性保留名額
    function VoterFemaleStatus(type = "def", status = false) {
        if(status)
        {
            $("#vr-"+type+"-female").removeClass('d-none');
        }
        else
        {
            $("#vr-"+type+"-female").addClass('d-none');
        }
        $("#formparties-"+type+"-numfemalekeep").attr('disabled', !status);
    }

    // 設定分組
    function setParty(type = "def", canRemove = true, def = false) {
        const voteType = $('#'+typeId);// 投票類型
        const division1Ary = $division1Ary;
        let removeHtml = '';
        let inputHtml = '';
        if(canRemove) {
            removeHtml = `
                <div class="input-group-prepend">
                    <div class="input-group-text">
                        <a href="#" class="remove_party">刪除此分組</a>
                    </div>
                </div>
            `;
        }
        else {
            removeHtml = `
                <div class="input-group-prepend">
                    <div class="input-group-text">
                        預設
                    </div>
                </div>
            `;
        }

        // 根據不同投票類型產生投票名稱輸入選擇
        switch (voteType.val()) {
            case "0": // 表決
                inputHtml = `
                        <h5 class="card-title text-primary">預設</h5>
                        <input type="hidden" name="FormParties[`+type+`][name]" value="預設">
                        <input type="hidden" name="FormParties[`+type+`][nameE]" value="Default">
                    `;
                break;
            case "1": // 匿名
                if(type == "def") {
                    inputHtml = `
                        <h5 class="card-title text-primary">預設</h5>
                        <input type="hidden" name="FormParties[`+type+`][name]" value="預設">
                        <input type="hidden" name="FormParties[`+type+`][nameE]" value="Default">
                    `;
                }
                else {
                    inputHtml = removeHtml+`
                        <input type="text" class="form-control" name="FormParties[`+type+`][name]" id="formparties-`+type+`-name" placeholder="請輸入分組中文名稱" required>
                        <input type="text" class="form-control" name="FormParties[`+type+`][nameE]" id="formparties-`+type+`-nameE" placeholder="請輸入分組英文名稱" required>
                        <div class="invalid-feedback"></div>
                    `;
                }
                break;
        }

        let party = `
        <div id="vr-`+type+`" class="no-gutters new-party`+(def == true ? ' def-party' : '')+`">
            <label class="sr-only" for="partyName`+type+`">分組名稱</label>
            <div class="input-group mb-2 field-formparties-`+type+`-name field-formparties-`+type+`-nameE">
                `+inputHtml+`
            </div>

            <div class="card-text">
                <div class="row">
                    <div class="col-lg">
                        <div class="mb-3 field-formparties-`+type+`-numballots">
                            <label for="formparties-`+type+`-numballots">最多可投票數</label>
                            <input type="text" id="formparties-`+type+`-numballots" class="form-control" name="FormParties[`+type+`][numBallots]" value="1" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="col-lg">
                        <div class="mb-3 field-formparties-`+type+`-leastnumballots">
                            <label for="formparties-`+type+`-leastnumballots">最少應投票數</label>
                            <input type="text" id="formparties-`+type+`-leastnumballots" class="form-control" name="FormParties[`+type+`][leastNumBallots]" value="1" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="col-lg">
                        <div class="form-group field-formparties-`+type+`-maxelect">
                            <label for="formparties-`+type+`-maxelect">當選人數</label>
                            <input type="text" id="formparties-`+type+`-maxelect" class="form-control" name="FormParties[`+type+`][maxElect]" value="1" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="col-lg">
                        <div class="form-group field-formparties-`+type+`-numofkeep">
                            <label for="formparties-`+type+`-numofkeep">遞補人數</label>
                            <input type="text" id="formparties-`+type+`-numofkeep" class="form-control" name="FormParties[`+type+`][numOfKeep]" value="1" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="col-lg`+($('#'+addiconditionId).val() === 'female' ? '' : ' d-none')+`" id="vr-`+type+`-female">
                        <div class="form-group field-formparties-`+type+`-numfemalekeep">
                            <label for="formparties-`+type+`-numfemalekeep">女性保留人數</label>
                            <input type="text" id="formparties-`+type+`-numfemalekeep" class="form-control" name="FormParties[`+type+`][numFemaleKeep]" value="0" `+($('#'+addiconditionId).val() === 'female' ? '' : 'disabled="disabled"')+`>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;
        return party;
    }

    // 新增分組 : max為總數，如果預設是2組，則最多可以再加(max-2)組自訂
    function addParty(max, defNum = 2) {
        let i = [];
        // 投票類型、是否分組及保留名額改變時，重新計算
        $('#'+partyornotId+', #'+typeId+', #'+addiconditionId).change(function() {
            i = [];
            for(elements=defNum;elements < max;elements++) {
                i.push(elements);
            }
        });
        $("#addNewParty").click(function(e) { 
            e.preventDefault();
            if(i.length > 0){ 
                let element = i.shift();
                $("#parties").append(setParty(element));
            }
            else {
                appDialog.warn("已達分組上限");
            }
        });
        
        // 將刪除的id加回去陣列中
        $("#parties").on("click", ".remove_party", function(e){
            e.preventDefault(); 
            $(this).parentsUntil("#parties", ".new-party").remove();
            i.push($(this).parentsUntil("#parties", ".new-party")[0].id.split("-")[1]);
            i.sort();
        });
    }
    
    // 設定新增分組上限
    addParty($partyLimit);

    // 記名投票分組名稱不得相同
    const division1EAry = $division1EAry;
    let mutations = new MutationObserver(function (mutations) {
        $(".partySelect").change(function(e) {
            let partyName = [];
            $(".partySelect").each(function(i, obj) {
                if(partyName.includes($('option:selected',obj).text()) && $('option:selected',obj).text() != '請選擇') {
                    appDialog.warn($('option:selected',this).text()+'已存在');
                    $(this).val('');
                    $("#"+this.id.replace(/name/g, 'nameE')).val('');
                }
                else {
                    partyName.push($('option:selected',obj).text());
                    $("#"+obj.id.replace(/name/g, 'nameE')).val(division1EAry[$('option:selected', obj).val()]);
                }
            });
        });
    });
    let target = document.querySelector('#parties');
    let config = { attributes: true, childList: true, characterData: true };
    mutations.observe(target, config);

    // 建立時處理投票規則
    function VoterParty() {
        const pattern = $('#'+patternId);// 樣版
        const partyornot = $('#'+partyornotId);// 是否分組
        const addicondition = $('#'+addiconditionId);// 女性保留名額
        const type = $('#'+typeId);// 投票類型

        const division0Ary = $division0Ary;
        const division1Ary = $division1Ary;

        switch (partyornot.val()) {
            case "0":// 不分組
                $("#addNewParty").addClass('d-none');
                // 刪除分組的內容
                $('div[id^="vr-"]').remove();
                // 建立預設分組
                $("#parties").append(setParty('def', false, true));
                break;
            case "1":// 分組
                $("#addNewParty").removeClass('d-none');
                // 刪除不分組的內容
                $('div[id^="vr-"]').remove();
                // 建立預設分組
                if($('#vr-0').length == 0 && $('#vr-1').length == 0) {
                    $("#parties").append(setParty(0, false, true));
                    $("#parties").append(setParty(1, false, true));
                }
                
                break;
        }
        switch (addicondition.val()) {
            case "n":// 無
                $('div[id^="vr-"]').each(function(i, obj) {
                    VoterFemaleStatus(obj.id.split("-")[1], false);
                });
                break;
            case "female":// 女性保留名額
                $('div[id^="vr-"]').each(function(i, obj) {
                    VoterFemaleStatus(obj.id.split("-")[1], true);
                });
                break;
        }
        switch (type.val()) {
            case "0": // 表決
                VoteDetailValidateStatus(false);// 查看投票資訊是否驗證
                break;
            case "1": // 匿名
                VoteDetailValidateStatus(true);// 查看投票資訊是否驗證
                break;
        }
    }

    // 停用方法
    function deactivate(obj,status) {
        const flag = 'readonly';
        obj.attr(flag,status);
        if(status)
        {
            obj.css('pointer-events','none');
        }
        else
        {
            obj.css('pointer-events','');
        }
    }

    // 控制 基本設定 相關欄位
    function FuncSwitch() {
        const pattern = $('#'+patternId);
        const partyornot = $('#'+partyornotId);
        const isbyparty = $('#'+isbypartyId);
        const type = $('#'+typeId);
        const isbindvote = $('#'+isbindvoteId);
        const bindwhichvote = $('#'+bindwhichvoteId);
        const addicondition = $('#'+addiconditionId);
        const session = $('#'+sessionId);
        const authbeforedetail = $('#'+authbeforedetailId);

        deactivate(type, false);

        switch (type.val()) // 投票類型
        {
            case '0':
                partyornot.val(0);// 分組
                deactivate(partyornot, true);
                isbindvote.val(0);// 不共用投票
                deactivate(isbindvote, true);
                addicondition.val('n');// 無保留名額
                deactivate(addicondition, true);
                session.val('');// 無場次碼
                deactivate(session, true);
                authbeforedetail.val(0);// 無查看投票資訊是否驗證
                deactivate(authbeforedetail, true);
                break;
            case '1':
                deactivate(partyornot, false);
                deactivate(isbindvote, false);
                deactivate(addicondition, false);
                deactivate(session, false);
                deactivate(authbeforedetail, false);
                break;
        }

        switch (partyornot.val()) // 分組
        {
            case '0':
                isbyparty.val(0);// 看同組別選票
                deactivate(isbyparty, true);
                break;
            case '1':
                deactivate(isbyparty, false);
                break;
        }

        switch (isbindvote.val()) // 共用密碼之投票
        {
            case '0':
                bindwhichvote.val('');
                deactivate(bindwhichvote, true);
                break;
            case '1':
                $('#bind-vote-alert').modal('show');
                deactivate(bindwhichvote, false);
                break;
        }
    }

    var Ids = [
        partyornotId, isbypartyId, typeId, isbindvoteId, bindwhichvoteId, addiconditionId
    ];
    for (var id in Ids) {
        document.getElementById(Ids[id]).addEventListener('change', () => {
            $BasicDataTypeTrigger
        }, false);
    }

    $BasicDataTypeTrigger

EOT;
}

$this->registerJs(
    <<<EOT

    $TriggerFunction

    /* ==================================================
       === 以下是判斷 投票時間(含起迄)、驗證時間(含起迄) ===
       ==================================================*/

    const openstart = document.getElementById('formvotes-openstart');
    const openend = document.getElementById('formvotes-openend');
    const verifystart = document.getElementById('formvotes-verifystart');
    const verifyend = document.getElementById('formvotes-verifyend');
    const dateField = [ openstart, openend, verifystart, verifyend];

    var promptError = function ( obj, isError) {
        if(isError == false)
        {
            appDialog.warn($(obj)[0].nextElementSibling.innerText.substr(2));
            isError = true;
        }
        return isError;
    };

    dateField.forEach(function(field){
        field.addEventListener('focusin', (event) => {
            // 建立準備編輯的欄位限制
            switch (event.target) {
                case openstart:
                    var openEndDate = $(openend).val();
                    if(openEndDate != '')
                    {
                        $(event.target).attr('max', openEndDate);
                    }
                    break;
                case openend:
                    var openStartDate = $(openstart).val();
                    var verifyStartDate = $(verifystart).val();
                    if(openStartDate != '')
                    {
                        $(event.target).attr('min', openStartDate);
                    }
                    if(verifyStartDate != '')
                    {
                        $(event.target).attr('max', verifyStartDate);
                    }
                    break;
                case verifystart:
                    var openEndDate = $(openend).val();
                    var verifyEndDate = $(verifyend).val();
                    if(openEndDate != '')
                    {
                        $(event.target).attr('min', openEndDate);
                    }
                    if(verifyEndDate != '')
                    {
                        $(event.target).attr('max', verifyEndDate);
                    }
                    break;
                case verifyend:
                    var verifyStartDate = $(verifystart).val();
                    if(verifyStartDate != '')
                    {
                        $(event.target).attr('min', verifyStartDate);
                    }
                    break;
            }
        });
        field.addEventListener('change', (event) => {
            const openStartDate = $(openstart).val();
            const openEndDate = $(openend).val();
            const verifyStartDate = $(verifystart).val();
            const verifyEndDate = $(verifyend).val();
            
            // 設置當欄位為空或者日期格式異常(也為空)時，顯示驚嘆號！
            for (let df in dateField) {
                if( $(dateField[df]).val() == '' )
                {
                    $(dateField[df]).addClass('is-invalid');
                }
            };

            // 是否已有任何錯誤
            var isError = false;

            // 投票時間(起)
            if(openStartDate != '')
            {
                // 修改對於別的欄位的限制
                $(openend).attr('min', openStartDate);

                if( openEndDate != '' )
                {
                    // 限制在 投票時間(迄) 之前
                    if ( ((Date.parse(openStartDate)).valueOf() >= (Date.parse(openEndDate)).valueOf()))
                    {
                        $(openstart).addClass('is-invalid');
                        isError = promptError(openstart,isError);
                    }
                    else
                    {
                        $(openstart).removeClass('is-invalid');
                    }
                }
                // openEndDate 為空，由該物件自行處置
            }

            // 投票時間(迄)
            if( openEndDate != '' )
            {
                // 修改對於別的欄位的限制
                $(openstart).attr('max', openEndDate);
                $(verifystart).attr('min', openEndDate);

                var status = true;

                if(openStartDate != '')
                {
                    // 限制在 投票時間(起) 之後
                    if ( (Date.parse(openStartDate)).valueOf() >= (Date.parse(openEndDate)).valueOf())
                    {
                        $(openend).addClass('is-invalid');
                        isError = promptError(openend,isError);
                        status = false;
                    }
                }

                if(verifyStartDate != '')
                {
                    // 限制在 驗證時間(起) 之前
                    if ( (Date.parse(openEndDate)).valueOf() > (Date.parse(verifyStartDate)).valueOf())
                    {
                        $(openend).addClass('is-invalid');
                        isError = promptError(openend,isError);
                        status = false;
                    }
                }

                if(status) // 欄位有誤
                {
                    $(openend).removeClass('is-invalid');
                }
            }

            // 驗證時間(起)
            if( verifyStartDate != '' )
            {
                // 修改對於別的欄位的限制
                $(openend).attr('max', verifyStartDate);
                $(verifyend).attr('min', verifyStartDate);

                var status = true;

                if( openEndDate != '' )
                {
                    // 限制在 投票時間(迄) 之後
                    if ( (Date.parse(openEndDate)).valueOf() > (Date.parse(verifyStartDate)).valueOf())
                    {
                        $(verifystart).addClass('is-invalid');
                        isError = promptError(openend,isError);
                        status = false;
                    }
                }

                if( verifyEndDate != '' )
                {
                    // 限制在 驗證時間(迄) 之前
                    if ( (Date.parse(verifyStartDate)).valueOf() > (Date.parse(verifyEndDate)).valueOf())
                    {
                        $(verifystart).addClass('is-invalid');
                        isError = promptError(openend,isError);
                        status = false;
                    }
                }

                if(status) // 欄位有誤
                {
                    $(verifystart).removeClass('is-invalid');
                }
            }

            // 驗證時間(迄)
            if( verifyEndDate != '' )
            {
                // 修改對於別的欄位的限制
                $(verifystart).attr('max', verifyStartDate);

                if( verifyStartDate != '' )
                {
                    // 限制在 驗證時間(起) 之後
                    if ( (Date.parse(verifyStartDate)).valueOf() >= (Date.parse(verifyEndDate)).valueOf())
                    {
                        $(verifyend).addClass('is-invalid');
                        isError = promptError(verifyend,isError);
                    }
                    else
                    {
                        $(verifyend).removeClass('is-invalid');
                    }
                }
            }
        });
    });
EOT
);

// 驗證分組輸入
$this->registerJs(<<<JS
    $('#form-submit').click(function(e) {
        e.preventDefault();
        let partyCount = $('.new-party').length;
        let partyInput = ['name', 'nameE', 'numballots', 'leastnumballots', 'maxelect', 'numofkeep'];
        let minPartyInput = ['numballots', 'leastnumballots', 'maxelect'];
        let errorCount = 0;
        // 分組
        if(partyCount > 1) {
            for(i=0; i < partyCount; i++) {
                partyInput.forEach(function(value){
                    if($('#formparties-'+i+'-'+value).val() == '' || $('#formparties-'+i+'-'+value).val() == null) {
                        $('#formparties-'+i+'-'+value).addClass('is-invalid');
                        $('.field-formparties-'+i+'-'+value).children('.invalid-feedback').text('不能為空白。');
                        $('#formparties-'+i+'-'+value).focus();
                        errorCount += 1;
                    }
                    else {
                        $('#formparties-'+i+'-'+value).removeClass('is-invalid');
                        $('.field-formparties-'+i+'-'+value).children('.invalid-feedback').text('');
                    }
                });
                minPartyInput.forEach(function(value){
                    if($('#formparties-'+i+'-'+value).val() <= 0) {
                        $('#formparties-'+i+'-'+value).addClass('is-invalid');
                        $('.field-formparties-'+i+'-'+value).children('.invalid-feedback').text('最少為1。');
                        $('#formparties-'+i+'-'+value).focus();
                        errorCount += 1;
                    }
                    else {
                        $('#formparties-'+i+'-'+value).removeClass('is-invalid');
                        $('.field-formparties-'+i+'-'+value).children('.invalid-feedback').text('');
                    }
                });
            }
        }
        // 不分組
        else {
            partyInput.forEach(function(value){
                if($('#formparties-def-'+value).val() == '') {
                    $('#formparties-def-'+value).addClass('is-invalid');
                    $('.field-formparties-def-'+value).children('.invalid-feedback').text('不能為空白。');
                    $('#formparties-def-'+value).focus();
                    errorCount += 1;
                }
                else {
                    $('#formparties-def-'+value).removeClass('is-invalid');
                    $('.field-formparties-def-'+value).children('.invalid-feedback').text('');
                }
            });
            minPartyInput.forEach(function(value){
                if($('#formparties-def-'+value).val() <= 0) {
                    $('#formparties-def-'+value).addClass('is-invalid');
                    $('.field-formparties-def-'+value).children('.invalid-feedback').text('最少為1。');
                    $('#formparties-def-'+value).focus();
                    errorCount += 1;
                }
                else {
                    $('#formparties-def-'+value).removeClass('is-invalid');
                    $('.field-formparties-def-'+value).children('.invalid-feedback').text('');
                }
            });
        }

        return errorCount > 0 ? false : true;
    })
    $('.modal').appendTo('body');
JS
);

// 設定驗證scenario為create
$voteInfo->setScenario('create');

echo \app\widgets\Summernote::widget(Yii::$app->params['summernote']);
echo \app\widgets\Alert::widget();

$form = ActiveForm::begin([
    'enableClientValidation' => true,
    'fieldConfig' => [
        'options' => ['class' => 'mb-0'],
        'labelOptions' => ['class' => 'mb-0 mt-2'],
    ],
]);
?>
<h4 id="bs">基本設定</h4>
<div class="row">
    <div class="col-lg">
        <?= $form->field($voteInfo, 'Name')->textInput(['required' => true]) ?>
    </div>
    <div class="col-lg">
        <?= $form->field($voteInfo, 'NameE')->textInput() ?>
    </div>
</div>
<div class="row">
    <div class="col-lg">
        <?= $form->field($voteInfo, 'hosted')->textInput(['required' => true]) ?>
    </div>
    <div class="col-lg">
        <?= $form->field($voteInfo, 'hostedE')->textInput() ?>
    </div>
</div>
<div class="row">
    <div class="col-md">
        <?= $form
            ->field($voteInfo, 'openStart')
            ->textInput(['value' => $dateFormat($voteInfo->openStart), 'type' => 'datetime-local', 'required' => true])
            ->hint(sprintf('* 必須在 <b>%s</b> 之前', $voteInfo->getAttributeLabel('openEnd'))) ?>
    </div>
    <div class="col-md">
        <?= $form
            ->field($voteInfo, 'openEnd')
            ->textInput(['value' => $dateFormat($voteInfo->openEnd), 'type' => 'datetime-local', 'required' => true])
            ->hint(
                sprintf(
                    '* 必須介於 <b>%s</b> 至 <b>%s</b> 之間',
                    $voteInfo->getAttributeLabel('openStart'),
                    $voteInfo->getAttributeLabel('verifyStart')
                )
            ) ?>
    </div>
</div>
<div class="row">
    <div class="col-md">
        <?= $form
            ->field($voteInfo, 'verifyStart')
            ->textInput(['value' => $dateFormat($voteInfo->verifyStart), 'type' => 'datetime-local', 'required' => true])
            ->hint(
                sprintf(
                    '* 必須介於 <b>%s</b> 至 <b>%s</b> 之間',
                    $voteInfo->getAttributeLabel('openEnd'),
                    $voteInfo->getAttributeLabel('verifyEnd')
                )
            ) ?>
    </div>
    <div class="col-md">
        <?= $form
            ->field($voteInfo, 'verifyEnd')
            ->textInput(['value' => $dateFormat($voteInfo->verifyEnd), 'type' => 'datetime-local', 'required' => true])
            ->hint(sprintf('* 必須在 <b>%s</b> 之後', $voteInfo->getAttributeLabel('verifyStart'))) ?>
    </div>
</div>
<div class="row">
    <div class="col-lg-3">
        <?= $form
            ->field($voteInfo, 'type')
            ->dropDownList(
                Yii::$app->user->can('sa') ? Yii::$app->params['ct.voteType'] : array_slice(Yii::$app->params['ct.voteType'], 1, null, true), ['class' => 'form-select']
            )
            ->hint(
                ($action == 'create') ?
                    '<span class="p-1 mb-2 bg-warning text-dark">* 建立後該欄位將無法修改！</span>' :
                    '* 如須修改請重新建立！'
            ) ?>
    </div>
    <div class="col-lg-3">
        <?= $form
            ->field($voteInfo, 'partyOrNot')
            ->dropDownList(Yii::$app->params['ct.yesOrNoAry'], ['class' => 'form-select'])
            ->hint(
                ($action == 'create') ?
                    '<span class="p-1 mb-2 bg-warning text-dark">* 建立後該欄位將無法修改！</span>' :
                    '* 如須修改請重新建立！'
            ) ?>
    </div>
    <div class="col-lg-3">
        <?= $form
            ->field($voteInfo, 'addiCondition')
            ->dropDownList(Yii::$app->params['ct.addiCondAry'], ['class' => 'form-select'])
            ->hint(
                ($action == 'create') ?
                    '<span class="p-1 mb-2 bg-warning text-dark">* 建立後該欄位將無法修改！</span>' :
                    '* 如須修改請重新建立！'
            ) ?>
    </div>
    <div class="col-lg-3">
        <?= $form
            ->field($voteInfo, 'isByParty')
            ->dropDownList(Yii::$app->params['ct.votes.isByPartyAry'], ['class' => 'form-select'])
            ->hint(
                ($action == 'edit') ?
                    (($voteInfo->partyOrNot != 1) ? '* 僅限分組投票使用' : '') :
                    ''
            ) ?>
    </div>
</div>
<div class="row">
    <div class="col-lg-3">
        <?= $form
            ->field($voteInfo, 'isBindVote')
            ->dropDownList(Yii::$app->params['ct.yesOrNoAry'], ['class' => 'form-select'])
            ->hint(
                ($action == 'edit') ?
                    (($voteInfo->type != Votes::TYPE_ANON) ? '* 僅限匿名投票使用' : '') :
                    ''
            );
            // 共用密碼選'是'時跳出提醒
            Modal::begin([
                'id' => 'bind-vote-alert',
                'title' => '共用密碼注意事項',
                'dialogOptions' => ['class' => 'modal-lg']
            ]);
            echo Html::ol(
                [
                    '若此投票有分組，分組數量及順序必須與共用密碼的投票相同。',
                    '分組投票無法共用不分組投票的密碼'
                ]
            );
            Modal::end();
        ?>
    </div>
    <div class="col-lg-9">
        <?= $form
            ->field($voteInfo, 'bindWhichVote')
            ->dropDownList(['' => '無']+$canBindVoteAry, ['class' => 'form-select'])
            ->hint(
                ($action == 'edit') ?
                    (($voteInfo->type != Votes::TYPE_ANON) ? '* 僅限匿名投票使用' : '') :
                    ''
            ) ?>
    </div>
</div>
<div class="row">
    <?php if (Yii::$app->user->can('sa') || Yii::$app->user->can('va')): ?>
    <div class="col-md-3">
        <?= $form->field($voteInfo, 'groupId')->dropDownList($groups, ['prompt' => ''], ['class' => 'form-select'])
        ->hint(
            '* 加入群組後群組成員也可以管理此投票'
        ) ?>
    </div>
    <?php else: ?>
    <?=$form->field($voteInfo, 'groupId')->label(false)->hiddenInput(); ?>
    <?php endif; ?>
    
    <div class="col-md-3">
        <?= $form->field($voteInfo, 'creator')->label('投票管理者')->dropDownList($VoteCreatorAry, ['class' => 'form-select']) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($voteInfo, 'session')->textInput()
            ->hint(
                '* 未輸入則不啟用'
            ) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($voteInfo, 'shortUrl', [
                'addon' => [
                    'append' => [
                        'content' => 
                        (Yii::$app->user->can('sa') ? Html::button(FAS::icon('redo'), ['class' => 'btn btn-outline-secondary btn-sm', 'id' => 'renew-short-url']) : '')." ".
                        Html::button(FAS::icon('copy'), ['class' => 'btn btn-outline-secondary btn-sm', 'id' => 'copy-short-url']),
                        'asButton' => true
                    ]
                ],
            ])
            ->textInput(['readonly' => !Yii::$app->user->can('sa')])
            ->hint(
                '* 點擊右側按鈕複製完整網址'
            ) ?>
    </div>
</div>
<hr class="mb-3">
<h4 id="ci">顯示及流程</h4>
<div class="row">
    <div class="col-md-3">
        <?= $form->field($voteInfo, 'active')->dropDownList(Yii::$app->params['ct.activeAry'], ['class' => 'form-select']) ?>
    </div>
    <div class="col-md-3">
        <?= $form
            ->field($voteInfo, 'finishPage')
            ->dropDownList(Yii::$app->params['ct.finishPage'], ['class' => 'form-select'])
            ->hint(
                ($action == 'edit') ?
                    (($voteInfo->partyOrNot != 1) ? '* 僅限分組投票使用' : '') :
                    ''
            ) ?>
    </div>
    <div class="col-md-3 <?= ($voteInfo->type == Votes::TYPE_NO_AUTH) ? ' d-none' : '' ?>">
        <?= $form->field($voteInfo, 'skipDetail')->dropDownList(Yii::$app->params['ct.yesOrNoAry'], ['class' => 'form-select'])
        ->hint(
            '* 是否點擊首頁連結直接跳到投票登入頁面'
        ) ?>
    </div>
    <div class="col-md-3 vl-authBeforeDetail<?= ($voteInfo->type == Votes::TYPE_NO_AUTH) ? ' d-none' : '' ?>">
        <?= $form->field($voteInfo, 'authBeforeDetail')->dropDownList(Yii::$app->params['ct.yesOrNoAry'], ['class' => 'form-select'])
        ->hint(
            '* 如果略過投票資訊頁可以無視此選項'
        ) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($voteInfo, 'skipCheck')->dropDownList(Yii::$app->params['ct.yesOrNoAry'], ['class' => 'form-select']) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($voteInfo, 'isShow')->dropDownList(Yii::$app->params['ct.yesOrNoAry'], ['class' => 'form-select']) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($voteInfo, 'candiConfig')->dropDownList(Yii::$app->params['ct.candiConfig'], ['class' => 'form-select']) ?>
    </div>
    <?php if (Yii::$app->user->can('sa')): ?>
    <div class="col-md-3">
        <?= $form->field($voteInfo, 'sort')->textInput()
        ->hint(
            '* 僅系統管理員可更改'
        ) ?>
    </div>
    <?php else: ?>
    <?=$form->field($voteInfo, 'sort')->label(false)->hiddenInput(); ?>
    <?php endif; ?>
</div>
<!-- <hr class="mb-3"> -->
<!-- <h4 id="ci">聯絡資訊</h4> -->

<div class="row">
    <div class="col-lg">
        <?= $form->field($voteInfo, 'contact')->hiddenInput()->label(false) ?>
    </div>
    <div class="col-lg">
        <?= $form->field($voteInfo, 'contactE')->hiddenInput()->label(false) ?>
    </div>
    <div class="col-lg">
        <?= $form->field($voteInfo, 'tel')->hiddenInput()->label(false) ?>
    </div>
    <div class="col-lg">
        <?= $form->field($voteInfo, 'email')->hiddenInput()->label(false) ?>
    </div>
</div>

<hr class="mb-3">
<h4 class="mb-1" id="vr">投票規則</h4>

<div class="card mb-3">
    <div class="card-body" id="parties">
        <button class="btn btn-sm btn-primary mb-2 d-none" id="addNewParty"><?= FAS::icon('plus') ?> 新增分組</button>
        <?php
        if ($action == 'edit') {
            foreach ($partyData as $pa) {
                // 設定party驗證scenario為update
                $pa->scenario = 'update';
        ?>
                <div class="row">
                    <?php if ($voteInfo->type == Votes::TYPE_ANON) : ?>
                        <div class="col-lg">
                            <?= $pa->party !== Parties::DEF_PARTY ? $form->field($pa, "[$pa->party]name")->textInput() : "<h5 class='card-title text-primary'>$pa->name</h5>" . $form->field($pa, "[$pa->party]name")->hiddenInput()->label(false) ?>
                        </div>                                                                              
                        <div class="col-lg">
                            <?= $pa->party !== Parties::DEF_PARTY ? $form->field($pa, "[$pa->party]nameE")->textInput() : "<h5 class='card-title text-primary'>$pa->nameE</h5>" . $form->field($pa, "[$pa->party]nameE")->hiddenInput()->label(false) ?>
                        </div>
                    <?php else : ?>
                        <div class="col-lg">
                            <?= "<h5 class='card-title text-primary'>$pa->name</h5>" ?>
                            <?= $form->field($pa, "[$pa->party]name")->hiddenInput()->label(false) ?>
                            <?= $form->field($pa, "[$pa->party]nameE")->hiddenInput()->label(false) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div id="vr-<?= $pa->party ?>">
                    <div class="card-text">
                        <div class="row">
                            <div class="col-lg">
                                <?= $form->field($pa, "[$pa->party]numBallots")->textInput() ?>
                            </div>
                            <div class="col-lg">
                                <?= $form->field($pa, "[$pa->party]leastNumBallots")->textInput() ?>
                            </div>
                            <div class="col-lg">
                                <?= $form->field($pa, "[$pa->party]maxElect")->textInput() ?>
                            </div>
                            <div class="col-lg">
                                <?= $form->field($pa, "[$pa->party]numOfKeep")->textInput() ?>
                            </div>
                            <div class="col-lg<?= ($voteInfo->addiCondition != 'female') ? ' d-none' : '' ?>" id="vr-<?= $pa->party ?>-female">
                                <?= $form->field($pa, "[$pa->party]numFemaleKeep")->textInput(['disabled' => ($voteInfo->addiCondition != 'female')]) ?>
                            </div>
                            <div class="col-lg">
                                <?= $form->field($pa, "[$pa->party]numCounting")->textInput(['placeholder' => '僅當前輪次有效']) ?>
                            </div>
                        </div>
                    </div>
                </div>
        <?php
            }
        }
        ?>
    </div>
    <div class="card-footer py-1 px-1">
        <ol class="mb-0">
            <li>此處設定的<b>最多可投票數</b>、<b>最少應投票數</b>、<b>當選人數</b>、<b>遞補人數</b>、<b>女性保留人數</b>，不影響有效票及廢票的判斷，僅供設定<b>問題</b>時快速帶入。</li>
            <?php if ($action == 'edit'): ?>
            <li>清點人數影響
                <a href=<?=Url::to(['count/index', 'voteID' => $voteInfo->voteID])?> target="_blank">計票</a>的<b>選票數量。</b>
            </li>
            <?php endif; ?>
        </ol>
    </div>
</div>

<hr class="mb-3">
<h4 id="vi">投票說明(選填)</h4>
<div class="row">
    <div class="col-md">
        <?= $form->field($voteInfo, 'notice')->textarea(['class' => 'form-control summernote']); ?>
    </div>
    <div class="col-md">
        <?= $form->field($voteInfo, 'noticeE')->textarea(['class' => 'form-control summernote']); ?>
    </div>
</div>
<div class="row">
    <div class="col-md">
        <?= $form->field($voteInfo, 'information')
            ->label(
                Html::button(
                    $voteInfo->getAttributeLabel('information'), [
                        'class' => 'information-tips btn btn-sm btn-outline-dark mb-1'
                    ]
                ).
                Html::button(
                    '預覽圈選須知', [
                        'class' => 'preview-information btn btn-sm btn-outline-info mb-1 ms-2',
                        'data' => ['bs-toggle' => 'modal', 'bs-target' => '#preview-information-modal'],
                    ]
                )
            )
            ->textarea(['class' => 'form-control summernote']); ?>
    </div>
    <div class="col-md">
        <?= $form->field($voteInfo, 'informationE')
            ->label(
                Html::button(
                    $voteInfo->getAttributeLabel('informationE'), [
                        'class' => 'information-tips btn btn-sm btn-outline-dark mb-1'
                    ]).
                Html::button(
                    '預覽圈選須知(英)', [
                        'class' => 'preview-information btn btn-sm btn-outline-info mb-1 ms-2',
                        'data' => ['bs-toggle' => 'modal', 'bs-target' => '#preview-information-modal']
                    ]
                )
            )
            ->textarea(['class' => 'form-control summernote']); ?>
    </div>
</div>
<div class="row">
    <div class="col-md">
        <?= $form->field($voteInfo, 'candComment')->textarea(['class' => 'form-control summernote']); ?>
    </div>
    <div class="col-md">
        <?= $form->field($voteInfo, 'candCommentE')->textarea(['class' => 'form-control summernote']); ?>
    </div>
</div>
<div class="row">
    <div class="col-md">
        <?= $form->field($voteInfo, 'otherInfoTitle')->textInput(); ?>
        <?= $form->field($voteInfo, 'otherInfo')->textarea(['class' => 'form-control summernote']); ?>
    </div>
    <div class="col-md">
        <?= $form->field($voteInfo, 'otherInfoTitleE')->textInput(); ?>
        <?= $form->field($voteInfo, 'otherInfoE')->textarea(['class' => 'form-control summernote']); ?>
    </div>
</div>

<div class="collapse" id="advanced">
    <hr class="mb-3">
    <h4 id="adv">進階</h4>
    <div class="row">
        <div class="col-lg-3">
            <?= $form
                ->field($voteInfo, 'loginLayout')
                ->dropDownList(Yii::$app->params['ct.loginLayoutAry'], ['class' => 'form-select']) ?>
        </div>
        <div class="col-lg-3">
            <?= $form
                ->field($voteInfo, 'pattern')
                ->dropDownList(Yii::$app->params['ct.patternAry'], ['class' => 'form-select']) ?>
        </div>
        <div class="col-lg-3">
            <?= $form
                ->field($voteInfo, 'themeColor')
                ->textInput() ?>
        </div>
    </div>
</div>

<hr>
<?php
// 預覽圈選須知
echo $this->renderFile('@app/views/partials/_vote_information.php', [
    'id' => 'preview-information-modal', 'voteInfo' => $voteInfo
]);

echo Html::tag(
    'div',
    ($action == 'edit') ? ($form->field($voteInfo, 'voteID')->hiddenInput()->label(false) .
        $form->field($voteInfo, 'round')->hiddenInput()->label(false) .
        Html::submitButton('修改', ['class' => 'btn btn-primary me-2', 'name' => 'action', 'value' => 'save', 'style' => 'width: auto']) .
        Html::submitButton('另存', ['class' => 'btn btn-primary me-2', 'name' => 'action', 'value' => 'saveAs', 'style' => 'width: auto']).
        Html::Button('附件上傳', ['class' => 'btn btn-warning me-2', 'data' => ['bs-toggle' => 'modal', 'bs-target' => '#fileUpload'], 'style' => 'width: auto']).
        Html::Button('進階', ['class' => 'btn btn-secondary', 'aria-expanded' => "false", 'data' => ['bs-toggle' => 'collapse', 'bs-target' => '#advanced'], 'style' => 'width: auto'])
    ) : ($form->field($voteInfo, 'voteID')->hiddenInput()->label(false) .
        Html::submitButton('建立', [
            'class' => 'btn btn-primary',
            'id' => 'form-submit', 'style' => 'width: auto',
            'data' => [
                'confirm' => join("\n", [
                    '建立前請再三確認以下欄位是否設定正確！',
                    '',
                    '【投票類型、是否分組、保留名額】',
                    '',
                    '* 按下確定後，您將無法修改這些設定！',
                ])
            ]
        ])
    ),
    ['class' => 'row justify-content-center m-0']
);

ActiveForm::end();
$voteID = $voteInfo->voteID;
$urlBase = Url::base('https');
$renewShortUrl = Url::to(['/elect/renew-short-url']);
$previewInformationUrl = Url::to(['/vote/preview-information', 'voteID' => $voteID]);

// 初始化變數以避免 create action 時未定義
$informations = '';

if ($action == 'edit') {
    $questionModel = new Questions;
    $informations .= Html::beginTag('div', ['class' => 'row']);
    foreach ($questions as $questionID => $question) {
        $informations .= Html::beginTag('div', ['class' => 'col-3']);
        $informations .= "<h4 class=\'border-bottom border-dark\'>第{$question['round']}輪 - {$question['title']}</h4>";
        foreach ($question as $key => $value) {
            if (in_array($key, ['questionID', 'voteID'])) {
                continue;
            }
            $informations .= $questionModel->getAttributeLabel($key).'：'.Html::tag('font', "{%{$questionID}_{$key}%}<br>", ['color' => 'green']);
        }
        $informations .= Html::endTag('div');
    }
    $informations .= Html::endTag('div');

}

$this->registerJs(<<<JS
    // 預覽圈選須知
    $(".preview-information").on("click", function(e) {
        var information = $(this).parent().next().val();
        $.ajax({
            url: '$previewInformationUrl',
            async: false,
            type: 'POST',
            data: {
                'information': information,
            },
            dataType: "json",
            success: function (response) {
                $('#preview-information-modal .modal-body').html(response.information)
                return true;
            },
            error: function (response) {
                console.log(response.responseText);
            }
        })
    });
    // 圈選須知tips
    $(".information-tips").on("click", function(e) {
        appDialog.infoWide('$informations', '圈選須知參數');
    });
    // 更新短網址
    $('#renew-short-url').click(function(e) {
        appDialog.confirm('確定要更新短網址?', function(result) {
            if (result) {
                $.ajax({
                    url: '$renewShortUrl',
                    async: false,
                    type: 'POST',
                    data: {
                        'voteID': '$voteID',
                    },
                    dataType: "json",
                    success: function (response) {
                        console.log(response);
                        appDialog.info('短網址更新成功');
                        $('#formvotes-shorturl').val(response.shortUrl);
                        return false;
                    },
                    error: function (response) {
                        appDialog.error('短網址更新失敗');
                        console.log(response.responseText);
                    }
                })
            }
        });
    });

    // 複製短網址
    $('#copy-short-url').click(function(e) {
        let url = '$urlBase/'+$('#formvotes-shorturl').val();
        navigator.clipboard.writeText(url);
        appDialog.info('複製成功');
    });
JS
);

