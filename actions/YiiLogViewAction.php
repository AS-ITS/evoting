<?php
namespace app\actions;

use app\components\helper\ArrayHelper;
use Yii;
use SplFileInfo;

use yii\base\Action;
use yii\data\ArrayDataProvider;
use app\interfaces\HandleSwitchInterface;

/**
 * Yii2 檢視系統日誌 yii-log-view
 */
class YiiLogViewAction extends Action
{
    /**
     * 日誌存放目錄
     * @var string
     */
    const LOG_RELATIVE_PATH = '@app/runtime/logs';

    /**
     * @var string 讀取的日誌目錄
     */
    public $path=self::LOG_RELATIVE_PATH;

    /**
     * @var string 浮動視窗的 id
     */
    public $modalId='details-modal-yii-log-view';

    /**
     * @var string 要渲染的視圖文件。
     * 若未設置，將使用 `id` 的值。
     */
    public $view='@app/views/actions/yii-log-view';

    /**
     * @var array 那些檔名的內容不要經過 HTML 編碼，直接輸出(配合以下套件用於顯示BIG5的LOG)
     * https://github.com/jinliming2/Chrome-Charset
     */
    public $nonHtmlEncodeFilesName=[];

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
     * Yii2 日誌
     *
     * @param mixed $id
     *
     * @return \yii\web\Response
     */
    public function run($id=null, $encode=null)
    {
        $modalId = 'details-modal-yii-log-view';
        $fileList = $this->getFileListFromPath();

        $request = Yii::$app->request;
        if ($request->isAjax && !$request->isPjax)
        {
            // 取得檔案的索引節點
            $fileInode = $this->getFileInode($id, $fileList);
            if(is_null($fileInode))
            {
                echo '無此檔案';
                Yii::$app->end();
            }
            else if($fileInode['isRead'] == false)
            {
                echo '無此讀取檔案';
                Yii::$app->end();
            }
            return $this->controller->renderAjax($this->view.'-modal', [
                'file'  => $fileInode,
                'modalId' => $this->modalId,
                'encode' => $encode,
            ]);
        }

        $filterItem = [];
        $searchModel = ArrayHelper::getSearchModel($filterItem);
        $dataProvider = $this->getDataProvider($fileList, $filterItem, $searchModel);

        return $this->controller->render($this->view, [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'title' => 'Yii2 App 日誌',
            'contactAjaxAction' => $this->getCurrAction(),
            'modalId' => $this->modalId,
            'nonHtmlEncodeFilesName' => $this->nonHtmlEncodeFilesName,
        ]);
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

    /**
     * 存取網址之 ControllerId/ActionId
     *
     * @return string ControllerId/ActionId
     */
    protected function getCurrAction()
    {
        $resolve = Yii::$app->request->resolve();
        return $resolve[0];
    }

    /**
     * 取得檔案的詳細資訊
     *
     * @param string $path 檔案路徑
     *
     * @return (int|bool|string)[] 檔案的詳細資訊
     */
    protected function getFileInfoAry($path)
    {
        $FileInfo = new SplFileInfo($path);

        return [
            'inode' => $FileInfo->getInode(), // https://zh.wikipedia.org/wiki/Inode
            'baseName' => $FileInfo->getBasename(),// 檔名
            'path' => $FileInfo->getRealPath(),// 檔案絕對路徑
            'size' => $FileInfo->getSize(),// 檔案大小
            'perms' => $FileInfo->getPerms(),// 檔案權限
            'accessTime' => date("Y-m-d H:i:s", $FileInfo->getATime()),// 最後查看時間
            'changeTime' => date("Y-m-d H:i:s", $FileInfo->getCTime()),// 最後變更時間
            'modifyTime' => date("Y-m-d H:i:s", $FileInfo->getMTime()),// 最後修改時間
            'isRead' => $FileInfo->isReadable(),// 是否可讀
            'isWrite'=> $FileInfo->isWritable(),// 是否可寫
            'isExec' => $FileInfo->isExecutable(),// 是否可執行
            'isDir'  => $FileInfo->isDir(),// 是否為目錄
            'isFile' => $FileInfo->isFile(),// 是否為檔案
            'isLink' => $FileInfo->isLink(),// 是否為連結
        ];
    }

    /**
     * 取得指定目錄下所有檔案
     *
     * @return array
     */
    protected function getFileListFromPath()
    {
        $pathAry = glob(Yii::getAlias($this->path).'/*');
        $result = [];
        foreach($pathAry as $path)
        {
            $result[] = $this->getFileInfoAry($path);
        }
        return $result;
    }

    /**
     * 取得搜尋過濾後的數據模型
     *
     * @param array $data 要過濾的資料
     * @param array $filterItem 特定字典格式
     * @param null|array $searchModel 欄位對應搜尋值的 Array
     *
     * @return \yii\data\ArrayDataProvider
     */
    protected function getDataProvider($data, $filterItem, $searchModel)
    {
        return new ArrayDataProvider([
            'allModels' => ArrayHelper::getFilteredArray($data, $filterItem, $searchModel),
            'pagination' => Yii::$app->tablePag->getPagination(),
        ]);
    }

    /**
     * 取得檔案的索引節點(Inode)
     *
     * @param int $inode 索引節點
     *
     * @return array|null 返回文件系統對象
     */
    protected function getFileInode($inode, $listAry=null)
    {
        if(is_null($listAry))
        {
            $listAry = $this->getFileListFromPath();
        }
        $result = null;
        foreach($listAry as $item)
        {
            if($item['inode'] == $inode)
            {
                $result = $item;
                break;
            }
        }
        return $result;
    }
}