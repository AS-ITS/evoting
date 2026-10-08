<?php

namespace app\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use rmrevin\yii\fontawesome\FAS;

/**
 * 顯示置頂浮動按鈕
 */
class ScrollToTop extends Widget
{
    public function init()
    {
        parent::init();
    }

    public function run()
    {
        $view = Yii::$app->view;
        $view->registerJs(<<<JS
            $(function(){
                let btnScroller = $('#scroller');
                //var fixmeTop = $('.fixme').offset().top; Fisher
                var fixmeTop = $('.fixme').length ? $('.fixme').offset().top : 0;

                // 初始化 OverlayScrollbars
                var osInstance = OverlayScrollbars(document.body, { });

                osInstance.options('callbacks.onScroll', function() {
                    let scrollTop = osInstance.scroll().position.y;
                    if (scrollTop > 150 && !btnScroller.is(':visible')) {
                        btnScroller.fadeIn();
                    } else if (scrollTop < 150 && btnScroller.is(':visible')) {
                        btnScroller.fadeOut();
                    }

                    // 讓有 fixme 的元素隨著捲軸往下固定
                    if (scrollTop >= fixmeTop) {
                        $('.fixme').css({
                            'position': 'fixed',
                            'top': '100',
                            'right': '0'
                        });
                        $('.fixme-vote').css({
                            'width': '8rem',
                            'right': '0',
                            'margin-top': '2rem',
                            'margin-right': '15px',
                            'font-size': '16px'
                        });
                        $('.fixme-vote').addClass('badge badge-primary text-wrap h5');
                    } else {
                        $('.fixme').css({ position: 'static' });
                        $('.fixme-vote').css({ width: '', 'margin-top': '', 'margin-right': '', 'font-size': '24px'});
                        $('.fixme-vote').removeClass('badge badge-primary text-wrap');
                    }
                });

                btnScroller.on('click', function(e) {
                    e.preventDefault();
                    osInstance.scroll({ y: 0 }, 300); // 使用 OverlayScrollbars 的 scroll 方法來滾動到頂部
                });
            });
JS
        );
        return Html::a(
            Html::tag('span',
                FAS::icon('circle', ['class' => 'text-dark fa-stack-2x']).
                FAS::icon('arrow-up', ['class' => 'text-light fa-stack-1x'])
            , ['class' => 'fa-stack fa-lg']),
            'javascript:void(0)',
            [
                'id' => 'scroller',
                'class' => 'scroll-to-top',
                'style' => 'position: fixed; bottom: 50px; right: 20px; display: none;',
                'encode' => false
            ]
        );
    }
}
