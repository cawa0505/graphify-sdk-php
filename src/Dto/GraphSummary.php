<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * Result of a graphify_graph_summary call.
 */
final class GraphSummary
{
    /**
     * @param int      $totalNodes Total node count
     * @param int      $totalEdges Total edge count
     * @param string[] $languages  Languages present in the graph
     */
    public function __construct(
        public readonly int $totalNodes,
        public readonly int $totalEdges,
        public readonly array $languages,
    ) {}

    /**
     * Create GraphSummary from a decoded JSON array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            totalNodes: (int) ($data['total_nodes'] ?? 0),
            totalEdges: (int) ($data['total_edges'] ?? 0),
            languages: isset($data['languages']) ? (array) $data['languages'] : [],
        );
    }
}
