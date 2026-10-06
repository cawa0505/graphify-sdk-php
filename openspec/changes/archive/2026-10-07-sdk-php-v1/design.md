# Design: graphify-sdk-php v1

## Architecture Overview

```
┌─────────────────────────────────┐
│   PHP Application / CLI         │
│   (Laravel, Symfony, etc.)      │
├─────────────────────────────────┤
│   graphify-sdk-php              │
│                                 │
│  ┌───────────────────────────┐  │
│  │  GraphifyClient (Client)  │  │  ← Query Graphify
│  │  - graphSummary()         │  │
│  │  - queryGraph()           │  │
│  │  - tracePath()            │  │
│  │  - memoryQuery()          │  │
│  │  - 26 public methods      │  │
│  └──────┬────────────────────┘  │
│         │                       │
│  ┌──────▼────────────────────┐  │
│  │  McpTransport (Bridge)     │  │  ← Stdio/JSON-RPC
│  │  - sendRequest()           │  │
│  │  - process lifecycle       │  │
│  └──────┬────────────────────┘  │
│         │                       │
│  ┌──────▼────────────────────┐  │
│  │  DTOs (Data Transfer)      │  │  ← Node, Edge, GraphOutput, etc.
│  └───────────────────────────┘  │
│                                 │
│  ┌───────────────────────────┐  │
│  │  PluginHost (Plugin SDK)  │  │  ← Graphify plugin runner
│  │  - registerTool()         │  │
│  │  - run()                   │  │
│  │  - handleInitialize()      │  │
│  │  - handleToolsCall()       │  │
│  └───────────────────────────┘  │
│                                 │
│  ┌───────────────────────────┐  │
│  │  Exceptions                │  │  ← Typed error hierarchy
│  └───────────────────────────┘  │
└──────────┬──────────────────────┘
           │ Stdio (stdin/stdout)
┌──────────▼──────────────────────┐
│  graphify-mcp (Rust binary)      │
└─────────────────────────────────┘
```

## Directory Structure

```
graphify-sdk-php/
├── composer.json
├── phpunit.xml
├── AGENTS.md
├── README.md
├── README.zh-TW.md
├── .gitignore
├── .github/
│   └── workflows/
│       ├── ci.yml              # CI — PHP 8.2+ lint + test
│       └── release.yml         # Release — tag → GitHub Release → Packagist
├── openspec/
│   ├── config.yaml
│   └── changes/
│       └── sdk-php-v1/
│           ├── proposal.md
│           ├── design.md
│           └── tasks.md
├── src/
│   ├── GraphifyClient.php          # Public API — wraps all tools
│   ├── Bridge/
│   │   └── McpTransport.php        # Stdio/JSON-RPC transport
│   ├── Plugin/
│   │   └── PluginHost.php          # JSON-RPC stdio host (Graphify plugin runner)
│   ├── Dto/
│   │   ├── Node.php                # Core node data
│   │   ├── Edge.php                # Edge between nodes
│   │   ├── NodeId.php              # Node ID value object
│   │   ├── FileType.php            # File type enum
│   │   ├── GraphMetadata.php       # Graph metadata
│   │   ├── GraphOutput.php         # Complete graph (nodes+edges+metadata)
│   │   ├── WorkspaceContext.php    # Workspace context
│   │   ├── GraphSummary.php        # Summary result
│   │   ├── MemoryQueryResult.php   # Memory query result
│   │   ├── ReindexResult.php       # Reindex result
│   │   ├── ReviewFinding.php       # Review finding
│   │   ├── TelemetryBinding.php    # Telemetry binding
│   │   ├── CoverageResult.php      # Coverage result
│   │   └── RelayStatus.php         # Relay status
│   └── Exception/
│       ├── GraphifyException.php         # Base exception
│       ├── TransportException.php        # Transport/IO errors
│       ├── ProtocolException.php         # JSON-RPC protocol errors
│       └── EngineException.php           # Engine-level errors
└── tests/
    ├── GraphifyClientTest.php
    ├── PluginHostTest.php
    └── Dto/
        ├── NodeTest.php
        ├── EdgeTest.php
        ├── GraphOutputTest.php
        └── MiscDtoTest.php
```

## Transport Layer (McpTransport)

### Process Lifecycle

```php
$transport = new McpTransport('/usr/local/bin/graphify-mcp');
$transport->start();    // Spawns process, opens pipes
// ... use ...
$transport->stop();     // Closes pipes, terminates process
```

### JSON-RPC Communication

```php
$result = $transport->sendRequest('graphify_graph_summary', []);
// Returns decoded array: ['jsonrpc' => '2.0', 'id' => 1, 'result' => [...]]
```

### Request/Response Flow

1. Build JSON-RPC 2.0 request with unique ID
2. Write to process stdin (newline-delimited)
3. Read JSON-RPC response from process stdout
4. Parse response, check for errors
5. Return result content array

## Client Layer (GraphifyClient)

### Workspace Key Auto-Detection

```php
$client = new GraphifyClient(
    projectPath: '/path/to/project',  // Optional, auto-derives workspace_key
    binaryPath: null                   // Optional, auto-detect from PATH
);
```

Derivation (mirrors Rust): crc32 hash of canonicalized root path → hex string.
PHP does not have SipHash built-in, so crc32 is used as a stable alternative.

### Tool Method Mapping

Every `graphify-mcp` tool maps to a public method (26 total):

#### Core Graph
| Tool | Method | Returns |
|------|--------|---------|
| `graphify_graph_summary` | `graphSummary()` | `GraphSummary` |
| `graphify_graph_query` | `queryGraph(string $question)` | `GraphOutput` |
| `graphify_graph_query_node` | `queryNode(string $nodeId, ?int $depth)` | `GraphOutput` |
| `graphify_graph_trace_path` | `tracePath(string $from, string $to)` | `array` (path node IDs) |
| `graphify_graph_reindex` | `reindexFile(string $filePath)` | `ReindexResult` |

#### Memory
| Tool | Method | Returns |
|------|--------|---------|
| `graphify_memory_query` | `memoryQuery(string $query, ?int $limit)` | `MemoryQueryResult` |

#### Relay/Handoff
| Tool | Method | Returns |
|------|--------|---------|
| `graphify_relay_init` | `relayInit(string $projectContext, ?string $kind)` | `array` |
| `graphify_relay_save` | `relaySave(array $params)` | `array` |
| `graphify_relay_close` | `relayClose(string $repo, string $next)` | `array` |
| `graphify_relay_switch` | `relaySwitch(string $repo, ?string $kind)` | `array` |
| `graphify_relay_resume` | `relayResume(string $repo, ?string $kind)` | `array` |
| `graphify_relay_status` | `relayStatus()` | `RelayStatus` |
| `graphify_relay_add` | `relayAdd(string $file, string $repo)` | `array` |

#### OpenDoc
| Tool | Method | Returns |
|------|--------|---------|
| `graphify_opendoc_index` | `opendocIndex(?array $docPaths)` | `array` |
| `graphify_opendoc_get_context` | `opendocGetContext(string $symbol)` | `array` |
| `graphify_opendoc_audit_drift` | `opendocAuditDrift()` | `array` |

#### Review
| Tool | Method | Returns |
|------|--------|---------|
| `graphify_review_ingest` | `reviewIngest(string $payload)` | `array` |
| `graphify_review_get_context` | `reviewGetContext(string $node)` | `array` |
| `graphify_review_resolve` | `reviewResolve(string $reviewId, string $reason)` | `array` |
| `graphify_review_search_crg` | `reviewSearchCrg(?string $base)` | `array` |

#### Telemetry
| Tool | Method | Returns |
|------|--------|---------|
| `graphify_telemetry_ingest` | `telemetryIngest(string $source, ?string $pathOrParams)` | `array` |
| `graphify_telemetry_get_context` | `telemetryGetContext(string $node, ?bool $includeImpactRadius)` | `array` |

#### Coverage
| Tool | Method | Returns |
|------|--------|---------|
| `graphify_coverage_ingest` | `coverageIngest(string $format, string $data)` | `array` |
| `graphify_coverage_get_context` | `coverageGetContext(string $node)` | `CoverageResult` |
| `graphify_coverage_blindspots` | `coverageBlindspots()` | `array` |

#### Plugin
| Tool | Method | Returns |
|------|--------|---------|
| `graphify_plugin_notify` | `pluginNotify(string $kind)` | `array` |

## Plugin SDK Layer (PluginHost)

### Role

`PluginHost` is the **inbound** side of the SDK — it lets PHP scripts run as
Graphify plugins via IPC subprocess. Graphify Core spawns the PHP process,
communicates via JSON-RPC over stdin/stdout, and the PluginHost dispatches
tool calls to registered PHP handlers.

### Protocol

Implements MCP JSON-RPC subset:

| Method | Purpose |
|--------|---------|
| `initialize` | Returns protocol version, capabilities, and tool list |
| `tools/list` | Returns registered tools with schemas |
| `tools/call` | Dispatches to registered handler, returns result |
| `notifications/*` | Silently accepted (no response) |

### Plugin Host Lifecycle

```
Graphify Core (Rust)               PHP Plugin Process
       │                                  │
       │  proc_open("php analyzer.php")   │
       │─────────────────────────────────>│
       │                                  │
       │  {"method":"initialize",...}     │
       │─────────────────────────────────>│
       │  {"tools":["analyze_schema",...]} │
       │<─────────────────────────────────│
       │                                  │
       │  {"method":"tools/call",...}     │
       │─────────────────────────────────>│
       │  {"result":{...}}                │
       │<─────────────────────────────────│
       │                                  │
       │  close stdin (EOF)               │
       │─────────────────────────────────>│
       │  exit(0)                         │
       │<─────────────────────────────────│
```

### Usage

```php
<?php
// analyzer.php — Graphify Plugin written in PHP
use Graphify\Sdk\Plugin\PluginHost;

$host = new PluginHost();

$host->registerTool('analyze_schema', [
    'description' => 'Analyze Laravel database schema',
    'inputSchema' => [
        'type'       => 'object',
        'properties' => [
            'migration_path' => ['type' => 'string'],
        ],
        'required' => ['migration_path'],
    ],
], function (array $args): array {
    // Your PHP logic here — no Rust, no WASM
    return analyzeMigrations($args['migration_path']);
});

$host->run(); // Blocks, reads stdin, writes stdout
```

### Implementation Notes

- `registerTool()` is chainable (returns `$this`)
- Handler exceptions propagate as JSON-RPC error responses (code -32603)
- Notifications (requests without `id`) are silently discarded
- EOF on stdin triggers clean exit(0)
- Schema defaults: empty `inputSchema` if omitted

## DTO Design

### Node

```php
class Node {
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $fileType,  // FileType enum value
        public readonly string $kind,
        public readonly string $language,
        public readonly string $sourceFile,
        public readonly int $startLine,
        public readonly int $endLine,
        public readonly ?string $docComment = null,
        public readonly ?string $description = null,
        public readonly ?array $metadata = null,
    ) {}

    public static function fromArray(array $data): self;
    public function toArray(): array;
}
```

### Edge

```php
class Edge {
    public function __construct(
        public readonly string $source,
        public readonly string $target,
        public readonly string $relation,
        public readonly string $sourceFile,
        public readonly string $confidence,
        public readonly string $sourceLocation,
        public readonly ?string $description = null,
    ) {}

    public static function fromArray(array $data): self;
    public function toArray(): array;
}
```

### GraphOutput

```php
class GraphOutput {
    public function __construct(
        public readonly array $nodes,      // Node[]
        public readonly array $edges,      // Edge[]
        public readonly GraphMetadata $metadata,
    ) {}

    public static function fromArray(array $data): self;
}
```

### GraphSummary

```php
class GraphSummary {
    public function __construct(
        public readonly int $totalNodes,
        public readonly int $totalEdges,
        public readonly array $languages,   // string[]
    ) {}

    public static function fromArray(array $data): self;
}
```

### CoverageResult

```php
class CoverageResult {
    public readonly float $lineRate;
    public readonly int $coveredLines;
    public readonly int $totalLines;
    public readonly ?array $nodeIds;

    public static function fromArray(array $data): self;
}
```

### RelayStatus

```php
class RelayStatus {
    public readonly string $status;
    public readonly ?string $activeBaton;
    public readonly array $repos;

    public static function fromArray(array $data): self;
}
```

> **Note**: `HandoffPayload` and `HandoffSnapshot` DTOs are intentionally omitted.
> Relay methods return `array` directly since handoff payloads have dynamic structure
> that varies by repo. Static DTOs would add maintenance burden without type safety benefit.

## Exception Hierarchy

```
GraphifyException (base)
├── TransportException    — Process spawn/pipe I/O errors
├── ProtocolException     — JSON-RPC parse/error response
└── EngineException       — graphify-mcp returned error
```

## Composer Configuration

```json
{
    "name": "graphify/sdk-php",
    "description": "Graphify PHP SDK — access Graphify's knowledge graph capabilities via MCP protocol",
    "type": "library",
    "require": {
        "php": ">=8.0",
        "ext-json": "*",
        "ext-mbstring": "*"
    },
    "autoload": {
        "psr-4": {
            "Graphify\\Sdk\\": "src/"
        }
    }
}
```

Zero external dependencies — uses only PHP built-in extensions (json, mbstring).
Stdio transport uses `proc_open()` from PHP core.

## Key Design Decisions

1. **Zero external dependencies**: No Guzzle, no MCP client lib. PHP's built-in
   `proc_open()`, `stream_*()`, and `json_*()` are sufficient for Stdio/JSON-RPC.
   This keeps the SDK installable on any PHP 8.0+ project without dependency conflicts.

2. **Synchronous API**: PHP is primarily synchronous; async adds complexity without
   real benefit for CLI/CLI tools. The underlying MCP protocol is request-response.

3. **Readonly properties**: All DTOs use PHP 8.0+ readonly properties for immutability
   and type safety.

4. **Factory methods**: `fromArray()` static factories on DTOs for JSON deserialization.

5. **Lazy process start**: The transport process is spawned on first request, not in
   constructor, to allow configuration before connection.

6. **crc32 workspace key**: PHP lacks SipHash built-in; crc32 provides a stable
   cross-platform hash as an alternative.

## Test Coverage

### Test Files

| File | Tests | Coverage |
|------|-------|----------|
| `tests/Dto/NodeTest.php` | Node creation, fromArray, toArray, metadata, edge cases | 10+ assertions |
| `tests/Dto/EdgeTest.php` | Edge creation, fromArray, toArray, metadata | 10+ assertions |
| `tests/Dto/GraphOutputTest.php` | GraphOutput with nodes/edges, empty graph, metadata | 10+ assertions |
| `tests/Dto/MiscDtoTest.php` | GraphSummary, NodeId, FileType, ReindexResult, CoverageResult, MemoryQueryResult, RelayStatus, WorkspaceContext, ReviewFinding, TelemetryBinding | 30+ assertions |
| `tests/GraphifyClientTest.php` | Workspace key derivation, transport lifecycle, method mapping | 16 assertions |
| `tests/PluginHostTest.php` | initialize, tools/list, tools/call, error handling, tool registration | 38 assertions |

### Running Tests

```bash
vendor/bin/phpunit
vendor/bin/phpunit tests/PluginHostTest.php  # Plugin-specific
vendor/bin/phpunit tests/Dto/                # DTO-specific
```

### CI Workflow

GitHub Actions CI (`.github/workflows/ci.yml`):

- **Trigger**: push / pull request on main
- **Matrix**: PHP 8.2+ (PHPUnit 11.x requires PHP >= 8.2)
- **Steps**: composer install → php -l lint → phpunit
- **Release** (`.github/workflows/release.yml`): on tag → CI + GitHub Release + Packagist auto-update
