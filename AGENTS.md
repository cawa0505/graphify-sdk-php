# AGENTS.md — graphify-sdk-php（本機私有，不進 GitHub）

Graphify 官方 PHP SDK：Layer 2 外部 SDK，讓 PHP 生態的開發者
（Laravel, Symfony, 純 PHP）透過 Stdio/JSON-RPC（MCP 協議）
存取 Graphify 的知識圖譜能力。

## 定位與架構

- **屬於 Graphify SDK 家族**：與 `graphify-sdk-python` 平行，
  後續可能有 `-ts` / `-rust` / `-go`。
- **Layer 2 外部 SDK**：不是 plugin，不實作 `GraphifyPlugin` trait；
  透過 Stdio+JSON-RPC 與 `graphify-mcp` 通訊。
- **零外部依賴**：只使用 PHP 內建 extension（json, mbstring, proc_open）。
- **資料契約**：與 graphify-core 共用，payload 透過 Stdio/JSON-RPC 交換。

## 開發與驗證命令

- **安裝**：`composer install`
- **Autoload 驗證**：`composer dump-autoload`
- **語法檢查**：`find src -name '*.php' -exec php -l {} \;`
- **PHPStan（選用）**：`vendor/bin/phpstan analyse src --level=1`

## 開發守則

- **文件優先**：變更前先更新 openspec，實作後更新 README。
- **雙語文檔維護**：英文版 `README.md` 與台灣繁體中文版 `README.zh-TW.md` 永遠保持一致。
- **秘密與敏感資訊防護**：不提交真實金鑰；使用標準環境變數或本地 gitignored 檔案。
- **開源去識別化**：對應 GitHub repo 為公開 repo，
  嚴禁在版本控制檔案中寫入本地網路拓撲、私有主機名、本地 IP、或本機絕對路徑。
- **可抽出性**：本套件未來可無痛獨立為 `graphify-sdk-php` repo。

## SDK 家族對齊

所有 Graphify SDK 必須實現相同的工具方法集（基於 `graphify-mcp` 的所有 tools），
確保跨語言 API 一致性。工具清單與 DTO 定義見 `docs/graphify-rust-api.md`。
