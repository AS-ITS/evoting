<?php
namespace app\models;

use Yii;
use app\models\FormBallotsCreator;

class FormMemberCreator extends FormBallotsCreator
{
    /**
     * 欄位及過濾項目
     */
    static public $filterItem = [
        'cn'  => 'filterCn',
        'name' => 'filterName',
    ];

    /**
     * 紀錄查詢結果的 session 欄位名稱
     */
    static public $sessionKey = 'Search.personUser';
}
