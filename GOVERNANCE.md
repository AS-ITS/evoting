# 治理模型

對齊《公部門開源軟體應用參考手冊》§3.4（維護等級與社群治理）。

## 維護等級

本專案定位為 **Tier 2：官方長期維護**（手冊 §3.4.1）。

- 由釋出機關（或受託維運團隊）持續開發、安全更新與 Issue 回應
- 歡迎外部貢獻，合併權在維護者
- 若日後降為封存（Tier 0–1），會在 README 標示 Archived，且不再保證安全修補

## 維護單位

本專案由 **中央研究院資訊服務處（Department of Information Technology Services, Academia Sinica）** 正式維護。

受託維運團隊得協助開發、審查及安全修補；最終合併、發布、授權及治理決策權屬正式維護單位。

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

版本發布、無法連外開發機與受控 Windows PC 間的移轉、GitHub 安全設定及 clean-room 驗證，依 [GitHub 發布與離線開發環境作業手冊](docs/GITHUB_RELEASE_RUNBOOK.md) 執行。

## 貢獻入口

見 [CONTRIBUTING.md](CONTRIBUTING.md)。議題追蹤使用 GitHub Issues；程式碼審查使用 Pull Request。
