<?php

declare(strict_types=1);

namespace Graphify\Sdk\Test\Dto;

use Graphify\Sdk\Dto\GraphSummary;
use Graphify\Sdk\Dto\GraphMetadata;
use Graphify\Sdk\Dto\GraphOutput;
use Graphify\Sdk\Dto\Node;
use Graphify\Sdk\Dto\Edge;
use PHPUnit\Framework\TestCase;

final class GraphOutputTest extends TestCase
{
    public function test_graph_summary(): void
    {
        $s = GraphSummary::fromArray([
            'total_nodes' => 42,
            'total_edges' => 99,
            'languages' => ['rust', 'python'],
        ]);

        $this->assertSame(42, $s->totalNodes);
        $this->assertSame(99, $s->totalEdges);
        $this->assertSame(['rust', 'python'], $s->languages);
    }

    public function test_graph_summary_defaults(): void
    {
        $s = GraphSummary::fromArray([]);
        $this->assertSame(0, $s->totalNodes);
        $this->assertSame(0, $s->totalEdges);
        $this->assertSame([], $s->languages);
    }

    public function test_graph_metadata(): void
    {
        $m = GraphMetadata::fromArray([
            'version' => '1.0.0',
            'generated_at' => '2026-08-19T00:00:00Z',
            'total_nodes' => 100,
            'total_edges' => 200,
            'languages' => ['rust'],
            'input_tokens' => 5000,
            'output_tokens' => 1000,
        ]);

        $this->assertSame('1.0.0', $m->version);
        $this->assertSame(5000, $m->inputTokens);
        $this->assertNull($m->pluginData);
    }

    public function test_graph_output(): void
    {
        $go = GraphOutput::fromArray([
            'nodes' => [
                [
                    'id' => 'a:kind:a1',
                    'label' => 'a1',
                    'file_type' => 'code',
                    'kind' => 'function',
                    'language' => 'rust',
                    'source_file' => 'a.rs',
                    'start_line' => 1,
                    'end_line' => 5,
                ],
            ],
            'edges' => [],
            'metadata' => [
                'version' => '1.0.0',
                'generated_at' => '2026-08-19T00:00:00Z',
                'total_nodes' => 1,
                'total_edges' => 0,
                'languages' => ['rust'],
            ],
        ]);

        $this->assertCount(1, $go->nodes);
        $this->assertCount(0, $go->edges);
        $this->assertSame('1.0.0', $go->metadata->version);
    }
}
