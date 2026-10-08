<?php
namespace app\assets;

use Yii;

/**
 * 用於可重新排序的拖放列表
 * 
 * @see https://github.com/SortableJS/Sortable
 */
class SortableAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@frontend/sortable';

    public $css = [];

    public $js = ['Sortable.js','jquery-sortable.js'];

    public $depends = ['app\assets\AppAsset'];
}
