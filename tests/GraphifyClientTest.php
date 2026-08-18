<?php

declare(strict_types=1);

namespace Graphify\Sdk\Test;

use Graphify\Sdk\Dto\CoverageResult;
use Graphify\Sdk\Dto\GraphSummary;
use Graphify\Sdk\Dto\MemoryQueryResult;
use Graphify\Sdk\Dto\ReindexResult;
use Graphify\Sdk\Dto\RelayStatus;
use Graphify\Sdk\GraphifyClient;
use PHPUnit\Framework\TestCase;

final class GraphifyClientTest extends TestCase
{
    public function test_derive_workspace_key_stable(): void
    {
        $k1 = GraphifyClient::deriveWorkspaceKey('/some/project');
        $k2 = GraphifyClient::deriveWorkspaceKey('/some/project');
        $this->assertSame($k1, $k2);
    }

    public function test_derive_workspace_key_different(): void
    {
        $k1 = GraphifyClient::deriveWorkspaceKey('/project/one');
        $k2 = GraphifyClient::deriveWorkspaceKey('/project/two');
        $this->assertNotSame($k1, $k2);
    }

    public function test_get_workspace_context(): void
    {
        $client = new GraphifyClient('/tmp');
        $ctx = $client->getWorkspaceContext();

        $this->assertNotEmpty($ctx->workspaceKey);
        $this->assertSame('tmp', $ctx->workspaceName);
        $this->assertNotEmpty($ctx->rootPath);
    }

    public function test_graph_summary_parsing(): void
    {
        // Use reflection to call the private call() method with a mock response
        $result = GraphSummary::fromArray([
            'total_nodes' => 100,
            'total_edges' => 50,
            'languages' => ['rust', 'python'],
        ]);

        $this->assertSame(100, $result->totalNodes);
        $this->assertSame(50, $result->totalEdges);
        $this->assertSame(['rust', 'python'], $result->languages);
    }

    public function test_memory_query_result_parsing(): void
    {
        $result = MemoryQueryResult::fromArray([
            'status' => 'found',
            'nodes' => [
                ['id' => 'a:func:x', 'label' => 'x', 'file_type' => 'code', 'kind' => 'func', 'language' => 'php', 'source_file' => 'a.php', 'start_line' => 1, 'end_line' => 5],
            ],
        ]);

        $this->assertTrue($result->isFound());
        $this->assertCount(1, $result->getNodes());
        $this->assertSame('x', $result->getNodes()[0]->label);
    }

    public function test_reindex_result_parsing(): void
    {
        $result = ReindexResult::fromArray([
            'status' => 'success',
            'total_nodes' => 10,
            'total_edges' => 20,
        ]);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(10, $result->totalNodes);
    }

    public function test_coverage_result_parsing(): void
    {
        $result = CoverageResult::fromArray([
            'node' => 'src/app.php:function:main',
            'covered_lines' => 5,
            'total_lines' => 10,
            'line_rate' => 0.5,
        ]);

        $this->assertSame(50.0, $result->getPercentage());
    }

    public function test_relay_status_parsing(): void
    {
        $result = RelayStatus::fromArray([
            'repos' => ['repo-a' => ['phase' => 'executing']],
            'active_baton' => 'repo-a',
        ]);

        $this->assertSame('repo-a', $result->activeBaton);
    }
}
