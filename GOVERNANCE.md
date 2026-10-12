# 治理模型

本文件說明目前的維護與決策方式，不構成服務等級或永久維護承諾。

## 維護狀態

- 專案目前持續維護，歡迎外部貢獻，合併權在維護者。
- 安全修補的支援範圍與回報方式見 [SECURITY.md](SECURITY.md)。
- 若日後停止維護，會在 README 與 GitHub repository 狀態明確標示。

## 維護單位

本專案目前由**中央研究院資訊服務處人員**維護。此陳述僅說明目前維護現況，不代表中央研究院提供服務等級、永久維護或特定期限的支援承諾。

## 角色

| 角色 | 職責 |
|------|------|
| 維護者 | 審查／合併 PR、發布版本、安全公告、授權決策 |
| 貢獻者 | 提交 Issue／PR，遵守 CONTRIBUTING 與行為準則 |

## 決策

- **日常**：相關維護者審查後合併
- **破壞性變更**：須維護者共識，記在 CHANGELOG
- **授權**：專案為 MIT。再改授權須維護者共識
- **安全漏洞**：依 [SECURITY.md](SECURITY.md)，不在公開 Issue 討論細節

## 發布

公開版本依 [發布指南](docs/RELEASING.md) 經 PR、CI、版本標籤與 GitHub Release 發布。

## 貢獻入口

見 [CONTRIBUTING.md](CONTRIBUTING.md)。議題追蹤使用 GitHub Issues；程式碼審查使用 Pull Request。
