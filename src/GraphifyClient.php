<?php

declare(strict_types=1);

namespace Graphify\Sdk;

use Graphify\Sdk\Bridge\McpTransport;
use Graphify\Sdk\Dto\CoverageResult;
use Graphify\Sdk\Dto\GraphOutput;
use Graphify\Sdk\Dto\GraphSummary;
use Graphify\Sdk\Dto\MemoryQueryResult;
use Graphify\Sdk\Dto\ReindexResult;
use Graphify\Sdk\Dto\RelayStatus;
use Graphify\Sdk\Dto\WorkspaceContext;

/**
 * Graphify PHP SDK — main client interface.
 *
 * Provides access to all graphify-mcp tools over Stdio/JSON-RPC.
 * Auto-derives workspace_key from the project path.
 */
final class GraphifyClient
{
    private readonly McpTransport $transport;
    private readonly string $workspaceKey;
    private readonly string $workspaceName;

    /**
     * @param string|null $projectPath Project root path (auto-detects from cwd if null)
     * @param string|null $binaryPath  Path to graphify-mcp binary (auto-detects from PATH if null)
     * @param float       $timeout     I/O timeout in seconds
     */
    public function __construct(
        ?string $projectPath = null,
        ?string $binaryPath = null,
        float $timeout = 30.0,
    ) {
        $projectPath ??= getcwd() ?: '.';
        $projectPath = realpath($projectPath) ?: $projectPath;

        $this->workspaceKey = self::deriveWorkspaceKey($projectPath);
        $this->workspaceName = basename($projectPath);

        $this->transport = new McpTransport(
            binaryPath: $binaryPath ?? 'graphify-mcp',
            timeout: $timeout,
            cwd: $projectPath,
        );
    }

    // ─── Workspace ───────────────────────────────────────────────

    /**
     * Get the current workspace context.
     */
    public function getWorkspaceContext(): WorkspaceContext
    {
        return new WorkspaceContext(
            workspaceKey: $this->workspaceKey,
            workspaceName: $this->workspaceName,
            rootPath: $this->getProjectPath(),
            timestamp: time(),
        );
    }

    /**
     * Get the project root path.
     */
    public function getProjectPath(): string
    {
        return $this->workspaceName;
    }

    /**
     * Derive a stable workspace key from a project path.
     * Mirrors the Rust implementation in graphify-core.
     */
    public static function deriveWorkspaceKey(string $projectPath): string
    {
        // Simple hash: crc32 for stability across platforms
        // (Rust uses SipHash; PHP doesn't have it built-in, so we use a stable alternative)
        return dechex(crc32($projectPath));
    }

    // ─── Core Graph Tools ────────────────────────────────────────

    /**
     * Get high-level topology metrics.
     */
    public function graphSummary(): GraphSummary
    {
        $result = $this->call('graphify_graph_summary', []);

        return GraphSummary::fromArray($result);
    }

    /**
     * BFS traversal by question.
     *
     * @return GraphOutput Graph output with nodes and edges
     */
    public function queryGraph(string $question): GraphOutput
    {
        $result = $this->call('graphify_graph_query', [
            'question' => $question,
        ]);

        return GraphOutput::fromArray($result);
    }

    /**
     * Query nodes by ID with optional depth.
     *
     * @return GraphOutput Graph output with nodes and edges
     */
    public function queryNode(string $nodeId, ?int $depth = null): GraphOutput
    {
        $params = ['node_id' => $nodeId];
        if ($depth !== null) {
            $params['depth'] = $depth;
        }

        $result = $this->call('graphify_graph_query_node', $params);

        return GraphOutput::fromArray($result);
    }

    /**
     * Find shortest path between two nodes.
     *
     * @return array List of node IDs in the path
     */
    public function tracePath(string $from, string $to): array
    {
        $result = $this->call('graphify_graph_trace_path', [
            'from' => $from,
            'to' => $to,
        ]);

        return $result['path'] ?? [];
    }

    /**
     * Reindex a file into the graph.
     */
    public function reindexFile(string $filePath): ReindexResult
    {
        $result = $this->call('graphify_graph_reindex', [
            'file_path' => $filePath,
        ]);

        return ReindexResult::fromArray($result);
    }

    // ─── Memory Query ────────────────────────────────────────────

    /**
     * Semantic memory search.
     */
    public function memoryQuery(string $query, ?int $limit = null): MemoryQueryResult
    {
        $params = [
            'workspace_key' => $this->workspaceKey,
            'query' => $query,
        ];
        if ($limit !== null) {
            $params['limit'] = $limit;
        }

        $result = $this->call('graphify_memory_query', $params);

        return MemoryQueryResult::fromArray($result);
    }

    // ─── Relay / Handoff Tools ───────────────────────────────────

    /**
     * Initialize a relay session.
     *
     * @return array<string, mixed>
     */
    public function relayInit(string $projectContext, ?string $kind = null): array
    {
        $params = ['project_context' => $projectContext];
        if ($kind !== null) {
            $params['kind'] = $kind;
        }

        return $this->call('graphify_relay_init', $params);
    }

    /**
     * Save session state.
     *
     * @param  array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function relaySave(array $params): array
    {
        return $this->call('graphify_relay_save', $params);
    }

    /**
     * Close a relay session.
     *
     * @return array<string, mixed>
     */
    public function relayClose(string $repo, string $next): array
    {
        return $this->call('graphify_relay_close', [
            'repo' => $repo,
            'next' => $next,
        ]);
    }

    /**
     * Switch to another repo.
     *
     * @return array<string, mixed>
     */
    public function relaySwitch(string $repo, ?string $kind = null): array
    {
        $params = ['repo' => $repo];
        if ($kind !== null) {
            $params['kind'] = $kind;
        }

        return $this->call('graphify_relay_switch', $params);
    }

    /**
     * Resume a relay session.
     *
     * @return array<string, mixed>
     */
    public function relayResume(string $repo, ?string $kind = null): array
    {
        $params = ['repo' => $repo];
        if ($kind !== null) {
            $params['kind'] = $kind;
        }

        return $this->call('graphify_relay_resume', $params);
    }

    /**
     * Get relay status summary.
     */
    public function relayStatus(): RelayStatus
    {
        $result = $this->call('graphify_relay_status', []);

        return RelayStatus::fromArray($result);
    }

    /**
     * Ingest a TODO/handoff doc into relay.
     *
     * @return array<string, mixed>
     */
    public function relayAdd(string $file, string $repo): array
    {
        return $this->call('graphify_relay_add', [
            'file' => $file,
            'repo' => $repo,
        ]);
    }

    // ─── OpenDoc Tools ───────────────────────────────────────────

    /**
     * Index all spec blocks in the workspace.
     *
     * @param  string[]|null $docPaths Optional explicit doc paths
     * @return array<string, mixed>
     */
    public function opendocIndex(?array $docPaths = null): array
    {
        $params = [];
        if ($docPaths !== null) {
            $params['doc_paths'] = $docPaths;
        }

        return $this->call('graphify_opendoc_index', $params);
    }

    /**
     * Get spec blocks documenting a symbol.
     *
     * @return array<string, mixed>
     */
    public function opendocGetContext(string $symbol): array
    {
        return $this->call('graphify_opendoc_get_context', [
            'symbol' => $symbol,
        ]);
    }

    /**
     * Audit doc-side drift.
     *
     * @return array<string, mixed>
     */
    public function opendocAuditDrift(): array
    {
        return $this->call('graphify_opendoc_audit_drift', []);
    }

    // ─── Review Tools ────────────────────────────────────────────

    /**
     * Import a CRG review payload.
     *
     * @return array<string, mixed>
     */
    public function reviewIngest(string $payload): array
    {
        return $this->call('graphify_review_ingest', [
            'payload' => $payload,
        ]);
    }

    /**
     * Query unresolved reviews for a node.
     *
     * @return array<string, mixed>
     */
    public function reviewGetContext(string $node): array
    {
        return $this->call('graphify_review_get_context', [
            'node' => $node,
        ]);
    }

    /**
     * Mark a review as resolved.
     *
     * @return array<string, mixed>
     */
    public function reviewResolve(string $reviewId, string $reason): array
    {
        return $this->call('graphify_review_resolve', [
            'review_id' => $reviewId,
            'reason' => $reason,
        ]);
    }

    /**
     * Search CRG for changed functions.
     *
     * @return array<string, mixed>
     */
    public function reviewSearchCrg(?string $base = null): array
    {
        $params = [];
        if ($base !== null) {
            $params['base'] = $base;
        }

        return $this->call('graphify_review_search_crg', $params);
    }

    // ─── Telemetry Tools ─────────────────────────────────────────

    /**
     * Import telemetry metrics.
     *
     * @return array<string, mixed>
     */
    public function telemetryIngest(string $source, ?string $pathOrDracoParams = null): array
    {
        $params = ['source' => $source];
        if ($pathOrDracoParams !== null) {
            $params['path_or_draco_params'] = $pathOrDracoParams;
        }

        return $this->call('graphify_telemetry_ingest', $params);
    }

    /**
     * Query telemetry bindings for a node.
     *
     * @return array<string, mixed>
     */
    public function telemetryGetContext(string $node, ?bool $includeImpactRadius = null): array
    {
        $params = ['node' => $node];
        if ($includeImpactRadius !== null) {
            $params['include_impact_radius'] = $includeImpactRadius;
        }

        return $this->call('graphify_telemetry_get_context', $params);
    }

    // ─── Coverage Tools ──────────────────────────────────────────

    /**
     * Import coverage data.
     *
     * @return array<string, mixed>
     */
    public function coverageIngest(string $format, string $data): array
    {
        return $this->call('graphify_coverage_ingest', [
            'format' => $format,
            'data' => $data,
        ]);
    }

    /**
     * Query coverage for a node.
     */
    public function coverageGetContext(string $node): CoverageResult
    {
        $result = $this->call('graphify_coverage_get_context', [
            'node' => $node,
        ]);

        return CoverageResult::fromArray($result);
    }

    /**
     * List low-coverage nodes.
     *
     * @return array<string, mixed>
     */
    public function coverageBlindspots(): array
    {
        return $this->call('graphify_coverage_blindspots', []);
    }

    // ─── Plugin Gateway ──────────────────────────────────────────

    /**
     * Broadcast a graph update notification to all plugins.
     *
     * @return array<string, mixed>
     */
    public function pluginNotify(string $kind): array
    {
        return $this->call('graphify_plugin_notify', [
            'kind' => $kind,
        ]);
    }

    // ─── Lifecycle ───────────────────────────────────────────────

    /**
     * Start the transport (explicit, auto-starts on first request).
     */
    public function start(): void
    {
        $this->transport->start();
    }

    /**
     * Stop the transport and clean up.
     */
    public function stop(): void
    {
        $this->transport->stop();
    }

    /**
     * Check if the transport is running.
     */
    public function isRunning(): bool
    {
        return $this->transport->isRunning();
    }

    // ─── Internal ────────────────────────────────────────────────

    /**
     * Low-level MCP tool call.
     *
     * @param  string               $method Tool name
     * @param  array<string, mixed> $params Tool arguments
     * @return array<string, mixed>
     */
    private function call(string $method, array $params): array
    {
        return $this->transport->sendRequest($method, $params);
    }
}
