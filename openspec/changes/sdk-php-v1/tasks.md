# Tasks: graphify-sdk-php v1

## Task List

### T1 — Project Scaffolding
- [x] Create directory structure (`src/Bridge/`, `src/Dto/`, `src/Exception/`, `openspec/`)
- [x] Create `openspec/config.yaml`
- [x] Create `openspec/changes/sdk-php-v1/proposal.md`
- [x] Create `openspec/changes/sdk-php-v1/design.md`
- [x] Create `composer.json`
- [x] Create `.gitignore`
- [x] Create `AGENTS.md`
- [x] Create `README.md` (English)
- [x] Create `README.zh-TW.md` (Traditional Chinese)
- [x] Create `phpunit.xml`
- [x] Create `.github/workflows/ci.yml`
- [x] Create `.github/workflows/release.yml`

### T2 — DTO Layer
- [x] Implement `NodeId` — value object
- [x] Implement `FileType` — backed enum
- [x] Implement `Node` — core node DTO with `fromArray()`
- [x] Implement `Edge` — core edge DTO with `fromArray()`
- [x] Implement `GraphMetadata` — graph metadata DTO
- [x] Implement `GraphOutput` — complete graph container
- [x] Implement `GraphSummary` — summary result DTO
- [x] Implement `WorkspaceContext` — workspace context DTO
- [x] Implement `MemoryQueryResult` — memory query result
- [x] Implement `ReindexResult` — reindex result DTO
- [x] Implement `ReviewFinding` — review finding DTO
- [x] Implement `TelemetryBinding` — telemetry binding DTO
- [x] Implement `CoverageResult` — coverage result DTO
- [x] Implement `RelayStatus` — relay status DTO
- [ ] ~~`HandoffPayload` / `HandoffSnapshot`~~ — intentionally omitted (relay methods return `array`)

### T3 — Exception Layer
- [x] Implement `GraphifyException` — base exception
- [x] Implement `TransportException` — transport/IO errors
- [x] Implement `ProtocolException` — JSON-RPC protocol errors
- [x] Implement `EngineException` — engine-level errors

### T4 — Transport Layer
- [x] Implement `McpTransport` — Stdio/JSON-RPC transport
  - [x] Process lifecycle (`start()`, `stop()`, destructor cleanup)
  - [x] `sendRequest(string $method, array $params): array`
  - [x] Request ID generation
  - [x] Error response handling
  - [x] Timeout handling
  - [x] Process auto-detection from PATH

### T5 — Client Layer
- [x] Implement `GraphifyClient` — public API facade
  - [x] Constructor with project path, binary path, auto workspace_key derivation
  - [x] Core Graph methods (5 tools)
  - [x] Memory Query methods (1 tool)
  - [x] Relay/Handoff methods (7 tools)
  - [x] OpenDoc methods (3 tools)
  - [x] Review methods (4 tools)
  - [x] Telemetry methods (2 tools)
  - [x] Coverage methods (3 tools)
  - [x] Plugin Gateway method (1 tool)

### T6 — Plugin SDK Layer
- [x] Implement `PluginHost` — JSON-RPC stdio host
  - [x] `registerTool()` — chainable tool registration
  - [x] `run()` — blocking stdin/stdout listener
  - [x] `handleInitialize()` — protocol version + capabilities + tools
  - [x] `handleToolsList()` — registered tools with schemas
  - [x] `handleToolsCall()` — dispatch to handler
  - [x] Notification support (silent discard)
  - [x] Error handling (exceptions → JSON-RPC error response)

### T7 — Test Coverage
- [x] DTO tests (`NodeTest`, `EdgeTest`, `GraphOutputTest`, `MiscDtoTest`)
- [x] Client tests (`GraphifyClientTest`)
- [x] PluginHost tests (`PluginHostTest`)
- [x] Verify `composer dump-autoload` works
- [x] Basic syntax check with `php -l` on all files
- [x] Verify all tools are wrapped
- [x] Verify DTO field alignment with Rust structs

## Dependencies

```
T1 (scaffolding) → T2 (DTOs) + T3 (exceptions)
T2 + T3 → T4 (transport)
T4 → T5 (client)
T1 → T6 (plugin SDK)  [independent from T2-T5]
T5 + T6 → T7 (testing)
```
