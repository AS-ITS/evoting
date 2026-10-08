<?php
use yii\helpers\Html;

$title = '流程';
$this->title = $title;
$this->params['secNavType'] = 'vote'; // 啟用共用之 vote 二級導航

echo Html::tag('div', Html::tag('h2', $title), ['class'=>'text-center']);
echo Html::tag('hr');

?>
<div class="row">
    <div class="col-md">
        <ul class="timeline">
            <li class="event" data-date="等待投票">
                <h3>投票資訊開放</h3>
                <p><ol>
                    <li>建立投票資訊<br>(包含投票基本資料、聯絡資訊、投票規則、投票說明)</li>
                    <li>建立問題(包含投票規則)</li>
                    <li>建立候選人(包含配置)</li>
                    <li>建立密碼(非匿名投票請跳過)</li>
                </ol></p>
            </li>
            <li class="event" data-date="開始投票">
                <h3>投票開放</h3>
                <p>選票設定可查看投票情形</p>
            </li>
            <li class="event" data-date="等待驗證">
                <h3>結束投票</h3>
                <p>(驗票前準備)</p>
            </li>
            <li class="event" data-date="開始驗證">
                <h3>投票驗證中</h3>
                <p><ol>
                    <li>查看計票單</li>
                    <li>有效票、無效票確認</li>
                    <li>候選人得票數量確認</li>
                </ol></p>
            </li>
            <li class="event" data-date="開票">
                <h3>開票</h3>
                <p><ol>
                    <li>確認開票結果</li>
                    <li>處理同票數問題</li>
                    <li>決定當選名單</li>
                </ol></p>
            </li>
            <li class="event" data-date="完成投票">
                <h3>投票結果開放</h3>
                <p>確認投票已完成</p>
            </li>
        </ul>
    </div>
</div>
<div class="alert alert-info mt-2" role="alert">
    <strong>在計票及投票結果(前台)可以按下CTRL+ALT+P進入列印模式。</strong>
</div>
<?php

$this->registerCss(<<<CSS
.timeline {
    border-left: 3px solid #17a2b8;
    border-bottom-right-radius: 4px;
    border-top-right-radius: 4px;
    /* background: rgba(114, 124, 245, 0.09); */
    margin: 0 auto 0 26%;
    letter-spacing: 0.2px;
    position: relative;
    line-height: 1.4em;
    font-size: 1.03em;
    padding: 50px;
    list-style: none;
    text-align: left;
    /* max-width: 40%; */
}

@media (max-width: 767px) {
    .timeline {
        margin: 0 auto 0 10%;
        max-width: 85%;
        padding: 25px;
    }
}

.timeline h1 {
    font-weight: 300;
    font-size: 1.4em;
}

.timeline h2,
.timeline h3 {
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 10px;
}

.timeline .event {
    border-bottom: 1px dashed #e8ebf1;
    padding-bottom: 25px;
    margin-bottom: 25px;
    position: relative;
}

@media (max-width: 767px) {
    .timeline .event {
        padding-top: 30px;
    }
}

.timeline .event:last-of-type {
    padding-bottom: 0;
    margin-bottom: 0;
    border: none;
}

.timeline .event:before,
.timeline .event:after {
    position: absolute;
    display: block;
    top: 0;
}

.timeline .event:before {
    left: -207px;
    content: attr(data-date);
    text-align: right;
    font-weight: 100;
    font-size: 0.9em;
    min-width: 120px;
}

@media (max-width: 767px) {
    .timeline .event:before {
        left: 0px;
        text-align: left;
    }
}

.timeline .event:after {
    -webkit-box-shadow: 0 0 0 3px #17a2b8;
    box-shadow: 0 0 0 3px #17a2b8;
    left: -55.8px;
    background: #fff;
    border-radius: 50%;
    height: 9px;
    width: 9px;
    content: "";
    top: 10px;
}

@media (max-width: 767px) {
    .timeline .event:after {
        left: -31.8px;
    }
}

.rtl .timeline {
    border-left: 0;
    text-align: right;
    border-bottom-right-radius: 0;
    border-top-right-radius: 0;
    border-bottom-left-radius: 4px;
    border-top-left-radius: 4px;
    border-right: 3px solid #17a2b8;
}

.rtl .timeline .event::before {
    left: 0;
    right: -170px;
}

.rtl .timeline .event::after {
    left: 0;
    right: -55.8px;
}
CSS
);
