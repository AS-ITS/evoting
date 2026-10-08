# 威脅建模（Threat Model）

> **狀態**：初版（2026-09-17），對應當前程式；殘餘風險待選務＋資安簽核  
> **相關**：[SECURITY.md](../../SECURITY.md)、[SETUP.md](../../SETUP.md)、[MASTER_KEY_SETUP.md](../MASTER_KEY_SETUP.md)、[LOG_RETENTION_POLICY.md](../manual/LOG_RETENTION_POLICY.md)

本目錄是應用層威脅建模產出，不是掃描報告。方法：**STRIDE**（應用安全）+ **匿名性專章**（選民身分與選票不可連結）。不做完整 ISO 27005。

## 匿名投票：系統保證什麼

匿名場次（`Votes::TYPE_ANON`）的產品假設是：

**管理者在系統裡不知道「這組亂數密碼是哪一位選民持有的」。**

系統**沒有**選民姓名、帳號、身分證件等欄位對應到密碼。密碼亂數產生，經密碼函／現場發放等**場外程序**交給選民。因此：

| 連結 | 系統內？ | 說明 |
|------|----------|------|
| 人 ↔ 密碼 | **否** | `passwords` 無身分欄位；這是匿名的核心保證 |
| 密碼（`passwords.id`）↔ 選票 | **是** | `ballots.creator` = 密碼主鍵，用來一密一票、標已投、紙本封存 |
| 人 ↔ 選票 | **否**（在系統內） | 由上一列兩個事實推得；場外若記名發放則程序破壞匿名 |

這**不是**密碼學 unlinkability（管理員＋金鑰仍可還原「哪組密碼投了什麼」）。那是作業所需，用來防重複投票與雙軌對帳，**不**等於知道是誰投的。

詳見 [04-anonymity.md](04-anonymity.md)。

## 文件索引

| 文件 | 內容 |
|------|------|
| [01-scope.md](01-scope.md) | 範圍、信任假設、資產、角色、信任邊界 |
| [02-dfd.md](02-dfd.md) | 資料流圖（DFD 1～5） |
| [03-stride.md](03-stride.md) | STRIDE 表：威脅／既有控制／殘餘風險 |
| [04-anonymity.md](04-anonymity.md) | 匿名性專章（人／憑證／選票） |
| [05-code-changes-discussion.md](05-code-changes-discussion.md) | 程式異動討論／已決議（C1 不做、C5 未設定即開啟、C6 已寫測試、代投可查） |
| [06-election-decisions.md](06-election-decisions.md) | 選務 A1–A6（A1/A2/A3 代投可查／A5/A6 已接受；A4 待部署） |

## 評等

| 等級 | 含義 |
|------|------|
| Critical | 選舉可作廢，或系統內可還原「哪一位選民投給誰」 |
| High | 越權跨場次、明文密碼未再驗證即外流 |
| Medium | 需額外條件（小樣本＋場外知識、錯誤部署） |
| Low | 防禦縱深／衛生 |

評等假設部署符合 `SETUP.md` 正式環境檢查表。`SENSITIVE_REAUTH=false`、金鑰只在 `.env`、除錯開啟等，會把多條 High 直接升 Critical。

## 維護

- 認證、選票寫入、密碼儲存、匯出明文、RBAC 變更時，至少重審 DFD 1、DFD 5 與匿名專章。
- 每屆選舉前：核對 [06-election-decisions.md](06-election-decisions.md) 是否仍被選務接受。
- 本目錄**不**取代 penetration test 或 `composer audit`（見 [DEPENDENCY_AUDIT.md](../DEPENDENCY_AUDIT.md)）。
