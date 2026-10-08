# Vendor Patches

`composer install` / `composer update` 結束後會執行 `scripts/apply-vendor-patches.php`，自動套用 [`patches/`](patches/) 內的 unified diff。**不要手改 `vendor/`。**

手動重套：

```bash
php scripts/apply-vendor-patches.php
```

已套用則略過；upstream 已改掉 hunk 時腳本警告並繼續（安裝不中斷）。官方修復後刪對應 `.patch` 並更新本文件。

---

## Yii2 QueryBuilder — PHP 8.x `resetSequence`

| | |
|---|---|
| Patch | [`patches/yiisoft-yii2-QueryBuilder-resetSequence.patch`](patches/yiisoft-yii2-QueryBuilder-resetSequence.patch) |
| 目標 | `vendor/yiisoft/yii2/db/mysql/QueryBuilder.php` |
| 適用 | Yii 2.0.55（`composer.lock`）；空表 `MAX()` 為 `null` 時 `null + 1` 在 PHP 8 會 TypeError |

驗證：`php vendor/bin/codecept run unit`（fixture 清理會走 `resetSequence`）。

相關：[yiisoft/yii2#19059](https://github.com/yiisoft/yii2/issues/19059)
