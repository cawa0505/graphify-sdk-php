<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * Represents a node in the Graphify knowledge graph.
 *
 * Maps to the Rust struct `graphify_core::types::Node`.
 */
final class Node
{
    /**
     * @param string         $id          Canonical node ID (e.g., "./src/lib.rs:function:MyFunction")
     * @param string         $label       Symbol name (e.g., "MyFunction")
     * @param string         $fileType    FileType enum value ("code", "document", etc.)
     * @param string         $kind        Node kind ("function", "class", "struct", "module", etc.)
     * @param string         $language    Programming language ("rust", "python", "php", etc.)
     * @param string         $sourceFile  Absolute or relative file path
     * @param int            $startLine   0-indexed start line
     * @param int            $endLine     0-indexed end line
     * @param string|null    $docComment  Optional docstring
     * @param string|null    $description Optional description
     * @param array|null     $metadata    Optional custom metadata key-value map
     */
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $fileType,
        public readonly string $kind,
        public readonly string $language,
        public readonly string $sourceFile,
        public readonly int $startLine,
        public readonly int $endLine,
        public readonly ?string $docComment = null,
        public readonly ?string $description = null,
        public readonly ?array $metadata = null,
    ) {}

    /**
     * Create a Node from a decoded JSON array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            label: (string) ($data['label'] ?? ''),
            fileType: (string) ($data['file_type'] ?? 'code'),
            kind: (string) ($data['kind'] ?? ''),
            language: (string) ($data['language'] ?? ''),
            sourceFile: (string) ($data['source_file'] ?? ''),
            startLine: (int) ($data['start_line'] ?? 0),
            endLine: (int) ($data['end_line'] ?? 0),
            docComment: isset($data['doc_comment']) ? (string) $data['doc_comment'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            metadata: isset($data['metadata']) ? (array) $data['metadata'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'label' => $this->label,
            'file_type' => $this->fileType,
            'kind' => $this->kind,
            'language' => $this->language,
            'source_file' => $this->sourceFile,
            'start_line' => $this->startLine,
            'end_line' => $this->endLine,
            'doc_comment' => $this->docComment,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ], fn ($v) => $v !== null);
    }

    /**
     * Create multiple Nodes from an array of decoded JSON arrays.
     *
     * @param array[] $dataList
     * @return self[]
     */
    public static function listFromArray(array $dataList): array
    {
        return array_map(fn (array $data) => self::fromArray($data), $dataList);
    }
}
