<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * Complete graph output containing nodes, edges, and metadata.
 *
 * Maps to the Rust struct `graphify_core::types::GraphOutput`.
 */
final class GraphOutput
{
    /**
     * @param Node[]         $nodes    List of graph nodes
     * @param Edge[]         $edges    List of graph edges
     * @param GraphMetadata  $metadata Graph metadata
     */
    public function __construct(
        public readonly array $nodes,
        public readonly array $edges,
        public readonly GraphMetadata $metadata,
    ) {}

    /**
     * Create GraphOutput from a decoded JSON array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nodes: isset($data['nodes']) ? Node::listFromArray($data['nodes']) : [],
            edges: isset($data['edges']) ? Edge::listFromArray($data['edges']) : [],
            metadata: GraphMetadata::fromArray($data['metadata'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'nodes' => array_map(fn (Node $n) => $n->toArray(), $this->nodes),
            'edges' => array_map(fn (Edge $e) => $e->toArray(), $this->edges),
            'metadata' => $this->metadata->toArray(),
        ];
    }
}
