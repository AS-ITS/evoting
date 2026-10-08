<?php
/**
 * @link https://getbootstrap.com/docs/4.5/examples/offcanvas/
 */
use yii\helpers\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\bootstrap5\Breadcrumbs;

/** @var \yii\web\View $this */

$appAsset = \app\assets\AppAsset::register($this);

if(!isset($this->params['navCustomHeaderCss']) || $this->params['navCustomHeaderCss'] === false)
{
    $pt5434 = isset($this->params['navItems'])?'5.4':'3.4';
    $this->registerCss("
        .header-pt {
            padding-top: {$pt5434}rem;
        }
        .header-nav-items-mt {
            margin-top: 3.5rem;
        }
    ");
}

// tooltip
$this->registerJs("$('[data-bs-toggle=\"tooltip\"]').tooltip();", $this::POS_READY);
?>
<header class="header-pt">
    <?php
    //--- 父導航 開始 ---
    $navBackGround = isset($this->params['navBackGround'])?$this->params['navBackGround']:'bg-dark';// 導航顏色

    if(!isset($this->params['navColorSchemes'])) // 預設導航色系
        $this->params['navColorSchemes'] = 'dark';

    switch($this->params['navColorSchemes'])
    {
        case 'light':
            $navColorSchemes = 'navbar-light';// 亮色系
            break;
        case 'dark':
            $navColorSchemes = 'navbar-dark';// 暗色系
            break;
    }

    if(!isset($this->params['navPlacement'])) // 預設導航放置位置
        $this->params['navPlacement'] = 'fixed-top';

    switch($this->params['navPlacement'])
    {
        case 'fixed-top':
            $navPlacement = 'fixed-top';// 上
            break;
        case 'fixed-bottom':
            $navPlacement = 'fixed-bottom';// 下
            break;
        case 'sticky-top': // which isn’t fully supported in every browser
            $navPlacement = 'sticky-top';// 上
            break;
        default:
            $navPlacement = '';// 預設
            break;
    }
    NavBar::begin([
        'brandLabel'=> Yii::t('app',Yii::$app->name),
        'brandUrl'  => Yii::$app->homeUrl,
        'options'   => [
            'class' => "navbar navbar-expand-lg $navColorSchemes $navBackGround $navPlacement",
        ],
        // 'containerOptions' => [
        //     'class' => "collapse navbar-collapse flex-column",
        // ],
    ]);
    echo Nav::widget([
        'items' => $this->params['navFirst'],
        'encodeLabels' => false,
        'options' => ['class' => 'navbar-nav me-auto'],//nav-pills
    ]);
    echo Nav::widget([
        'items' => $this->params['navSecond'],
        'encodeLabels' => false,
        'options' => ['class' => 'navbar-nav my-2 my-md-0'],//nav-pills
    ]);
    NavBar::end();
    //--- 父導航 結束 ---

    //--- 子導航 開始 ---
    if(isset($this->params['navItems']))
    {
        echo Html::tag( 'div',
            Nav::widget([
                'items' => $this->params['navItems'],
                'encodeLabels' => false,
                'options' => ['class' => 'container'],//nav-scroller bg-white shadow-sm
            ]),
            ['class' => ['nav-scroller',$navBackGround,'shadow-sm','header-nav-items-mt','fixed-top']]
        );
    }
    //--- 子導航 結束 ---
    ?>
</header>

<main role="main" class="flex-shrink-0 mt-3">
    <?php
    echo Html::tag( 'div',
        // 導航列
        Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
            'options' => [],
        ]).
        // 主體
        $content,
        ['class' => 'container']
    );
    ?>
</main>

<footer class="text-muted mt-5">
    <div class="container">
        <?php if (isset($this->blocks['footer'])): ?>
            <?=$this->blocks['footer']; ?>
        <?php else: ?>
            <hr>
            <p class="float-end">
            <a href="#"><?=Yii::t('app','回頂部')?></a>
            </p>
            <p><?= \yii\helpers\Html::encode(\app\components\Branding::copyright()) ?></p>
        <?php endif; ?>
    </div>
</footer>
