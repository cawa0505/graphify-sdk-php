<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * Result of a reindex operation.
 */
final class ReindexResult
{
    /**
     * @param string $status     "success" or error message
     * @param int    $totalNodes Number of nodes indexed
     * @param int    $totalEdges Number of edges indexed
     */
    public function __construct(
        public readonly string $status,
        public readonly int $totalNodes = 0,
        public readonly int $totalEdges = 0,
    ) {}

    /**
     * Create ReindexResult from a decoded JSON array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: (string) ($data['status'] ?? ''),
            totalNodes: (int) ($data['total_nodes'] ?? 0),
            totalEdges: (int) ($data['total_edges'] ?? 0),
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }
}
