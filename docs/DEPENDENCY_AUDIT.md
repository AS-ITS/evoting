# 依賴與供應鏈稽核（M7）

> 最後更新：2026-10-07  
> 相關：`composer.json`、`composer.lock`、`SECURITY.md`

## 稽核方式

```bash
composer audit
composer install --no-dev --dry-run
```

部署與 CI 應使用 `composer install --no-dev`，並以 lock file 鎖定版本。

## composer audit 已知忽略項

目前無忽略項。曾列入的 advisory 已由 lock 中版本修補：

- `yiisoft/yii2-gii` 2.2.7：高於 `PKSA-mm6g-r738-y4dn`（修補於 2.2.2）與
  `PKSA-bbgw-mkqz-n76v`（修補於 2.2.5）的受影響範圍。
- `phpunit/phpunit` 9.6.35：高於 `PKSA-z3gr-8qht-p93v` 的 9.x 修補版 9.6.33。

**注意**：ignore 只能用於已知、已評估且短期無替代方案的 advisory，並須記錄
套件、影響、補償控制與移除條件；不可永久忽略。

## @dev 相依

`composer.json` 中 `@dev` 來源（如 kartik 系列）表示追蹤 VCS 分支而非穩定 tag。開源釋出前：

1. 確認 lock file 已提交。
2. 評估是否可改為穩定版 constraint。
3. 在 release note 註明已知 advisory 與升級計畫。

## 建議週期

- **每次 release**：`composer audit` + 更新 lock。
- **每季**：檢視 ignore 清單是否可移除。
- **重大 CVE**：72 小時內評估影響並發 patch release（見 `SECURITY.md`）。
