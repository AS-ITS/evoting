# Vendor Patches

`composer install` / `composer update` 結束後會執行 `scripts/apply-vendor-patches.php`，自動套用 [`patches/`](patches/) 內的 unified diff。**不要手改 `vendor/`。**

手動重套：

```bash
php scripts/apply-vendor-patches.php
```

已套用則略過；若必要 patch 無法套用，安裝會失敗，避免未修補的 vendor 被誤用。官方修復後刪除對應 `.patch` 並更新本文件。

---

## Yii2 QueryBuilder — PHP 8.x `resetSequence`

| | |
|---|---|
| Patch | [`patches/yiisoft-yii2-QueryBuilder-resetSequence.patch`](patches/yiisoft-yii2-QueryBuilder-resetSequence.patch) |
| 目標 | `vendor/yiisoft/yii2/db/mysql/QueryBuilder.php` |
| 適用 | Yii 2.0.55（`composer.lock`）；空表 `MAX()` 為 `null` 時 `null + 1` 在 PHP 8 會 TypeError |

驗證：`php vendor/bin/codecept run unit`（fixture 清理會走 `resetSequence`）。

相關：[yiisoft/yii2#19059](https://github.com/yiisoft/yii2/issues/19059)

---

## Codeception Gherkin — Behat 4.16 路徑相容

| | |
|---|---|
| Patch | [`patches/codeception-Gherkin-default-keywords.patch`](patches/codeception-Gherkin-default-keywords.patch) |
| 目標 | `vendor/codeception/codeception/src/Codeception/Test/Loader/Gherkin.php` |
| 適用 | Codeception 5.1.2 搭配 Behat Gherkin 4.16.x；改用 `CachedArrayKeywords::withDefaultKeywords()`，避免依賴已變更的 `i18n.php` 相對路徑 |

此修改源自 Codeception 5.3.0 的上游修正；保留 patch 可維持本專案 PHP 8.0／8.1 開發環境相容性。升級至 Codeception 5.3+ 並停止支援 PHP 8.1 後可移除。

相關：[Codeception PR #6839](https://github.com/Codeception/Codeception/pull/6839)
