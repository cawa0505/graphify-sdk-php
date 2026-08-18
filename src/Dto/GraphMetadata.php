<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * Metadata about a Graphify knowledge graph.
 *
 * Maps to the Rust struct `graphify_core::types::GraphMetadata`.
 */
final class GraphMetadata
{
    /**
     * @param string            $version      Format version (e.g., "1.0.0")
     * @param string            $generatedAt  Timestamp string
     * @param int               $totalNodes   Total node count
     * @param int               $totalEdges   Total edge count
     * @param string[]          $languages    Languages present in the graph
     * @param int               $inputTokens  LLM input token count
     * @param int               $outputTokens LLM output token count
     * @param array<string,mixed>|null $pluginData Plugin metadata (optional)
     */
    public function __construct(
        public readonly string $version,
        public readonly string $generatedAt,
        public readonly int $totalNodes,
        public readonly int $totalEdges,
        public readonly array $languages,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly ?array $pluginData = null,
    ) {}

    /**
     * Create GraphMetadata from a decoded JSON array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            version: (string) ($data['version'] ?? '1.0.0'),
            generatedAt: (string) ($data['generated_at'] ?? ''),
            totalNodes: (int) ($data['total_nodes'] ?? 0),
            totalEdges: (int) ($data['total_edges'] ?? 0),
            languages: isset($data['languages']) ? (array) $data['languages'] : [],
            inputTokens: (int) ($data['input_tokens'] ?? 0),
            outputTokens: (int) ($data['output_tokens'] ?? 0),
            pluginData: isset($data['plugin_data']) ? (array) $data['plugin_data'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'version' => $this->version,
            'generated_at' => $this->generatedAt,
            'total_nodes' => $this->totalNodes,
            'total_edges' => $this->totalEdges,
            'languages' => $this->languages,
            'input_tokens' => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'plugin_data' => $this->pluginData,
        ], fn ($v) => $v !== null);
    }
}
