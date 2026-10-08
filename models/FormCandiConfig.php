<?php

namespace app\models;

use Yii;
use yii\helpers\Json;
use yii\bootstrap5\Html;
use kartik\grid\GridView;
use yii\helpers\Inflector;
use app\components\helper\ArrayHelper;
use yii\helpers\HtmlPurifier;
use yii\helpers\StringHelper;
use app\components\Model as ComponentModel;
use rmrevin\yii\fontawesome\FAS;

/**
 * FormCandiConfig represents the model behind the search form of `app\models\CandiConfig`.
 */
class FormCandiConfig extends CandiConfig
{
    /**
     * 預設欄位
     */
    static public $defFieldSort = '[{"id":"num"},{"id":"Name"},{"id":"instName"},{"id":"title"}]';

    /**
     * 從頭新增
     */
    static public $FsBefore = 0;

    /**
     * 從尾新增
     */
    static public $FsAfter = 1;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // search
            [
                ['voteID', 'questionID', 'num', 'Name', 'NameE', 'showFieldSort',
                'otherColNameA', 'otherColNameB', 'otherColNameC', 'otherColNameD',
                'otherColNameAE', 'otherColNameBE', 'otherColNameCE', 'otherColNameDE'],
                'safe',
                'on' => ['search']
            ],
            // create
            [
                ['voteID', 'questionID', 'num', 'columnNum', 'Name', 'NameE', 'showFieldSort',
                'otherColNameA', 'otherColNameB', 'otherColNameC', 'otherColNameD',
                'otherColNameAE', 'otherColNameBE', 'otherColNameCE', 'otherColNameDE'],
                'safe',
                'on' => ['create'],
            ],
            // update
            [
                ['num', 'columnNum', 'Name', 'NameE', 'showFieldSort',
                'otherColNameA', 'otherColNameB', 'otherColNameC', 'otherColNameD',
                'otherColNameAE', 'otherColNameBE', 'otherColNameCE', 'otherColNameDE'],
                'safe',
                'on' => ['update']
            ],
            // create、update
            [
                ['NameUnit'],
                'string', 'max' => 10,
                'on' => ['create', 'update']
            ],
            [
                ['NameUnitE'],
                'string', 'max' => 30,
                'on' => ['create', 'update']
            ],
            [
                ['headerColor'],
                'string', 'max' => 60,
                'on' => ['create', 'update']
            ],
            [
                ['sortDefault'],
                'string', 'max' => 50,
                'on' => ['create', 'update']
            ],
            [
                ['fontSize', 'fontSizeE', 'cellHeight', 'cellHeightE'],
                'string', 'max' => 15,
                'on' => ['create', 'update']
            ],
            [
                [
                    'voteID', 'num', 'Name', 'NameE', 'columnNum', 'useBeforeHeader', 'width', 'showFieldSort'
                ], 'required', 'on' => ['create', 'update']
            ],
            [
                ['voteID', 'Name'], 'string', 'max' => 20, 'on' => ['create', 'update'],
            ],
            [
                ['NameE'], 'string', 'max' => 100, 'on' => ['create', 'update'],
            ],
            [
                ['otherColNameA', 'otherColNameB', 'otherColNameC', 'otherColNameD', 'otherColNameE', 'otherColNameF',
                'beforeHeaderA', 'beforeHeaderB', 'beforeHeaderC'],
                'string', 'max' => 120,
                'on' => ['create', 'update']
            ],
            [
                ['otherColNameAE', 'otherColNameBE', 'otherColNameCE', 'otherColNameDE', 'otherColNameEE', 'otherColNameFE',
                'beforeHeaderAE', 'beforeHeaderBE', 'beforeHeaderCE', 'alignLeft'],
                'string', 'max' => 255,
                'on' => ['create', 'update']
            ],
            [
                ['num', 'useBeforeHeader', 'width', 'sort'],
                'string', 'max' => 1,
                'on' => ['create', 'update']
            ],
            [
                ['columnNum'],
                'integer', 'max' => 5,
                'on' => ['create', 'update']
            ],
            [
                ['showFieldSort', 'sortColumns'],
                'string', 'max' => 1000,
                'on' => ['create', 'update']
            ],
        ];
    }

    /**
     * Creates data provider instance with search query applied
     */
    public function search($voteID, $params)
    {
        $query = CandiConfig::find();

        $query->where(['voteID'=>$voteID]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $query;
        }

        // grid filtering conditions
        $query->andFilterWhere(['like', 'voteID', $this->voteID])
            ->andFilterWhere(['like', 'num', $this->num])
            ->andFilterWhere(['like', 'Name', $this->Name])
            ->andFilterWhere(['like', 'NameE', $this->NameE])
            ->andFilterWhere(['like', 'NameUnit', $this->NameUnit])
            ->andFilterWhere(['like', 'NameUnitE', $this->NameUnitE])
            ->andFilterWhere(['like', 'columnNum', $this->columnNum])
            ->andFilterWhere(['like', 'headerColor', $this->headerColor])
            ->andFilterWhere(['like', 'showFieldSort', $this->showFieldSort])
            ->andFilterWhere(['like', 'otherColNameA', $this->otherColNameA])
            ->andFilterWhere(['like', 'otherColNameB', $this->otherColNameB])
            ->andFilterWhere(['like', 'otherColNameC', $this->otherColNameC])
            ->andFilterWhere(['like', 'otherColNameD', $this->otherColNameD])
            ->andFilterWhere(['like', 'otherColNameAE', $this->otherColNameAE])
            ->andFilterWhere(['like', 'otherColNameBE', $this->otherColNameBE])
            ->andFilterWhere(['like', 'otherColNameCE', $this->otherColNameCE])
            ->andFilterWhere(['like', 'otherColNameDE', $this->otherColNameDE]);
 
        return $query;
    }

    /**
     * Get Config from voteID
     */
    public function getConfigWithVoteID($voteID, $questionID=null)
    {
        $result = CandiConfig::getConfigWithVoteID($voteID, $questionID)->one();
        if(empty($result)) {
            return null;
        }
        $this->setAttributes($result->attributes, false);
        return $this;
    }

    /**
     * Get Config Model
     */
    public function updateConfig($voteID, $postData, $action)
    {
        // 加載 post 資料、驗證
        $this->load($postData);
        $this->alignLeft = !empty($this->alignLeft) ? Json::encode($this->alignLeft) : NULL;
        if(!$this->validate())
        {
            $session = Yii::$app->session;
            foreach($this->errors as $message)
            {
                $session->addFlash('error', $message[0]);
            }
            Logs::add(
                $action == 'create' ? Logs::VOTE_CANDI_CONFIG_CREATE_FAIL : Logs::VOTE_CANDI_CONFIG_EDIT_FAIL, 
                Json::encode(compact('voteID')+$this->errors, 336)
            );
            return false;
        }

        // 更新資料
        // $this->setAttributes($this->attributes, false);
        $oldAttributes = $this->oldAttributes;
        // 從使用表頭變更為不使用
        if ($this->useBeforeHeader == '0') {
            $newSort = $this->removeHeaderFromFieldSort($this->showFieldSort);
            $this->showFieldSort = Json::encode($newSort);
        }

        if(!$this->save() && count($this->errors) > 0)
        {
            $session = Yii::$app->session;
            foreach($this->errors as $message)
            {
                $session->addFlash('error', $message[0]);
            }
            return false;
        }

        //比較差異
        if ($action == 'update') {
            $attributesDiff = ArrayHelper::getAttributesMigration($this->attributes, $oldAttributes);
            // LOG紀錄: 修改儲存差異，另存當作新增
            if (!empty($attributesDiff)) {
                Logs::add(Logs::VOTE_CANDI_CONFIG_EDIT, Json::encode(compact('voteID')+$attributesDiff, 336));
            }
        }
        else {
            Logs::add(Logs::VOTE_CANDI_CONFIG_CREATE, Json::encode(compact('voteID')+$this->attributes, 336));
        }
        return true;
    }

    /**
     * 取得設定的欄位排序
     * 
     * @param array $columnsAry 所有欄位的Ary
     * @param array $addField 由後端強制增加的欄位
     * 
     * @return array GridView::$columns
     */
    public function getFieldSort($columnsAry, $addField=[], $removeField=[], $header=false)
    {
        $newSort = [];
        if(is_null($this->showFieldSort)) {
            $showFieldSort = $this->removeHeaderFromFieldSort(self::$defFieldSort);
        }
        else {
            $showFieldSort = $this->removeHeaderFromFieldSort($this->showFieldSort);
        }
        // 只取顯示的欄位不含表頭
        foreach ($showFieldSort ?? [] as $sort) {
            $newSort[] = $sort['id'];
        }
        $num = is_null($this->num) ? 'A' : $this->num;
        // 文字要置左的欄位
        $alignLeftCols = Json::decode($this->alignLeft);
        $columns = [];
        // 要隱藏的欄位: 為了跟header merge
        $dNoneRows = $this->getDNoneRows();
        foreach($newSort as $showFS)
        {
            if($showFS == 'num')
            {
                if($num == 'A')
                    $columns[$showFS] = $columnsAry['autoId'];
                else if($num == 'C')
                    $columns[$showFS] = $columnsAry['id'];
            }
            else if(ArrayHelper::keyExists($showFS, $columnsAry)) {
                $columns[$showFS] = $columnsAry[$showFS];
            }
            if ($header && !is_null($dNoneRows) && $this->useBeforeHeader && in_array($showFS, $dNoneRows)) {
                Html::addCssClass($columns[$showFS]['headerOptions'], 'd-none');
            }
            // 文字設定置左
            if ($showFS != 'Name' && !is_null($alignLeftCols) && in_array($showFS, $alignLeftCols)) {
                Html::removeCssClass($columns[$showFS]['contentOptions'], 'text-center');
                Html::addCssClass($columns[$showFS]['contentOptions'], 'text-start');
            }
        }
        // 正序從頭新增
        $addFKey = array_keys($addField);
        foreach($addFKey as $afk)
        {
            if($addField[$afk] == self::$FsBefore) {
                array_unshift($columns, $columnsAry[$afk]);
            }
        }
        // 倒序從尾新增
        $addFKey = array_reverse($addFKey);
        foreach($addFKey as $afk)
        {
            if($addField[$afk] == self::$FsAfter) {
                array_push($columns, $columnsAry[$afk]);
            }
        }
        if (!empty($removeField)) {
            foreach ($removeField as $remove) {
                unset($columns[$remove]);
            }
        }
        
        return $columns;
    }

    /**
     * 刪除候選設定
     */
    public function deleteAllCandiConfig($voteID)
    {
        $model = new CandiConfig;
        return $model->deleteAllCandiConfig($voteID);
    }
    
    /**
     * 移除顯示欄位標頭
     * 
     * @param string $origin
     * @return array
     */
    public function removeHeaderFromFieldSort($origin)
    {
        $showFieldSort = json_decode($origin, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $showFieldSort = $this->fixOldData($origin);
        }
        $newSort = [];
        foreach ($showFieldSort as $showFS) {
            if (preg_match('/^header[A-Z]/', $showFS['id']) && !empty($showFS['children'])) {
                foreach ($showFS['children'] as $children) {
                    $newSort[] = ['id' => $children['id']];
                }
            }
            elseif (!preg_match('/^header[A-Z]/', $showFS['id'])) {
                $newSort[] = ['id' => $showFS['id']];
            }
        }
        
        return $newSort;
    }
    
    /**
     * 要隱藏的欄位: 為了跟header merge
     *
     * @return array
     */
    public function getDNoneRows()
    {
        $showFieldSort = json_decode($this->showFieldSort, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $showFieldSort = $this->fixOldData($this->showFieldSort);
        }
        $rows = [];
        foreach ($showFieldSort as $showFS) {
            if (!preg_match('/^header[A-Z]/', $showFS['id'])) {
                $rows[] = $showFS['id'];
            }
        }
        return $rows;
    }
    
    /**
     * 投票欄位表頭(beforeHeader)
     *
     * @param  bool  $vote
     * @param  array $options
     * @param  array $removeField 要刪除的欄位
     * @param  object|bool $sort DataProvider排序
     * @param  bool  $print 選票列印，中英文同時顯示
     * @return array|bool
     */
    public function getBeforeHeader($vote=false, $options=[], $removeField=[], $sort=false, $print=false, $gridViewKey=null)
    {
        $showFieldSort = Json::decode($this->showFieldSort);
        $header = $vote ? ['columns' => [
            [
                'content' => $print ? Html::tag('span', Yii::t('app', '圈選欄', [], 'zh-TW'), ['class' => 'text-ch']).'<br>'.
                                      Html::tag('span', Yii::t('app', '圈選欄', [], 'en-US'), ['class' => 'text-en']) : Yii::t('app', '圈選欄'), 
                'options' => ['rowspan' => 2, 'class' => 'align-middle', 'style' => "font-size: 18px;"]
            ],
        ], 'options' => $options] : ['options' => $options];
        $showHeader = false;
        foreach ($showFieldSort as $showFS) {
            // 排除刪掉的欄位
            if (!empty($removeField)) {
                if (in_array($showFS['id'], $removeField)) {
                    continue;
                }
                foreach ($showFS['children'] ?? [] as $key => $children) {
                    if (in_array($children['id'], $removeField)) {
                        unset($showFS['children'][$key]);
                    }
                }
            }
            // 處理欄位表頭
            if (preg_match('/^header[A-Z]/', $showFS['id']) && !empty($showFS['children'])) {
                $name = 'before'.ucfirst($showFS['id']);
                $nameE = $name.'E';
                if ($print) {
                    $content = Html::tag('span', HtmlPurifier::process($this->$name, Yii::$app->params['HtmlPurifier.config']), ['class' => 'text-ch']).'<br>'.
                               Html::tag('span', HtmlPurifier::process($this->$nameE, Yii::$app->params['HtmlPurifier.config']), ['class' => 'text-en']);
                    if ($this->columnNum > 1) {
                        $contentsCh = explode(',', $this->$name);
                        $contentsEn = explode(',', $this->$nameE);
                        $content = $contentsCh > 1 && (!is_null($gridViewKey) && !empty($contentsCh[$gridViewKey])) 
                            ? Html::tag('span', $contentsCh[$gridViewKey], ['class' => 'text-ch']).'<br>'.
                              Html::tag('span', $contentsEn[$gridViewKey], ['class' => 'text-en']) : $content;
                    }
                }
                else {
                    $content = ComponentModel::i18n($this->$nameE, $this->$name, false);

                    if ($this->columnNum > 1) {
                        $contents = explode(',', $content);
                        $content = $contents > 1 && (!is_null($gridViewKey) && !empty($contents[$gridViewKey])) ? $contents[$gridViewKey] : $content;
                    }
                }
                $header['columns'][] = [
                    'content' => $content, 
                    'options' => ['colspan' => count($showFS['children']), 'class' => 'align-middle', 'style' => "font-size: 18px;".(Yii::$app->language == 'zh-TW' ? '' : ' height: 70px;')]
                ];
                $showHeader = true;
            }
            // 處理欄位
            elseif (!preg_match('/^header[A-Z]/', $showFS['id'])) {
                // 名稱欄位特殊處理
                if ($showFS['id'] == 'Name') {
                    if ($print) {
                        $text = Html::tag('span', HtmlPurifier::process($this->Name, Yii::$app->params['HtmlPurifier.config']), ['class' => 'text-ch']).'<br>'.
                                Html::tag('span', HtmlPurifier::process($this->NameE, Yii::$app->params['HtmlPurifier.config']), ['class' => 'text-en']);
                    }
                    else {
                        $text = ComponentModel::i18n($this->NameE, $this->Name, false);
                    }
                    $content = $sort && array_key_exists('id', $sort->attributes) ? $this->sortLink('id', $sort) : $text;
                }
                // 自定義欄位處理
                elseif (StringHelper::startsWith($showFS['id'], 'otherCol')) {
                    $otherCol = str_replace("otherCol", "otherColName", $showFS['id']);
                    if ($print) {
                        $content = Html::tag('span', HtmlPurifier::process($this->{$otherCol}, Yii::$app->params['HtmlPurifier.config']), ['class' => 'text-ch']).'<br>'.
                                   Html::tag('span', HtmlPurifier::process($this->{$otherCol.'E'}, Yii::$app->params['HtmlPurifier.config']), ['class' => 'text-en']);
                    }
                    else {
                        $content = ComponentModel::i18n($this->{$otherCol.'E'}, $this->{$otherCol}, false);
                    }
                }
                // 其他欄位處理
                else {
                    if ($print) {
                        $content = Html::tag('span', Yii::t('app', Yii::$app->params['ct.candi.fieldName'][$showFS['id']], 'zh-TW'), ['class' => 'text-ch']).'<br>'.
                                   Html::tag('span', Yii::t('app', Yii::$app->params['ct.candi.fieldName'][$showFS['id']], 'en-US'), ['class' => 'text-en']);
                    }
                    else {
                        $content = Yii::t('app', Yii::$app->params['ct.candi.fieldName'][$showFS['id']]);
                    }
                }
                $header['columns'][] = [
                    'content' => ($sort && array_key_exists($showFS['id'], $sort->attributes)) ? $this->sortLink($showFS['id'], $sort) : $content, 
                    'options' => ['rowspan' => 2, 'class' => 'align-middle', 'style' => "font-size: 18px;"]
                ];
            }
        }
        if ($showHeader) {
            return [$header];
        }
        else {
            return false;
        }        
    }
    
    /**
     * 舊資料校正，舊資料格式為逗點分隔
     *
     * @param  string $showFieldSort
     * @return array
     */
    public function fixOldData($showFieldSort)
    {
        $oldShowFieldSort = explode(',', $this->showFieldSort);
        $showFieldSort = [];
        foreach ($oldShowFieldSort as $field) {
            $showFieldSort[] = ['id' => $field];
        }
        return $showFieldSort;
    }
    
    /**
     * 已經在左側顯示欄位的
     *
     * @param  array $showFieldSort
     * @return void
     */
    public function getShowFiled($showFieldSort)
    {
        $showField = [];
        foreach ($showFieldSort ?? [] as $sort) {
            if (preg_match('/^header[A-Z]/', $sort['id'])) {
                foreach ($sort['children'] as $children) {
                    $showField[] = $children['id'];
                }
            } else {
                $showField[] = $sort['id'];
            }
        }
        return $showField;
    }
    
    /**
     * 表格排序
     *
     * @return bool|array
     */
    public function getDataSort()
    {
        // 要排序的欄位
        $columns = ArrayHelper::index(json_decode($this->sortColumns, true), 'id');
        
        if (empty($columns)) {
            return false;
        }

        $attributes = [];
        foreach ($columns as $column) {
            if ($column['id'] == 'orderNum') {
                $attributes += [
                    'orderNum', 
                    'id' => [
                        'asc' => ['orderNum' => SORT_ASC, 'id' => SORT_ASC],
                        'desc' => ['orderNum' => SORT_DESC, 'id' => SORT_DESC],
                        'label' => ComponentModel::i18n($this->NameE, $this->Name)
                    ]
                ];
            }
            else {
                $label = str_replace('otherCol', 'otherColName', $column['id']);
                $attributes[$column['id']] = [
                    'asc' => [new \yii\db\Expression("length({$column['id']}) asc"), new \yii\db\Expression("cast({$column['id']} as unsigned) asc"), $column['id'] => SORT_ASC],
                    'desc' => [new \yii\db\Expression("length({$column['id']}) desc"), new \yii\db\Expression("cast({$column['id']} as unsigned) desc"), $column['id'] => SORT_DESC],
                    'default' => SORT_DESC,
                    'label' => $this->$label
                ];
                $attributes["{$column['id']}E"] = [
                    'asc' => [new \yii\db\Expression("length({$column['id']}E) asc"), new \yii\db\Expression("cast({$column['id']}E as unsigned) asc"), $column['id'].'E' => SORT_ASC],
                    'desc' => [new \yii\db\Expression("length({$column['id']}E) desc"), new \yii\db\Expression("cast({$column['id']}E as unsigned) desc"), $column['id'].'E' => SORT_DESC],
                    'default' => SORT_DESC,
                    'label' => $this->{$label.'E'}
                ];
            }
        }

        // 預設排序的欄位
        $sortDefaultDecoded = json_decode($this->sortDefault, true);
        $default = is_array($sortDefaultDecoded) ? ($sortDefaultDecoded[0] ?? null) : null;
        if (empty($default) || !array_key_exists(key($default), $columns)) {
            $defaultOrder = [];
        }
        elseif (key($default) == 'orderNum') {
            $order = $default['orderNum'];
            $defaultOrder = ['orderNum' => $order, 'id' => $order];
        }
        else {
            $defaultOrder = $default;
        }

        return compact('attributes', 'defaultOrder');
    }
    
    /**
     * 排序單個連結
     *
     * @param  string $attribute
     * @param  object $sort
     * @return string
     */
    public function sortLink($attribute, $sort)
    {
        if (strpos($attribute, 'otherCol') !== FALSE) {
            $attribute = strtolower(Yii::$app->language) == 'zh-tw' ? $attribute : $attribute.'E';
        }

        $sorterIcons = GridView::getDefaultSorterIcons(true);
        $icon = Html::tag('span', FAS::icon('sort'), ['class' => 'kv-sort-icon']);
        $direction = $sort->getAttributeOrder($attribute);
        if ($direction !== null) {
            $icon = Html::tag('span', $sorterIcons[$direction], ['class' => 'kv-sort-icon']);
        }
        $options = [];
        if (isset($sort->attributes[$attribute]['label'])) {
            $label = $sort->attributes[$attribute]['label'];
        } else {
            $label = Inflector::camel2words($attribute);
        }
        Html::addCssClass($options, 'kv-sort-link');
        $options['label'] = $label.$icon;
        $link = $sort->link($attribute, $options);

        return $link;
    }
}
