<?php

declare(strict_types=1);

namespace Graphify\Sdk\Test\Dto;

use Graphify\Sdk\Dto\Edge;
use PHPUnit\Framework\TestCase;

final class EdgeTest extends TestCase
{
    public function test_from_array_minimal(): void
    {
        $edge = Edge::fromArray([
            'source' => 'a:kind:a1',
            'target' => 'b:kind:b1',
            'relation' => 'calls',
            'source_file' => 'src/a.rs',
            'confidence' => 'EXTRACTED',
            'source_location' => 'src/a.rs:5',
        ]);

        $this->assertSame('a:kind:a1', $edge->source);
        $this->assertSame('b:kind:b1', $edge->target);
        $this->assertSame('calls', $edge->relation);
        $this->assertSame('EXTRACTED', $edge->confidence);
        $this->assertNull($edge->description);
    }

    public function test_from_array_with_description(): void
    {
        $edge = Edge::fromArray([
            'source' => 'a:kind:a1',
            'target' => 'b:kind:b1',
            'relation' => 'imports',
            'source_file' => 'src/main.rs',
            'confidence' => 'INFERRED',
            'source_location' => 'src/main.rs:1',
            'description' => 'Module import',
        ]);

        $this->assertSame('Module import', $edge->description);
    }

    public function test_from_array_default_confidence(): void
    {
        $edge = Edge::fromArray([
            'source' => 'a',
            'target' => 'b',
            'relation' => 'contains',
            'source_file' => 'x.rs',
            'source_location' => 'x.rs:1',
        ]);

        $this->assertSame('EXTRACTED', $edge->confidence);
    }

    public function test_list_from_array(): void
    {
        $edges = Edge::listFromArray([
            [
                'source' => 'a',
                'target' => 'b',
                'relation' => 'calls',
                'source_file' => 'a.rs',
                'confidence' => 'EXTRACTED',
                'source_location' => 'a.rs:1',
            ],
            [
                'source' => 'b',
                'target' => 'c',
                'relation' => 'calls',
                'source_file' => 'b.rs',
                'confidence' => 'EXTRACTED',
                'source_location' => 'b.rs:5',
            ],
        ]);

        $this->assertCount(2, $edges);
        $this->assertSame('b', $edges[1]->source);
    }
}
