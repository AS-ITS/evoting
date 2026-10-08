# 資料流圖（DFD）

> 上層：[README.md](README.md)　對應程式以註解標路徑，非逐步攻擊手冊。

圖例：實線＝系統資料；虛線＝場外程序（不寫入身分）。

## DFD 1 — 匿名登入 → 圈選 → 寫入選票

最高優先。匿名保證在此流成立或失敗。

```mermaid
flowchart LR
  subgraph outOfBand [場外]
    Dist[密碼函發放<br/>不寫入領取人]
  end

  subgraph browser [選民瀏覽器]
    P[輸入亂數密碼]
  end

  subgraph app [PHP 應用]
    Auth[SiteController actionPassword / FormAnon]
    Lock[Logs getPasswordFailWait]
    Sess[anon session + Logins]
    Vote[VoteController / FormBallots creatorBallot]
  end

  subgraph db [MariaDB]
    PW[(passwords 密文 + id + party)]
    LG[(logins creator=password id)]
    BL[(ballots.creator = password id)]
    BS[(ballotsSelected)]
    LOG[(logs 500/501 僅 passwordId)]
  end

  Dist -.->|程序| P
  P --> Auth
  Auth --> Lock
  Auth --> PW
  Auth --> Sess
  Auth --> LOG
  Sess --> LG
  Vote --> BL
  Vote --> BS
  Vote --> PW
```

要點：

- 登入成功後 `authData['id']` 進 session。
- `FormBallots::creatorBallot()` 把 `creator` 設成該 id，並可呼叫 `FormPasswords::setVote()`。
- `FormAnon::buildLoginLogContext()` 只記 `voteID`、`passwordId`，不含明文。`LogSanitizer` 再擋 `password`／`passwd` 等鍵。
- **沒有**「選民 Users 表」參與此流。

## DFD 2 — 表決（無驗證）投票

```mermaid
flowchart LR
  B[瀏覽器 POST 圈選] --> V[VoteController]
  V --> BL[(ballots.creator 空)]
  V --> BS[(ballotsSelected)]
```

無選民憑證。完整性／防灌票不在應用身份層（假設 A5）。

## DFD 3 — 管理員設定場次／密碼產生

```mermaid
flowchart TB
  Admin[管理員 user 身份] --> M[Manage / Round / Question / Candi / Passwd]
  M --> RBAC[voteManag 等 rule 綁 voteID]
  M --> V[(votes, round, questions, candi)]
  M --> P[(passwords 亂數密文)]
  M --> F[filePool]
  P -.->|密碼函紙本| Dist[場外發放]
```

`passwords.mark` 是批次標記，**不是**身分欄。若填入姓名，破壞 A2（見 04）。

## DFD 4 — 開票 → 結果編輯

```mermaid
flowchart LR
  C[CountController voteCount] --> R[(results)]
  R --> I[ResultController voteResult]
  I --> E[actionMultiEdit / EditableColumn 改 elected]
  I --> Pub[vote/result 公開結果]
```

`actionMultiEdit` 只檢查 `voteResult`，不走 Sensitive Reauth（**已決議不加**：開票需人為判斷）。

## DFD 5 — 敏感匯出（密碼／選票統計）

```mermaid
flowchart LR
  A[管理員] --> SR[SensitiveReauth 密碼 + 可選 TOTP]
  SR --> CSV[密碼 CSV / 密碼函 docx]
  SR --> ST[選票統計 CSV／封存單]
  CSV --> PW[(passwords 解密)]
  ST --> BL[(ballots + creator 對 passwd)]
```

匯出證明的是「憑證 ↔ 選票」，供紙本封存與雙軌對帳。**仍無人名。** 若匯出檔與場外領取名單放在一起，匿名在場外被破，不是這條 API 多寫了身分欄。
