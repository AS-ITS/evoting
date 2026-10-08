<?php
namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;

class DataProvider extends \yii\base\BaseObject
{
    public function getBasicDataProvider($query, $pagination = null)
    {
        $request = Yii::$app->request;
        if ($request->isPost) {
            Yii::$app->tablePag->setPagination($request->post('pageSize'));
        }

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => is_null($pagination)?
                Yii::$app->tablePag->getPagination(): $pagination,
        ]);
        return $provider;
    }

    public function getBasicArrayProvider($allModels, $pagination = null)
    {
        $request = Yii::$app->request;
        if ($request->isPost) {
            Yii::$app->tablePag->setPagination($request->post('pageSize'));
        }
        
        $provider = new ArrayDataProvider([
            'allModels' => $allModels,
            'pagination' => is_null($pagination)?
                Yii::$app->tablePag->getPagination(): $pagination,
        ]);
        return $provider;
    }
}
