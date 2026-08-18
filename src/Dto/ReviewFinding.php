<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * A code review finding.
 *
 * Maps to the Rust struct `graphify_core::plugin_memory::ReviewPayload`.
 */
final class ReviewFinding
{
    /**
     * @param string      $reviewId         Unique review identifier
     * @param string[]    $affectedSymbols   Symbol IDs affected by this finding
     * @param string      $findingSeverity   "high", "medium", or "low"
     * @param string      $resolutionStatus  "open" or "resolved"
     * @param string      $reviewComment     Review comment text
     * @param string|null $gitCommitSha      Git commit SHA (optional)
     */
    public function __construct(
        public readonly string $reviewId,
        public readonly array $affectedSymbols,
        public readonly string $findingSeverity,
        public readonly string $resolutionStatus,
        public readonly string $reviewComment,
        public readonly ?string $gitCommitSha = null,
    ) {}

    /**
     * Create ReviewFinding from a decoded JSON array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            reviewId: (string) ($data['review_id'] ?? ''),
            affectedSymbols: isset($data['affected_symbols']) ? (array) $data['affected_symbols'] : [],
            findingSeverity: (string) ($data['finding_severity'] ?? 'medium'),
            resolutionStatus: (string) ($data['resolution_status'] ?? 'open'),
            reviewComment: (string) ($data['review_comment'] ?? ''),
            gitCommitSha: isset($data['git_commit_sha']) ? (string) $data['git_commit_sha'] : null,
        );
    }

    public function isResolved(): bool
    {
        return $this->resolutionStatus === 'resolved';
    }
}
