<?php
use yii\helpers\Html;

$this->title = $name;
?>
<div class="site-error">

    <div class="alert alert-danger">
        <?= nl2br(Html::encode($message)) ?>
    </div>

    <p>Web服務器正在處理您的請求時，發生了以上錯誤。</p>
    <p>如果您認為這是服務器錯誤，請與我們聯繫。謝謝。</p>

</div>
