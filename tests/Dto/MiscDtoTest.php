<?php

declare(strict_types=1);

namespace Graphify\Sdk\Test\Dto;

use Graphify\Sdk\Dto\NodeId;
use Graphify\Sdk\Dto\FileType;
use Graphify\Sdk\Dto\WorkspaceContext;
use Graphify\Sdk\Dto\MemoryQueryResult;
use Graphify\Sdk\Dto\ReindexResult;
use Graphify\Sdk\Dto\ReviewFinding;
use Graphify\Sdk\Dto\TelemetryBinding;
use Graphify\Sdk\Dto\CoverageResult;
use Graphify\Sdk\Dto\RelayStatus;
use PHPUnit\Framework\TestCase;

final class MiscDtoTest extends TestCase
{
    public function test_node_id(): void
    {
        $id = new NodeId('src/a.rs:function:f');
        $this->assertSame('src/a.rs:function:f', (string) $id);

        $parsed = $id->parse();
        $this->assertSame('src/a.rs', $parsed['file']);
        $this->assertSame('function', $parsed['kind']);
        $this->assertSame('f', $parsed['name']);
    }

    public function test_node_id_static_factory(): void
    {
        $id = NodeId::fromString('x:class:Y');
        $this->assertSame('x:class:Y', $id->id);
    }

    public function test_file_type_enum(): void
    {
        $this->assertSame('code', FileType::Code->value);
        $this->assertSame('document', FileType::Document->value);
        $this->assertSame('paper', FileType::Paper->value);
        $this->assertSame('image', FileType::Image->value);
    }

    public function test_file_type_from_string(): void
    {
        $this->assertSame(FileType::Code, FileType::fromString('code'));
        $this->assertSame(FileType::Document, FileType::fromString('DOCUMENT'));
        $this->assertSame(FileType::Code, FileType::fromString('unknown'));
    }

    public function test_workspace_context(): void
    {
        $wc = WorkspaceContext::fromArray([
            'workspace_key' => 'w-abc123',
            'workspace_name' => 'my-project',
            'root_path' => '/home/user/project',
            'timestamp' => 1724000000,
        ]);

        $this->assertSame('w-abc123', $wc->workspaceKey);
        $this->assertSame('my-project', $wc->workspaceName);

        $arr = $wc->toArray();
        $this->assertSame('w-abc123', $arr['workspace_key']);
    }

    public function test_memory_query_result_found(): void
    {
        $mqr = MemoryQueryResult::fromArray([
            'status' => 'found',
            'nodes' => [
                ['id' => 'a:kind:a1', 'label' => 'a1', 'file_type' => 'code', 'kind' => 'func', 'language' => 'php', 'source_file' => 'a.php', 'start_line' => 1, 'end_line' => 2],
            ],
        ]);

        $this->assertTrue($mqr->isFound());
        $this->assertCount(1, $mqr->getNodes());
    }

    public function test_memory_query_result_not_found(): void
    {
        $mqr = MemoryQueryResult::fromArray(['status' => 'not_found']);
        $this->assertFalse($mqr->isFound());
        $this->assertCount(0, $mqr->getNodes());
    }

    public function test_reindex_result(): void
    {
        $rr = ReindexResult::fromArray([
            'status' => 'success',
            'total_nodes' => 15,
            'total_edges' => 42,
        ]);

        $this->assertTrue($rr->isSuccess());
        $this->assertSame(15, $rr->totalNodes);
    }

    public function test_reindex_result_failure(): void
    {
        $rr = ReindexResult::fromArray(['status' => 'error']);
        $this->assertFalse($rr->isSuccess());
    }

    public function test_review_finding(): void
    {
        $rf = ReviewFinding::fromArray([
            'review_id' => 'rev-001',
            'affected_symbols' => ['src/a.rs:function:f'],
            'finding_severity' => 'high',
            'resolution_status' => 'open',
            'review_comment' => 'Missing input validation',
            'git_commit_sha' => 'abc123',
        ]);

        $this->assertSame('rev-001', $rf->reviewId);
        $this->assertFalse($rf->isResolved());
        $this->assertSame('abc123', $rf->gitCommitSha);
    }

    public function test_review_finding_resolved(): void
    {
        $rf = ReviewFinding::fromArray([
            'review_id' => 'rev-002',
            'affected_symbols' => [],
            'finding_severity' => 'low',
            'resolution_status' => 'resolved',
            'review_comment' => 'Fixed',
        ]);

        $this->assertTrue($rf->isResolved());
    }

    public function test_telemetry_binding(): void
    {
        $tb = TelemetryBinding::fromArray([
            'node' => 'src/db.rs:function:query',
            'p99_latency' => 250.5,
            'alloc_bytes' => 1024.0,
            'call_rate' => 100.0,
            'is_hotspot' => true,
        ]);

        $this->assertSame(250.5, $tb->p99Latency);
        $this->assertTrue($tb->isHotspot);
        $this->assertNull($tb->impactRadius);
    }

    public function test_coverage_result(): void
    {
        $cr = CoverageResult::fromArray([
            'node' => 'src/a.rs:function:f',
            'covered_lines' => 8,
            'total_lines' => 10,
            'line_rate' => 0.8,
        ]);

        $this->assertSame(80.0, $cr->getPercentage());
        $this->assertSame('src/a.rs:function:f', $cr->node);
    }

    public function test_relay_status(): void
    {
        $rs = RelayStatus::fromArray([
            'repos' => ['repo-a' => ['phase' => 'planning']],
            'active_baton' => 'repo-a',
            'last_update' => '2026-08-19T00:00:00Z',
        ]);

        $this->assertSame('repo-a', $rs->activeBaton);
        $this->assertArrayHasKey('repo-a', $rs->repos);
    }
}
