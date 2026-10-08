<?php
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

$this->registerMetaTag(['http-equiv' => 'Content-Type','content' => 'text/html; charset='.Yii::$app->charset]);
$this->registerMetaTag(['http-equiv' => 'X-UA-Compatible','content' => 'IE=edge']);
$this->registerMetaTag(['name' => 'viewport','content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerCsrfMetaTags();// csrf tag
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => \app\components\Branding::faviconUrl()]);

// 供 global.js 登出前同步 CSRF
$this->registerJs(
    'window.votingCsrfTokenUrl = ' . Json::htmlEncode(Url::to(['/site/csrf-token'])) . ';',
    View::POS_HEAD
);
// 登入剛換發 CSRF 時，廣播給其他分頁更新 meta
if (Yii::$app->session->getFlash('csrfBroadcast')) {
    $this->registerJs(<<<'JS'
(function () {
    if (!window.yii || typeof BroadcastChannel === 'undefined') {
        return;
    }
    var param = yii.getCsrfParam();
    var token = yii.getCsrfToken();
    if (!param || !token) {
        return;
    }
    try {
        var ch = new BroadcastChannel('voting-csrf');
        ch.postMessage({type: 'csrf-refresh', param: param, token: token});
        ch.close();
    } catch (e) {}
})();
JS
    , View::POS_READY);
}

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
    <head>
        <?php
        $this->head();
        echo Html::tag('title',Html::encode($this->title));
        ?>
    </head>
    <body>
        <?php
        $this->beginBody();
        echo $content;
        $this->endBody();
        ?>
    </body>
</html>
<?php $this->endPage() ?>
