# Proposal: graphify-sdk-php v1

## Summary

Build the official **Graphify PHP SDK** — Layer 2 external SDK that lets PHP
developers and Laravel ecosystem users access Graphify's topology, memory,
review, telemetry, coverage, and handoff capabilities over Stdio/JSON-RPC
(MCP protocol).

## Motivation

Graphify's plugin ecosystem is two-layered:

1. **Layer 1 — embedded plugin trait** (Rust, in-process): first-party plugins
   implement `GraphifyPlugin` trait and run on Graphify Core's in-memory petgraph.
2. **Layer 2 — external SDK** (any language): access Graphify's topology via
   Stdio/JSON-RPC, served by `graphify-mcp`.

The PHP SDK is the second official language SDK (after Python), targeting the
large PHP/Laravel ecosystem. It enables:

- **Database migration impact analysis**: trace column/table changes through
  Eloquent models, observers, jobs, and repositories
- **Refactoring safety net**: given a symbol, find all downstream/upstream
  dependents
- **Code review integration**: query review findings, telemetry, coverage data
- **Plugin development**: build PHP-based graphify plugins that register via
  `graphify.toml`

## Scope

### v1 features

- **MCP Transport**: Stdio/JSON-RPC communication with `graphify-mcp`
- **Full Tool Coverage**: All 24+ graphify-mcp tools wrapped as PHP methods
- **DTO Layer**: Strongly-typed PHP objects for all core data types
- **Workspace Management**: Automatic `workspace_key` derivation from project path
- **Error Handling**: Typed exception hierarchy (transport, protocol, engine)

### Out of scope (v1)

- HTTP transport (Stdio-only in v1)
- Plugin registration in `graphify.toml` (covered by docs, not SDK code)
- Async/parallel tool calls (single-threaded synchronous in v1)

## Success Criteria

1. Every `graphify-mcp` tool has a corresponding PHP method with correct
   parameter types and return DTOs
2. All DTOs match the Rust struct field names and types
3. Stdio transport handles process lifecycle (spawn, communicate, shutdown)
4. PHP 8.0+ compatible with no framework dependency
5. Documentation in both English and Traditional Chinese
