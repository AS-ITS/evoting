# 匿名性專章

> 上層：[README.md](README.md)　本文件對齊現行程式與選務語意：**管理者不知道密碼是由哪一位投的。**

## 1. 三層身分，不要混

```
[選民自然人]  --場外發放-->  [亂數密碼 passwords]  --投票寫入-->  [ballots]
     ▲                            ▲                         ▲
     │                            │                         │
  系統不存                     系統主體                    系統主體
  （無姓名欄）                 id / 密文 / party           creator = password id
```

| 問題 | 答案 |
|------|------|
| 管理員能否從後台看出「張三投給誰」？ | **不能**。沒有張三這筆資料。 |
| 管理員（解鎖明文後）能否看出「密碼 `X` 投給誰」？ | **能**。這是一密一票與紙本封存所需。 |
| 這算不算破壞匿名？ | **不算**（在本系統的產品定義下）。匿名指人↔票，不是憑證↔票。 |
| DBA 讀庫能否得到人名？ | **不能**（A1）。能得到 password id ↔ ballot。 |
| 代投會不會寫入領票人？ | **不會**。依密碼編號代填，仍是密碼↔票；用 `isAdminAdd` 區分本人／代填。 |

先前若把 `ballots.creator = passwords.id` 說成「匿名被破」，那是把「憑證可連結」誤當成「自然人可連結」。**不建議為了密碼學 unlinkability 拿掉 `creator`**，會弄壞一密一票、本輪已投、雙軌對帳、封存單。

## 2. 資料模型（有／無）

**沒有（匿名依賴這點）：**

- 選民姓名、email、員工編號、身分證件
- 密碼領取人、發放簽收
- 記名投票類型（已移除）

**有（作業，不是身分）：**

| 欄位 | 用途 |
|------|------|
| `passwords.id` | 憑證主鍵 |
| `passwords.passwd` | 密文；登入／匯出才解密 |
| `passwords.sn` | 內部編號（密碼函／列表 `密碼#編號`）。發放場外用此號對領票人；系統內無人名 |
| `passwords.party` | 組別 |
| `passwords.mark` | 批次標記（**禁止填姓名**，A2） |
| `passwords.dtrack` | 雙軌：紙本／線上對帳 |
| `passwords.voted` | legacy 已投旗標（語意見 `PASSWORDS_VOTED_SYNC_EVALUATION.md`） |
| `ballots.creator` | 匿名場次＝上述 id |
| `ballots.isAdminAdd` | 代為輸入（1=後台依編號代填，0=本人）；可篩可匯出 |
| `ballots.ip` / `insTime` | 稽核／異常；見 §4 指紋 |
| `logs` 500／501 | `passwordId`，明文被 sanitizer 擋 |

`Users` 只有管理員，不是選民名冊。選票匯入是兩場合併計票，複製選票時保留來源 `isAdminAdd`，不把匯入當成代投。

## 3. 控制對照（LINDDUN 精簡）

| 面向 | 現況 |
|------|------|
| Linkability（人↔票） | 系統內不成立 |
| Identifiability | 無選民識別欄 |
| Non-repudiation vs 匿名 | 選民本來就不能被系統指認；管理員操作有 log |
| Detectability | 後台看得到「某密碼已投」；代填可與本人投區分 |
| Disclosure of info | 明文匯出需 Reauth；log 不寫明文 |
| Unawareness | 選民用密碼，不建個人檔 |
| Non-compliance | 個資：系統不收選民個資；IP 屬操作資料，保留政策草案 |

## 4. 仍可能「推論」是誰的情況（不是欄位洩漏）

這些**不**表示程式寫了人名，但會削弱匿名觀感。殘餘風險應由選務接受或用程序降：

1. **職務未分離**：同一人兼密碼管理與發放名冊，或把名冊匯入系統／填進 `mark`。這才破 A2。
2. **匯出檔 + 領取名單放一起**：密碼管理拿到場外名冊即能人↔票。程序禁止合併保管。
3. **IP＋時間＋小樣本**：會晤現場 NAT 通常同質；遠端較高。
4. **雙軌**（若使用）：紙本簽收編號＋線上同一密碼，對帳者在場外完成人↔票。
5. **現場肩窺／監視器**：程序／場地。

選務已定：發放另記名但不進系統、兩職分開，故 1–2 用人事控制，不當作系統漏洞。

## 5. 負向保證（C6，已做成測試）

- `passwords`／`ballots`／`logins`／`results` schema 無姓名類欄位。
- `Logs` 500／501 context 無明文密碼（`buildLoginLogContext` + `LogSanitizer`）。
- 未解鎖明文時列表遮罩，不顯示可複製明文。
- 公開結果頁不列出密碼、不列出 creator。
- 匿名場次投票者不讀 `Users.name`。

測試：`tests/unit/AnonymityNegativeTest.php`、`tests/functional/BallotControllerCest.php`。

## 6. 明確不納入「匿名漏洞」的項目

- 管理員解鎖後看到亂數密碼
- 封存單把密碼印在圈選旁
- `ballots.creator` 存在
- 表決場次沒有密碼（本來就不是匿名憑證模型）
- 授權人員依密碼編號代填（A3；系統內仍無人名）
