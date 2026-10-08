<?php
use Yii;
use yii\helpers\Html;

/* $this 是 yii\web\View ，所以 View 有自己的 params */
$this->params['navItems'] = [
    [
        'label' => Yii::t('app','功能選單').' 1',
        'url' => '#',
    ],
    [
        'label' => Yii::t('app','功能選單').' 2',
        'url' => '#',
    ],
    [
        'label' => Yii::t('app','功能選單').' 3',
        'url' => '#',
    ],
];

$this->title = Yii::t('app','Yii2 公版');

?>
<div class="jumbotron">
    <h1><?=Yii::t('app','恭喜你!')?></h1>
    <p class="lead"><?=Yii::t('app','您已成功創建基於 Yii 的應用程序。')?></p>
    <p><a class="btn btn-lg btn-success" href="http://www.yiiframework.com"><?=Yii::t('app','開始使用 Yii')?></a></p>
</div>

<div class="body-content">
    <div class="row">
        <div class="col-lg-4">
            <h2><?=Yii::t('app','標題')?></h2>

            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
                dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
                ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
                fugiat nulla pariatur.</p>

            <p><a class="btn btn-primary" href="http://www.yiiframework.com/doc/"><?=Yii::t('app','Yii 文檔')?> &raquo;</a></p>
        </div>
        <div class="col-lg-4">
            <h2><?=Yii::t('app','標題')?></h2>

            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
                dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
            ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
                fugiat nulla pariatur.</p>

            <p><a class="btn btn-primary" href="http://www.yiiframework.com/forum/"><?=Yii::t('app','Yii 論壇')?> &raquo;</a></p>
        </div>
        <div class="col-lg-4">
            <h2><?=Yii::t('app','標題')?></h2>

            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et
                dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip
                ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
                fugiat nulla pariatur.</p>

            <p><a class="btn btn-primary" href="http://www.yiiframework.com/extensions/"><?=Yii::t('app','Yii 擴充')?> &raquo;</a></p>
        </div>
    </div>
</div>
