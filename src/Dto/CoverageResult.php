<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * Coverage result for a node.
 */
final class CoverageResult
{
    /**
     * @param string $node         Canonical node ID
     * @param int    $coveredLines Number of covered lines
     * @param int    $totalLines   Total lines
     * @param float  $lineRate     Coverage ratio (0.0 - 1.0)
     */
    public function __construct(
        public readonly string $node,
        public readonly int $coveredLines = 0,
        public readonly int $totalLines = 0,
        public readonly float $lineRate = 0.0,
    ) {}

    /**
     * Create CoverageResult from a decoded JSON array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            node: (string) ($data['node'] ?? ''),
            coveredLines: (int) ($data['covered_lines'] ?? 0),
            totalLines: (int) ($data['total_lines'] ?? 0),
            lineRate: (float) ($data['line_rate'] ?? 0.0),
        );
    }

    public function getPercentage(): float
    {
        return $this->lineRate * 100;
    }
}
