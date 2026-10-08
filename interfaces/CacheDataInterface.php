<?php
namespace app\interfaces;

/**
 * 暫存項目介面
 */
interface CacheDataInterface
{
    /**
     * FISA API-getCode (600秒)
     * 單位: 每個分區
     * @var string
     */
    const FISA_API_OBJECT = 'fisa-api-object';
    /**
     * FISA API-getCode (28800秒，8小時)
     * 單位: 每個查詢結果
     * @var string
     */
    const ACTION_DATA_TABLE_MODIFIER_SEARCH = 'data-table-modifier-search';
}