# Graphify PHP SDK

Official PHP SDK for [Graphify](https://github.com/cawa0505/GraphifySDK) — access
knowledge graph capabilities over the MCP (Model Context Protocol) via Stdio/JSON-RPC.

## Requirements

- PHP 8.0+
- `ext-json`
- `ext-mbstring`
- `graphify-mcp` binary on PATH (or configured path)

## Installation

```bash
composer require graphify/sdk-php
```

## Quick Start

```php
use Graphify\Sdk\GraphifyClient;

$client = new GraphifyClient(
    projectPath: '/path/to/your/project',  // auto-derives workspace_key
);

// Graph summary
$summary = $client->graphSummary();
echo "Nodes: {$summary->totalNodes}, Edges: {$summary->totalEdges}\n";

// Semantic memory query
$result = $client->memoryQuery('find user authentication');
if ($result->isFound()) {
    foreach ($result->getNodes() as $node) {
        echo "{$node->label} ({$node->kind}) in {$node->sourceFile}\n";
    }
}

// Trace dependency path
$path = $client->tracePath(
    from: 'src/Models/User.php:class:User',
    to: 'src/Http/Controllers/AuthController.php:function:login',
);

// Query node with depth
$graph = $client->queryNode(
    nodeId: 'src/Services/AuthService.php:class:AuthService',
    depth: 2,
);

// Always clean up
$client->stop();
```

## API Reference

The SDK wraps all 24+ `graphify-mcp` tools. See the full list in
[openspec/changes/sdk-php-v1/design.md](openspec/changes/sdk-php-v1/design.md).

### Core Graph

| Method | Description | Returns |
|--------|-------------|---------|
| `graphSummary()` | Topology metrics | `GraphSummary` |
| `queryGraph(string $question)` | BFS traversal | `GraphOutput` |
| `queryNode(string $nodeId, ?int $depth)` | Node query | `GraphOutput` |
| `tracePath(string $from, string $to)` | Shortest path | `string[]` |
| `reindexFile(string $filePath)` | Reindex file | `ReindexResult` |

### Memory & Relay

| Method | Description | Returns |
|--------|-------------|---------|
| `memoryQuery(string $query, ?int $limit)` | Semantic search | `MemoryQueryResult` |
| `relayInit(string $projectContext, ?string $kind)` | Init handoff | `array` |
| `relaySave(array $params)` | Save state | `array` |
| `relayClose(string $repo, string $next)` | Close handoff | `array` |
| `relaySwitch(string $repo, ?string $kind)` | Switch repo | `array` |
| `relayResume(string $repo, ?string $kind)` | Resume | `array` |
| `relayStatus()` | Status summary | `RelayStatus` |
| `relayAdd(string $file, string $repo)` | Ingest doc | `array` |

### OpenDoc

| Method | Description | Returns |
|--------|-------------|---------|
| `opendocIndex(?array $docPaths)` | Index spec blocks | `array` |
| `opendocGetContext(string $symbol)` | Get symbol docs | `array` |
| `opendocAuditDrift()` | Audit drift | `array` |

### Review

| Method | Description | Returns |
|--------|-------------|---------|
| `reviewIngest(string $payload)` | Import review | `array` |
| `reviewGetContext(string $node)` | Query reviews | `array` |
| `reviewResolve(string $reviewId, string $reason)` | Resolve review | `array` |
| `reviewSearchCrg(?string $base)` | Search CRG | `array` |

### Telemetry & Coverage

| Method | Description | Returns |
|--------|-------------|---------|
| `telemetryIngest(string $source, ?string $path)` | Import metrics | `array` |
| `telemetryGetContext(string $node, ?bool $radius)` | Query telemetry | `array` |
| `coverageIngest(string $format, string $data)` | Import coverage | `array` |
| `coverageGetContext(string $node)` | Query coverage | `CoverageResult` |
| `coverageBlindspots()` | Low-coverage list | `array` |

### Plugin Gateway

| Method | Description | Returns |
|--------|-------------|---------|
| `pluginNotify(string $kind)` | Broadcast update | `array` |

## Architecture

```
PHP App → GraphifyClient → McpTransport (Stdio/JSON-RPC) → graphify-mcp (Rust)
```

- **Zero external dependencies**: uses only PHP built-in extensions
- **Synchronous API**: single-threaded, request-response over stdio
- **Auto workspace key**: derives from project path (mirrors Rust SipHash logic)
- **Lazy process start**: transport spawns `graphify-mcp` on first request

## Project Structure

```
graphify-sdk-php/
├── src/
│   ├── GraphifyClient.php      # Public API — wraps all MCP tools
│   ├── Bridge/
│   │   └── McpTransport.php    # Stdio/JSON-RPC transport
│   ├── Dto/                    # Data transfer objects (15 files)
│   └── Exception/              # Typed exception hierarchy
├── openspec/                   # OpenSpec change management
├── composer.json
├── AGENTS.md
├── README.md
└── README.zh-TW.md
```

## License

MIT
