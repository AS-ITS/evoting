# 發布指南

本文件說明公開版本的通用發布流程。主機拓撲、憑證管理、離線媒體及組織內部核准程序不屬於公開 repository 文件。

## 前置條件

- 從最新 `main` 建立 release branch。
- 核准版本號與 [CHANGELOG.md](../CHANGELOG.md)。
- 確認工作樹乾淨，且沒有 `.env`、金鑰、正式資料、上傳檔或 runtime 內容。
- 安裝與測試環境符合 [SETUP.md](../SETUP.md)。

## 驗證

```bash
composer validate --strict
composer audit
bash scripts/open-source-prep.sh
bash tests/run-all.sh
```

Acceptance tests 需依 [tests/README.md](../tests/README.md) 啟動測試伺服器，並且只能使用獨立測試資料庫。

若環境有安裝 Gitleaks，另執行：

```bash
gitleaks dir . --config .gitleaks.toml --redact --no-banner
gitleaks git . --config .gitleaks.toml --redact --no-banner
```

## Pull Request

1. 將 release branch 推送至 GitHub。
2. 建立以 `main` 為 base 的 Pull Request。
3. 確認 diff、授權、文件與安全掃描。
4. 等待所有必要 CI checks 通過並完成審查。
5. 依 repository 規則合併，禁止直接推送 `main`。

## 標籤與 GitHub Release

1. 確認本機 `main` 與遠端一致。
2. 建立 annotated tag：

   ```bash
   git tag -a vX.Y.Z -m "Release vX.Y.Z"
   git push origin vX.Y.Z
   ```

3. 以該 tag 建立 GitHub Release。
4. 正式版本不得標記為 Draft 或 Prerelease。
5. Release notes 應摘要使用者可見變更、升級注意事項、已知限制及安全修正。

## 發布後

- 驗證 tag 指向預期的 `main` commit。
- 驗證匿名下載及 `composer install --no-dev --optimize-autoloader`。
- 確認 GitHub Release 與 [CHANGELOG.md](../CHANGELOG.md) 一致。
- 保留測試與發布證據；不得將含秘密或正式資料的證據附加至公開 Release。
