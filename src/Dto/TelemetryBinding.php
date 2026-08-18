<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * A telemetry binding for a node.
 */
final class TelemetryBinding
{
    /**
     * @param string      $node               Canonical node ID
     * @param float       $p99Latency         P99 latency in milliseconds
     * @param float       $allocBytes         Allocation in bytes
     * @param float       $callRate           Calls per second
     * @param bool        $isHotspot          Whether this node is a hotspot
     * @param array|null  $impactRadius       Upstream callers (optional, when include_impact_radius=true)
     */
    public function __construct(
        public readonly string $node,
        public readonly float $p99Latency = 0.0,
        public readonly float $allocBytes = 0.0,
        public readonly float $callRate = 0.0,
        public readonly bool $isHotspot = false,
        public readonly ?array $impactRadius = null,
    ) {}

    /**
     * Create TelemetryBinding from a decoded JSON array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            node: (string) ($data['node'] ?? ''),
            p99Latency: (float) ($data['p99_latency'] ?? 0.0),
            allocBytes: (float) ($data['alloc_bytes'] ?? 0.0),
            callRate: (float) ($data['call_rate'] ?? 0.0),
            isHotspot: (bool) ($data['is_hotspot'] ?? false),
            impactRadius: isset($data['impact_radius']) ? (array) $data['impact_radius'] : null,
        );
    }
}
