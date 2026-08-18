<?php

declare(strict_types=1);

namespace Graphify\Sdk\Dto;

/**
 * Workspace context information.
 *
 * Maps to the Rust struct `graphify_core::plugin::WorkspaceContext`.
 */
final class WorkspaceContext
{
    /**
     * @param string $workspaceKey  Stable hash identifier for the workspace
     * @param string $workspaceName Human-readable workspace name
     * @param string $rootPath      Absolute filesystem root path
     * @param int    $timestamp     Unix epoch seconds
     */
    public function __construct(
        public readonly string $workspaceKey,
        public readonly string $workspaceName,
        public readonly string $rootPath,
        public readonly int $timestamp,
    ) {}

    /**
     * Create WorkspaceContext from a decoded JSON array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            workspaceKey: (string) ($data['workspace_key'] ?? ''),
            workspaceName: (string) ($data['workspace_name'] ?? ''),
            rootPath: (string) ($data['root_path'] ?? ''),
            timestamp: (int) ($data['timestamp'] ?? time()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'workspace_key' => $this->workspaceKey,
            'workspace_name' => $this->workspaceName,
            'root_path' => $this->rootPath,
            'timestamp' => $this->timestamp,
        ];
    }
}
