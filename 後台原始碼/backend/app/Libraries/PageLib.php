<?php

namespace App\Libraries;

// for non-static version
class PageLib
{
    /**
     * 添加列表用的參數至指定欄位
     * @param array $goalArray 目標要改變的 array
     * @param int $total 表示列表的全部數量
     * @param int $count 表示這次查詢使用的數量
     * @param int $start 表示前是第幾個
     * @return void
     * */
    public static function addFieldofList(array &$goalArray, int $total, int $count, int $start = 0): void
    {
        $goalArray['total'] = $total;
        $goalArray['start'] = $start;
        $goalArray['count'] = $count;
    }

    /**
     * 取得分頁參數
     * @param int $page 分頁數字
     * @param int $pre 單頁顯示數量
     * @return array [開始的 index（0 開始）, 頁數（1 開始）, 單頁數量]
     * */
    public static function getPageArgs(int $page, int $pre): array
    {
        $pre = abs($pre);
        $page = ($page < 1) ? 0 : ($page - 1);
        $start = $page * $pre;
        return [$start, $page, $pre];
    }
}
