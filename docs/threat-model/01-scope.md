# 範圍、資產與信任邊界

> 上層：[README.md](README.md)

## 1. 在範圍

- 表決（`TYPE_NO_AUTH='0'`）與匿名（`TYPE_ANON='1'`）。記名（`TYPE_VOTER='2'`）已於 2026-01-02 移除。
- 雙身份：`Yii::$app->user`（sa / va / ga / gm）與 `Yii::$app->anon`。
- 場次、輪次、問題、候選人、密碼、選票、開票、結果、filePool。
- 主金鑰派生、密碼加密、Sensitive Reauth／TOTP、Session、CSRF、Cookie、CSP、RBAC rules。
- Console（fixture、加密、migration）— 與 Web 同庫。

## 2. 不在範圍（另列假設）

- 已完全控制主機或 DBA 惡意讀庫（補償：稽核、金鑰與 DB 備份分離；見 MASTER_KEY_SETUP）。
- 社交工程、場地監票、密碼函實體保管（標「程序控制」）。
- 第三方套件 CVE（`composer audit` 追蹤）。
- 密碼學端到端可驗證選舉（Helios 等）— 本系統不是該架構。

## 3. 信任假設（簽核用）

下列假設成立，本威脅模型才主張「匿名投票管理者不知道密碼由哪一位投的」。任一條被選務否定，須重評 04 與 STRIDE I。選務勾選清單見 [06-election-decisions.md](06-election-decisions.md)。

| ID | 假設 | 由誰保證 |
|----|------|----------|
| A1 | 系統不儲存選民真實身分與密碼的對應 | **已接受**；資料模型無姓名欄 |
| A2 | 發放在場外記「內部編號（`sn`）+ 領票人」；**不寫入系統**。密碼管理與發放分職，前者不知領取人 | **已接受**（選務實務） |
| A3 | 密碼管理看得到憑證內容、看不到持有人；**可依密碼編號代填，且代投可查**（`isAdminAdd`） | **已接受**（2026-09-18 改為代投合法） |
| A4 | 正式環境：`YII_DEBUG=false`、HTTPS、`SENSITIVE_REAUTH` 未設定即開、主金鑰不在 repo、`SA_DB_TOOLS` 關 | 部署（**尚未此次拍板**） |
| A5 | 表決灌票／重放由實際會晤處理，與系統無關 | **已接受** |
| A6 | 系統管理與 DBA 分開；DBA 仍無法從 DB 得到人名 | **已接受** |

## 4. 資產（影響序）

1. **選民身分匿名**：系統內無人↔票、無人↔密碼。
2. **一密一票／選票完整性**：`ballots` + `ballotsSelected`、`isValiable`、補登。
3. **開票結果**：`results`、批次改當選、匯出。
4. **投票密碼密文**：`passwords.passwd`、明文匯出／密碼函。
5. **主金鑰與派生子金鑰**。
6. **管理員身份與 RBAC**（跨場次）。
7. **filePool**（附件、候選人照片、密碼函樣板）。
8. **Session**：admin 逾時；anon `logins` 防同時重登。

## 5. 角色

| 角色 | 能力／動機 |
|------|------------|
| 未登入外人 | 猜短網址、爆破密碼、掃場次 |
| 持有一組匿名密碼的選民 | 投兩次、窺探他人票、從結果反推 |
| gm / ga | 越權看他組／他場 |
| va | 建場、匯出明文、改規則 |
| sa | RBAC 同步、資料表工具（預設關） |
| 密碼管理 | 系統內 `sn`／狀態／已投／（解鎖後）明文；**不知領票人**；可依編號代填（`isAdminAdd=1`，可篩可匯出） |
| 發放人員 | 場外名冊：內部編號 + 領票人；不進系統 |
| 惡意內部人＋兩職合一 | 密碼管理 + 發放名冊才能人↔票 |
| 供應鏈 | composer／frontend 鎖定、誤提交 `.env` |

## 6. 信任邊界

```
[選民瀏覽器] --HTTPS--> [web/ 公開入口]
                              |
[管理員瀏覽器] --HTTPS--> [同一 app，Yii::$app->user]
                              |
                         [PHP-FPM] --> [MariaDB]
                              |              ^
                         [@filePool 在 docroot 外]
                              |
                         [master.key 不在 repo]

[密碼函紙本／現場發放] 虛線：場外，系統不記錄領取人
[雙軌 dtrack 紙本]     虛線：對帳用密碼是否已投，仍不含人名
```

文件根必須是 `web/`；`APP_PATH_UPLOADS` 在 docroot 外；正式環境不靠 `.env` 的 `MASTER_KEY`。
