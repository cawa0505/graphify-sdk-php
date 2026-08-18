# Graphify PHP SDK

Graphify 官方 PHP SDK — 讓 PHP 開發者透過 Stdio/JSON-RPC（MCP 協議）
存取 Graphify 知識圖譜的完整能力。

## 系統需求

- PHP 8.0+
- `ext-json`
- `ext-mbstring`
- `graphify-mcp` 二進位檔需在 PATH 中（或指定路徑）

## 安裝

```bash
composer require graphify/sdk-php
```

## 快速開始

```php
use Graphify\Sdk\GraphifyClient;

$client = new GraphifyClient(
    projectPath: '/path/to/your/project',  // 自動推導 workspace_key
);

// 圖譜摘要
$summary = $client->graphSummary();
echo "節點: {$summary->totalNodes}, 關聯: {$summary->totalEdges}\n";

// 語意記憶查詢
$result = $client->memoryQuery('尋找使用者認證相關程式碼');
if ($result->isFound()) {
    foreach ($result->getNodes() as $node) {
        echo "{$node->label} ({$node->kind}) 在 {$node->sourceFile}\n";
    }
}

// 追蹤依賴路徑
$path = $client->tracePath(
    from: 'src/Models/User.php:class:User',
    to: 'src/Http/Controllers/AuthController.php:function:login',
);

// 查詢節點及其關聯
$graph = $client->queryNode(
    nodeId: 'src/Services/AuthService.php:class:AuthService',
    depth: 2,
);

// 清理資源
$client->stop();
```

## API 參考

SDK 封裝了全部 24+ 個 `graphify-mcp` 工具。完整清單見
[openspec/changes/sdk-php-v1/design.md](openspec/changes/sdk-php-v1/design.md)。

### 核心圖譜

| 方法 | 說明 | 回傳 |
|--------|------|------|
| `graphSummary()` | 圖譜指標 | `GraphSummary` |
| `queryGraph(string $question)` | BFS 遍歷 | `GraphOutput` |
| `queryNode(string $nodeId, ?int $depth)` | 節點查詢 | `GraphOutput` |
| `tracePath(string $from, string $to)` | 最短路徑 | `string[]` |
| `reindexFile(string $filePath)` | 重新索引 | `ReindexResult` |

### 記憶與交接

| 方法 | 說明 | 回傳 |
|--------|------|------|
| `memoryQuery(string $query, ?int $limit)` | 語意搜尋 | `MemoryQueryResult` |
| `relayInit(string $projectContext, ?string $kind)` | 初始化交接 | `array` |
| `relaySave(array $params)` | 儲存狀態 | `array` |
| `relayClose(string $repo, string $next)` | 關閉交接 | `array` |
| `relaySwitch(string $repo, ?string $kind)` | 切換專案 | `array` |
| `relayResume(string $repo, ?string $kind)` | 恢復交接 | `array` |
| `relayStatus()` | 交接狀態 | `RelayStatus` |
| `relayAdd(string $file, string $repo)` | 匯入文件 | `array` |

### OpenDoc（文件索引）

| 方法 | 說明 | 回傳 |
|--------|------|------|
| `opendocIndex(?array $docPaths)` | 索引規格區塊 | `array` |
| `opendocGetContext(string $symbol)` | 查詢符號文件 | `array` |
| `opendocAuditDrift()` | 審核漂移 | `array` |

### 程式碼審查

| 方法 | 說明 | 回傳 |
|--------|------|------|
| `reviewIngest(string $payload)` | 匯入審查 | `array` |
| `reviewGetContext(string $node)` | 查詢審查 | `array` |
| `reviewResolve(string $reviewId, string $reason)` | 標記已解決 | `array` |
| `reviewSearchCrg(?string $base)` | 搜尋 CRG | `array` |

### 遙測與覆蓋率

| 方法 | 說明 | 回傳 |
|--------|------|------|
| `telemetryIngest(string $source, ?string $path)` | 匯入指標 | `array` |
| `telemetryGetContext(string $node, ?bool $radius)` | 查詢遙測 | `array` |
| `coverageIngest(string $format, string $data)` | 匯入覆蓋率 | `array` |
| `coverageGetContext(string $node)` | 查詢覆蓋率 | `CoverageResult` |
| `coverageBlindspots()` | 低覆蓋率清單 | `array` |

### 插件閘道

| 方法 | 說明 | 回傳 |
|--------|------|------|
| `pluginNotify(string $kind)` | 廣播圖譜更新 | `array` |

## 架構

```
PHP 應用 → GraphifyClient → McpTransport (Stdio/JSON-RPC) → graphify-mcp (Rust)
```

- **零外部依賴**：只使用 PHP 內建 extension
- **同步 API**：單執行緒 request-response over stdio
- **自動工作區金鑰**：從專案路徑推導（對應 Rust SipHash 邏輯）
- **延遲啟動**：transport 在首次請求時才啟動 `graphify-mcp` 程序

## 專案結構

```
graphify-sdk-php/
├── src/
│   ├── GraphifyClient.php      # 公開 API — 封裝所有 MCP 工具
│   ├── Bridge/
│   │   └── McpTransport.php    # Stdio/JSON-RPC 傳輸層
│   ├── Dto/                    # 資料傳輸物件（15 個檔案）
│   └── Exception/              # 型別化例外階層
├── openspec/                   # OpenSpec 變更管理
├── composer.json
├── AGENTS.md
├── README.md
└── README.zh-TW.md
```

## 授權

MIT
