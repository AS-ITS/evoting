<?php

namespace app\components;

use Yii;
use yii\bootstrap5\Html;
use app\components\helper\ArrayHelper;
use yii\helpers\HtmlPurifier;

class Model extends \yii\base\Model
{    
    /**
     * 過濾Query Params中的空值
     *
     * @param  array $params
     * @param  string $modelClass
     * @return array
     */
    public static function trimParams($params, $modelClass)
    {
        $modelClass = basename(str_replace('\\', '/', $modelClass));
        if (isset($params[$modelClass]) && is_array($params[$modelClass])) {
            $params[$modelClass] = array_filter($params[$modelClass], function ($value) {
                return ($value !== '');
            });
        }

        return $params;
    }
    
    /**
     * i18n
     *
     * @param  string $en
     * @param  string $zh
     * @param  bool $encode null=直接輸出，true=Html::encode，false=用HtmlPurifier過濾
     * @return string
     */
    public static function i18n($en, $zh, $encode=true)
    {
        $lang = strtolower(Yii::$app->language);
        if ($encode === true) {
            return $lang == 'zh-tw' ? Html::encode($zh, true) : Html::encode($en, true);
        }
        elseif ($encode === false) {
            return $lang == 'zh-tw' ? HtmlPurifier::process($zh, Yii::$app->params['HtmlPurifier.config']) : HtmlPurifier::process($en, Yii::$app->params['HtmlPurifier.config']);
        }
        else {
            return $lang == 'zh-tw' ? $zh : $en;
        }
    }

    /**
     * Creates and populates a set of models.
     *
     * @param string $modelClass
     * @param array $multipleModels
     * @return array
     */
    public static function createMultiple($modelClass, $multipleModels=[], $primary='id', $post=null)
    {
        $model    = new $modelClass;
        if (is_null($post))
        {
            $formName = $model->formName();
            $post     = Yii::$app->request->post($formName);
        }
        $models   = [];

        if (! empty($multipleModels)) {
            $keys = array_keys(ArrayHelper::map($multipleModels, $primary, $primary));
            $multipleModels = array_combine($keys, $multipleModels);
        }

        if ($post && is_array($post)) {
            foreach ($post as $i => $item) {
                if (isset($item[$primary]) && !empty($item[$primary]) && isset($multipleModels[$item[$primary]])) {
                    $models[] = $multipleModels[$item[$primary]];
                } else {
                    $models[] = new $modelClass;
                }
            }
        }

        unset($model, $formName, $post);

        return $models;
    }
}
