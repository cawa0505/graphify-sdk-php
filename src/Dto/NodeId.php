<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * Value object representing a Node ID in the Graphify knowledge graph.
 *
 * Format: "./path/to/file:kind:Name" (e.g., "./src/lib.rs:function:MyFunction")
 */
final class NodeId
{
    public function __construct(
        public readonly string $id,
    ) {}

    public function __toString(): string
    {
        return $this->id;
    }

    /**
     * Parse a NodeId into its components: file, kind, name.
     *
     * @return array{file: string, kind: string, name: string}
     */
    public function parse(): array
    {
        $parts = explode(':', $this->id, 3);

        return [
            'file' => $parts[0] ?? '',
            'kind' => $parts[1] ?? '',
            'name' => $parts[2] ?? '',
        ];
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }
}
