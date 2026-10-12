# 通用化遷移摘要

本專案原為特定選舉情境的參考實作，於 2026 年整理為可由不同機構部署的通用電子投票平台。

## 主要變更

- 將執行環境統一為 `production`、`testing`、`development`。
- 以 `.env` 與系統環境變數取代環境專屬設定。
- 將主金鑰移至系統環境變數或 repository 外部金鑰檔。
- 移除特定網域、網段、服務端點與固定檔案路徑。
- 允許自訂應用程式、資料庫備份及上傳目錄。
- 移除記名投票，只保留表決投票與匿名投票。
- 新增通用安裝、治理、安全、威脅模型、測試與授權文件。

## 升級原則

既有部署升級前應：

1. 備份資料庫、上傳檔、金鑰與環境設定。
2. 保留原有 `.env`，依 [SETUP.md](SETUP.md) 對照新設定。
3. 依 [docs/migrations/README.md](docs/migrations/README.md) 執行必要增量 migration。
4. 在 staging 驗證登入、投票、開票、匯入匯出及匿名性要求。
5. 驗證完成後才切換正式環境。

新安裝請直接依 [SETUP.md](SETUP.md)，不要套用舊環境的固定路徑或主機判斷。

## 相關文件

- [README.md](README.md)：功能摘要與快速開始
- [SETUP.md](SETUP.md)：完整安裝與升級
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)：系統架構
- [SECURITY.md](SECURITY.md)：安全政策
- [CONTRIBUTING.md](CONTRIBUTING.md)：貢獻流程
- [CHANGELOG.md](CHANGELOG.md)：版本變更
