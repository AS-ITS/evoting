# STRIDE

> 上層：[README.md](README.md)　控制「已有」指當前程式；不實作攻擊步驟。

評等見 [README.md](README.md)。匿名相關細節見 [04-anonymity.md](04-anonymity.md)。程式是否要改見 [05-code-changes-discussion.md](05-code-changes-discussion.md)。

## Spoofing（假冒）

| ID | 威脅 | 既有控制 | 殘餘 | 等級 |
|----|------|----------|------|------|
| S1 | 匿名密碼爆破 | `Logs::getPasswordFailWait`（場次設定錯次／等待）；密碼亂數 | 密碼空間取決於產生規則；未再驗證的短／弱規則 | Medium |
| S2 | 管理員密碼爆破 | `AuthController` 5 次／15 分鎖定 | 快取後端與多實例一致性 | Medium |
| S3 | 重放／同時兩裝置用同一密碼 | `Logins` + `SessionInvalidator`；一密一票 `creator` unique | 登出後同一密碼在未投完前可再登（設計） | Low |
| S4 | 短網址猜測進場次 | `shortUrl` 8 字元；場次仍可能公開 | 表決場次本來就無驗證 | Low |
| S5 | Session 固定／竊取 | Cookie httpOnly、SameSite=Strict、正式 secure；session 非 cookie identity | XSS 仍可能（CSP 寬鬆） | Medium |
| S6 | gm 冒用他組場次 | `voteManagRule` 等綁 `voteID`／群組 `isWrite` | 規則漏場次參數時 fail-closed（空 voteID → false） | Low |

## Tampering（竄改）

| ID | 威脅 | 既有控制 | 殘餘 | 等級 |
|----|------|----------|------|------|
| T1 | 投票中改問題／候選人 | 場次 `active`、RBAC | 進行中管理員仍可能改設定（選務紀律） | Medium |
| T2 | 重複投票 | `ballots` unique (voteID, round, party, creator)；`voted`／本輪 ballots | 共用密碼（`isBindVote`）語意見另案 | Low |
| T3 | 補登／後台代投 | `isAdminAdd` 標記；列表可篩、匯出可查；`active=3` 補登 | 授權代投為合法作業；殘餘為未授權代投或漏標 | Medium |
| T4 | 開票後改 `results.elected` | `voteResult` 權限；logs | **已決議不加 Reauth**（人為判斷當選） | Medium（授權誤用） |
| T5 | 後台改已投選票 | `voteBallot` + 寫 `modifier` | 有權即能改；匿名場次改的是密碼↔票，不是人↔票 | High（完整性） |

## Repudiation（否認）

| ID | 威脅 | 既有控制 | 殘餘 | 等級 |
|----|------|----------|------|------|
| R1 | 否認曾匯出明文／開票 | `Logs` 166／176 等；Sensitive Reauth 成功才匯出 | 日誌保留政策仍為草案 | Medium |
| R2 | Console 危險操作 | 420／421 | 正式覆寫需紀律 | Medium |
| R3 | 匿名選民否認「這組密碼投了」 | 系統本來就不能把票綁到人 | 不構成應用缺陷 | — |

## Information disclosure（揭露）

| ID | 威脅 | 既有控制 | 殘餘 | 等級 |
|----|------|----------|------|------|
| I1 | 系統還原「哪一位選民投給誰」 | 無身分欄；log 不含明文密碼 | 見 04；**在 A1–A2 下不成立** | — |
| I2 | 管理員看見密碼↔選票 | 設計如此；明文需 Reauth | 與匿名保證不衝突 | — |
| I3 | 明文 CSV／密碼函外流 | Sensitive Reauth；TTL 解鎖 | TOTP 可選；檔案落地後是程序 | High |
| I4 | 發放名冊寫進系統／`mark` 填姓名 | 實務：名冊場外、系統只用 `sn` | 兩職合一或名冊匯入才破 A2 | Medium（程序） |
| I5 | IP＋時間指紋 | 有權才看選票列表 | 小樣本＋場外「誰何時到場」可推論（04） | Medium |
| I6 | XSS 讀管理員 session | CSRF；CSP **report-only** 且 `unsafe-inline/eval` | XSS 幾乎不算被 CSP 擋住（C2） | High |
| I7 | filePool 直連／路徑 | 目錄在 docroot 外；下載／看照片入口另有 basename＋目錄限制 | helper 本身可 `../` 出池；HTTP 入口測不到（C3，2026-09-18） | Low |
| I8 | 除錯頁／`.env` | 正式 `YII_DEBUG=false`；gitignore；open-source-prep | 部署錯誤 | High（若發生） |

## Denial of service（阻斷）

| ID | 威脅 | 既有控制 | 殘餘 | 等級 |
|----|------|----------|------|------|
| D1 | 鎖死匿名登入 | 錯次鎖定是場次設定 | 惡意打光合法選民密碼空間 | Medium |
| D2 | 大量匯出／Pjax | 後台需登入 | 無明確 rate limit | Low |
| D3 | filePool 灌滿 | 維運磁碟監控（手冊） | 應用層無配額 | Low |

## Elevation of privilege（提權）

| ID | 威脅 | 既有控制 | 殘餘 | 等級 |
|----|------|----------|------|------|
| E1 | 跨場次管理 | RBAC + vote 範圍 rules | 需持續測 rule | High（若漏） |
| E2 | `SA_DB_TOOLS` | 預設關；正式 ConfigValidator 禁 | 開發誤開到正式 | High（若開） |
| E3 | `SENSITIVE_REAUTH=false` 上正式 | **未設定＝開啟**；須顯式 false 才關 | 正式硬擋顯式 false 可選（C5） | High（若顯式關閉） |
| E4 | 同步 RBAC | sa + Reauth | 可接受 | Low |

## 表決場次（DFD 2）單獨列

無驗證是產品行為。STRIDE 不把「未登入就能投票」當漏洞。殘餘：公開 URL 即可投票 → 場地／防火牆／會議模式（A5）。不要用「拿掉表決類型」當安全修補，除非產品要改。
