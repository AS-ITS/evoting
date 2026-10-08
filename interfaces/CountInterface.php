<?php

namespace app\interfaces;

interface CountInterface
{
    /** 排序-依得票高低排序 */
    const COUNT_BY_BALLOTS = 'N';
    /** 排序-依字首筆劃排序 */
    const COUNT_BY_NAME = 'L';
    /** 排序-依候選名單排序 */
    const COUNT_BY_LIST = 'I';
}
