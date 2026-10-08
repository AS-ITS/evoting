# 貢獻指南

感謝您對電子投票系統的關注。本文件說明如何回報問題與提交程式碼，對齊《公部門開源軟體應用參考手冊》§3.4.2。

## 歡迎的貢獻類型

- 錯誤回報與修正
- 文件（安裝、部署、安全）
- 測試補齊
- 不涉及機敏資料的功能改進

**不接受**：將真實投票資料、密碼、金鑰、個資帶進 Issue／PR。

## 回報問題

| 類型 | 管道 |
|------|------|
| 一般問題、功能建議 | GitHub Issues（請用 Issue 模板） |
| **安全漏洞** | **請勿**在公開 Issue 揭露；見 [SECURITY.md](SECURITY.md) |

提交 Issue 前：

1. 查閱 [SETUP.md](SETUP.md)「常見問題」
2. 檢查 `runtime/logs/`
3. 附上 PHP、MariaDB、`APP_ENV` 與重現步驟

## 開發與測試

1. Fork 並建立 feature branch（勿直接推 `main`）。clone 後執行 `composer install`（含 dev）；post-install 會套用 [`patches/`](patches/)。**不要**使用 `composer create-project`
2. 測試資料庫操作**務必**使用 `--appconfig=config/console-test.php`
3. 至少跑相關單元測試：

```bash
php vendor/bin/codecept run unit
```

完整 unit + functional：`bash tests/run-all.sh`（操作 `voting_test`，非 production）

4. PR 請說明變更目的、測試方式，並關聯 Issue（例如 `Fixes #123`）；使用者可見變更請更新 [CHANGELOG.md](CHANGELOG.md)
5. 對話框使用既有 `kartik-v/yii2-dialog`／Bootstrap 5 Modal，**不要**再引入 Webix

架構見 [CLAUDE.md](CLAUDE.md)；編碼請沿用現有 PHP／Yii2 風格。

## 程式碼審查

所有進入 `main` 的變更須經至少一位維護者審查（見 [GOVERNANCE.md](GOVERNANCE.md)）。審查重點：架構、測試、授權相容、不得引入秘密。

## 行為準則

參與本專案即同意遵守 [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)。
