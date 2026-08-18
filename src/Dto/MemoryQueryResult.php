<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * Result of a memory query.
 */
final class MemoryQueryResult
{
    /**
     * @param string     $status "found", "not_found", or "error"
     * @param array[]    $nodes  List of matching nodes (decoded JSON arrays)
     */
    public function __construct(
        public readonly string $status,
        public readonly array $nodes = [],
    ) {}

    /**
     * Create MemoryQueryResult from a decoded JSON array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: (string) ($data['status'] ?? 'error'),
            nodes: isset($data['nodes']) ? (array) $data['nodes'] : [],
        );
    }

    /**
     * @return Node[]
     */
    public function getNodes(): array
    {
        return Node::listFromArray($this->nodes);
    }

    public function isFound(): bool
    {
        return $this->status === 'found';
    }
}
