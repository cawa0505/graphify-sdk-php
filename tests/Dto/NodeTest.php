<?php

declare(strict_types=1);

namespace Graphify\Sdk\Test\Dto;

use Graphify\Sdk\Dto\Node;
use PHPUnit\Framework\TestCase;

final class NodeTest extends TestCase
{
    public function test_from_array_minimal(): void
    {
        $node = Node::fromArray([
            'id' => 'src/a.rs:function:f',
            'label' => 'f',
            'file_type' => 'code',
            'kind' => 'function',
            'language' => 'rust',
            'source_file' => 'src/a.rs',
            'start_line' => 1,
            'end_line' => 10,
        ]);

        $this->assertSame('src/a.rs:function:f', $node->id);
        $this->assertSame('f', $node->label);
        $this->assertSame('function', $node->kind);
        $this->assertSame('rust', $node->language);
        $this->assertSame('src/a.rs', $node->sourceFile);
        $this->assertSame(1, $node->startLine);
        $this->assertSame(10, $node->endLine);
        $this->assertNull($node->docComment);
        $this->assertNull($node->description);
        $this->assertNull($node->metadata);
    }

    public function test_from_array_full(): void
    {
        $node = Node::fromArray([
            'id' => 'src/lib.rs:function:greet',
            'label' => 'greet',
            'file_type' => 'code',
            'kind' => 'function',
            'language' => 'rust',
            'source_file' => 'src/lib.rs',
            'start_line' => 5,
            'end_line' => 12,
            'doc_comment' => 'Greets the user',
            'description' => 'A friendly greeting function',
            'metadata' => ['deprecated' => false],
        ]);

        $this->assertSame('Greets the user', $node->docComment);
        $this->assertSame('A friendly greeting function', $node->description);
        $this->assertSame(['deprecated' => false], $node->metadata);
    }

    public function test_to_array_omits_nulls(): void
    {
        $node = Node::fromArray([
            'id' => 'x:kind:y',
            'label' => 'y',
            'file_type' => 'code',
            'kind' => 'function',
            'language' => 'php',
            'source_file' => 'x.php',
            'start_line' => 0,
            'end_line' => 0,
        ]);

        $arr = $node->toArray();
        $this->assertArrayNotHasKey('doc_comment', $arr);
        $this->assertArrayNotHasKey('description', $arr);
        $this->assertArrayNotHasKey('metadata', $arr);
        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('label', $arr);
    }

    public function test_list_from_array(): void
    {
        $nodes = Node::listFromArray([
            [
                'id' => 'a:kind:a1',
                'label' => 'a1',
                'file_type' => 'code',
                'kind' => 'function',
                'language' => 'php',
                'source_file' => 'a.php',
                'start_line' => 1,
                'end_line' => 2,
            ],
            [
                'id' => 'b:kind:b1',
                'label' => 'b1',
                'file_type' => 'code',
                'kind' => 'class',
                'language' => 'php',
                'source_file' => 'b.php',
                'start_line' => 10,
                'end_line' => 50,
            ],
        ]);

        $this->assertCount(2, $nodes);
        $this->assertSame('a1', $nodes[0]->label);
        $this->assertSame('class', $nodes[1]->kind);
    }
}
