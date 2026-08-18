<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * Represents an edge (relationship) between two nodes in the Graphify knowledge graph.
 *
 * Maps to the Rust struct `graphify_core::types::Edge`.
 */
final class Edge
{
    /**
     * @param string      $source         Source node ID
     * @param string      $target         Target node ID
     * @param string      $relation       Relationship type ("calls", "imports", "contains", etc.)
     * @param string      $sourceFile     Source file path
     * @param string      $confidence     Confidence level (default: "EXTRACTED")
     * @param string      $sourceLocation Location string (e.g., "src/lib.rs:12")
     * @param string|null $description    Optional description
     */
    public function __construct(
        public readonly string $source,
        public readonly string $target,
        public readonly string $relation,
        public readonly string $sourceFile,
        public readonly string $confidence,
        public readonly string $sourceLocation,
        public readonly ?string $description = null,
    ) {}

    /**
     * Create an Edge from a decoded JSON array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            source: (string) ($data['source'] ?? ''),
            target: (string) ($data['target'] ?? ''),
            relation: (string) ($data['relation'] ?? ''),
            sourceFile: (string) ($data['source_file'] ?? ''),
            confidence: (string) ($data['confidence'] ?? 'EXTRACTED'),
            sourceLocation: (string) ($data['source_location'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'source' => $this->source,
            'target' => $this->target,
            'relation' => $this->relation,
            'source_file' => $this->sourceFile,
            'confidence' => $this->confidence,
            'source_location' => $this->sourceLocation,
            'description' => $this->description,
        ], fn ($v) => $v !== null);
    }

    /**
     * Create multiple Edges from an array of decoded JSON arrays.
     *
     * @param array[] $dataList
     * @return self[]
     */
    public static function listFromArray(array $dataList): array
    {
        return array_map(fn (array $data) => self::fromArray($data), $dataList);
    }
}
