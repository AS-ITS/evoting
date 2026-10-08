<?php
namespace app\actions;

use Yii;
use yii\base\Action;

use app\interfaces\HandleSwitchInterface;

/**
 * 表格切換頁數
 */
class SwitchPagesAction extends Action
{
    /**
     * @var null|string 繼承處理登入之介面的 namespace
     */
    public $handleEventClass=null;

    /**
     * @var null|object|HandleSwitchInterface 繼承處理登入之介面的 class object
     */
    protected $handleEvent;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if(class_exists($this->handleEventClass))
        {
            $this->handleEvent = new $this->handleEventClass;
            if (!$this->handleEvent instanceof HandleSwitchInterface)
            {
                $this->handleEvent = null;
            }
        }
        else $this->handleEvent = null;
    }

    /**
     * 表格切換頁數
     *
     * @return \yii\web\Response
     */
    public function run()
    {
        $request = Yii::$app->request;
        $tablePag = Yii::$app->tablePag;

        // 設定表格每頁筆數
        $tablePag->setPagination($request->post($tablePag->setPageGetName));

        // 觸發開發者事件
        $this->callEvent('switchPages', [$tablePag->getPagination()]);

        // 當為 Ajax 請求時的處理方式
        if(Yii::$app->request->isAjax)
        {
            Yii::$app->end();
        }
        // 如果有上一頁返回上一頁
        if(!is_null(Yii::$app->request->getReferrer()))
        {
            // 返回原本頁面
            return $this->controller->redirect(Yii::$app->request->getReferrer());
        }
        // 沒上一頁，返回首頁
        return $this->controller->redirect(Yii::$app->getHomeUrl());
    }

    /**
     * 存取事件
     *
     * @param string $fn 存取外部定義的方法
     * @param array $attr 傳入外部定義的方法之參數
     *
     * @return mixed
     */
    protected function callEvent($fn, $attr)
    {
        if(!is_null($this->handleEvent) && method_exists($this->handleEvent, $fn))
        {
            return call_user_func_array([$this->handleEvent, $fn], $attr);
        }
    }
}