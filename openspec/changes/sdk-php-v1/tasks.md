# Tasks: graphify-sdk-php v1

## Task List

### T1 — Project Scaffolding
- [x] Create directory structure (`src/Bridge/`, `src/Dto/`, `src/Exception/`, `openspec/`)
- [x] Create `openspec/config.yaml`
- [x] Create `openspec/changes/sdk-php-v1/proposal.md`
- [x] Create `openspec/changes/sdk-php-v1/design.md`
- [ ] Create `composer.json`
- [ ] Create `.gitignore`
- [ ] Create `AGENTS.md`
- [ ] Create `README.md` (English)
- [ ] Create `README.zh-TW.md` (Traditional Chinese)

### T2 — DTO Layer
- [ ] Implement `NodeId` — value object
- [ ] Implement `FileType` — backed enum
- [ ] Implement `Node` — core node DTO with `fromArray()`
- [ ] Implement `Edge` — core edge DTO with `fromArray()`
- [ ] Implement `GraphMetadata` — graph metadata DTO
- [ ] Implement `GraphOutput` — complete graph container
- [ ] Implement `GraphSummary` — summary result DTO
- [ ] Implement `WorkspaceContext` — workspace context DTO
- [ ] Implement `MemoryQueryResult` — memory query result
- [ ] Implement `ReindexResult` — reindex result DTO
- [ ] Implement `ReviewFinding` — review finding DTO
- [ ] Implement `TelemetryBinding` — telemetry binding DTO
- [ ] Implement `CoverageResult` — coverage result DTO
- [ ] Implement `RelayStatus` — relay status DTO

### T3 — Exception Layer
- [ ] Implement `GraphifyException` — base exception
- [ ] Implement `TransportException` — transport/IO errors
- [ ] Implement `ProtocolException` — JSON-RPC protocol errors
- [ ] Implement `EngineException` — engine-level errors

### T4 — Transport Layer
- [ ] Implement `McpTransport` — Stdio/JSON-RPC transport
  - [ ] Process lifecycle (`start()`, `stop()`, destructor cleanup)
  - [ ] `sendRequest(string $method, array $params): array`
  - [ ] Request ID generation
  - [ ] Error response handling
  - [ ] Timeout handling
  - [ ] Process auto-detection from PATH

### T5 — Client Layer
- [ ] Implement `GraphifyClient` — public API facade
  - [ ] Constructor with project path, binary path, auto workspace_key derivation
  - [ ] Core Graph methods (5 tools)
  - [ ] Memory Query methods (1 tool)
  - [ ] Relay/Handoff methods (7 tools)
  - [ ] OpenDoc methods (3 tools)
  - [ ] Review methods (4 tools)
  - [ ] Telemetry methods (2 tools)
  - [ ] Coverage methods (3 tools)
  - [ ] Plugin Gateway method (1 tool)

### T6 — Verification
- [ ] Verify `composer dump-autoload` works
- [ ] Basic syntax check with `php -l` on all files
- [ ] Verify all 24+ tools are wrapped
- [ ] Verify DTO field alignment with Rust structs

## Dependencies

```
T1 (scaffolding) → T2 (DTOs) + T3 (exceptions)
T2 + T3 → T4 (transport)
T4 → T5 (client)
T5 → T6 (verification)
```
