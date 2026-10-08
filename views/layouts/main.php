<?php

use yii\bootstrap5\Html;
$this->beginContent('@app/useTemplate/main.php');    // HTML 基本樣版/開始
/* body - start */
$this->beginContent('@app/useTheme/main.php');       // 自定義主題/開始


$this->title = $this->title.' - '.Yii::t( 'app', Yii::$app->name);

require('main_params.php'); // 加載所需參數

// 捲軸美化
\app\assets\OverlayScrollbarsAsset::register($this);

// 有二級導航時，加大頂部空間
$pt5434 = isset($this->params['navItems'])?'3.5':'2';
$this->registerCss(<<<EOT
.header-pt {
	padding-top: {$pt5434}rem; /* 主導航欄 */
}
EOT
);

// 整個網站字體變大、自定義部分樣式
$this->registerCssFile( '@web/css/site.css', [
	'depends' => [\app\assets\AppAsset::className()],
]);
// 全局JS
$this->registerJsFile('@web/js/global.js', [
	'depends' => [\app\assets\AppAsset::className()],
]);

// 二級導航換行空間
echo \yii\helpers\Html::tag('div', '　', ['class'=>'d-sm-none mt-3']); // 小於 992px
echo \yii\helpers\Html::tag('div', '　', ['class'=>'d-lg-none mt-1']); // 小於 544px

// 顯示投票資訊
if (!empty($this->params['showVoteInfo'])) {
	$voteInfo = $this->params['voteInfo'];
	echo Html::beginTag('div', ['class'=>'hide-print bg-fixme']);
		echo Html::tag('div', 

			Html::tag('div', 
				Html::tag('h1', 
					Html::tag('span', $this->params['title'], ['class' => 'text-secondary'])
				, ['class' => 'fw-bolder'])
			, ['class' => 'col-3 text-start align-self-start']).

			Html::tag('div', 
				Html::tag('h5', $voteInfo->voteName, ['class' => 'fixme-vote fixme fw-bold'])
			, ['class' => 'col-6 text-center']).

			Html::tag('div', 
				Html::tag('div', $voteInfo->roundBadge.'<br>'.$voteInfo->statusBadge, ['class' => 'text-end h5'])
			, ['class' => 'col-3 d-flex justify-content-end align-self-start fixme']),
		['class' => 'row flex-nowrap justify-content-between align-items-center']);
		echo Html::tag('hr');
	echo Html::endTag('div');
}

// 切換角色
echo $this->render('_switch-role');

// 輸出 VIEW 的 HTML
echo $content;
echo \app\widgets\AppDialog::widget();
echo app\widgets\ScrollToTop::widget();

$this->endContent();    // 自定義主題/結束
/* body - end */
$this->endContent();    // HTML 基本樣版/結束
