<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * Result of a relay_status call.
 */
final class RelayStatus
{
    /**
     * @param array $repos      Registered repos with their state
     * @param string|null $activeBaton Active repo (if any)
     * @param string|null $specDrift   Spec drift info
     * @param string|null $lastUpdate  Last update timestamp
     */
    public function __construct(
        public readonly array $repos = [],
        public readonly ?string $activeBaton = null,
        public readonly ?string $specDrift = null,
        public readonly ?string $lastUpdate = null,
    ) {}

    /**
     * Create RelayStatus from a decoded JSON array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            repos: isset($data['repos']) ? (array) $data['repos'] : [],
            activeBaton: isset($data['active_baton']) ? (string) $data['active_baton'] : null,
            specDrift: isset($data['spec_drift']) ? (string) $data['spec_drift'] : null,
            lastUpdate: isset($data['last_update']) ? (string) $data['last_update'] : null,
        );
    }
}
