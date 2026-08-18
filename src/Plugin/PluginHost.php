<?php

declare(strict_types=1);

namespace Graphify\Sdk\Plugin;

/**
 * JSON-RPC stdio host for Graphify Plugin SDK.
 *
 * Runs as a subprocess (IPC mode), receives JSON-RPC requests on stdin,
 * dispatches to registered tool handlers, and writes responses to stdout.
 *
 * Protocol: MCP JSON-RPC (subset)
 *   - initialize          → returns tools + capabilities
 *   - tools/list          → returns registered tools
 *   - tools/call          → dispatches to handler
 *   - notifications/*     → silently accepted (no response)
 *
 * Usage:
 *   $host = new PluginHost();
 *   $host->registerTool('analyze_schema', [
 *       'description' => 'Analyze Laravel database schema',
 *       'inputSchema'  => ['type' => 'object', 'properties' => [...]],
 *   ], function (array $args): array {
 *       return ['columns' => [...]];
 *   });
 *   $host->run(); // blocks, reads stdin forever
 */
final class PluginHost
{
    /** @var array<string, array{description: string, inputSchema: array, handler: callable}> */
    private array $tools = [];

    private bool $running = false;

    /**
     * Register a tool that this plugin provides.
     *
     * @param string   $name    Tool name (e.g., "analyze_schema")
     * @param array    $schema  Tool schema with 'description' and 'inputSchema' keys
     * @param callable $handler Function(array $args): array
     */
    public function registerTool(string $name, array $schema, callable $handler): self
    {
        $this->tools[$name] = [
            'description' => $schema['description'] ?? '',
            'inputSchema' => $schema['inputSchema'] ?? ['type' => 'object', 'properties' => []],
            'handler'     => $handler,
        ];

        return $this;
    }

    /**
     * Start the JSON-RPC stdio listener (blocking).
     *
     * Reads newline-delimited JSON from stdin, writes responses to stdout.
     * Exits cleanly when stdin closes (parent process terminated the pipe).
     */
    public function run(): never
    {
        $this->running = true;

        while ($this->running) {
            $line = fgets(STDIN);

            if ($line === false || $line === null) {
                break; // EOF — parent closed the pipe
            }

            $line = trim($line);
            if ($line === '') {
                continue;
            }

            /** @var array|null $request */
            $request = json_decode($line, true);

            if (!is_array($request) || !isset($request['method'])) {
                continue;
            }

            $id     = $request['id'] ?? null;
            $method = $request['method'];
            $params = $request['params'] ?? [];

            // Notifications (no id) — no response expected
            if ($id === null) {
                continue;
            }

            try {
                $result = match ($method) {
                    'initialize' => $this->handleInitialize(),
                    'tools/list' => $this->handleToolsList(),
                    'tools/call' => $this->handleToolsCall($params),
                    default      => throw new \RuntimeException("Unknown method: {$method}"),
                };

                $this->sendResponse($id, $result);
            } catch (\Throwable $e) {
                $this->sendError($id, -32603, $e->getMessage());
            }
        }

        exit(0);
    }

    /**
     * Gracefully stop the listener loop.
     */
    public function stop(): void
    {
        $this->running = false;
    }

    // ─── Protocol handlers ────────────────────────────────────────

    /**
     * Handle 'initialize' — return protocol version + capabilities + tools.
     *
     * @return array<string, mixed>
     */
    public function handleInitialize(): array
    {
        return [
            'protocolVersion' => '2024-11-05',
            'capabilities'    => [
                'tools' => new \stdClass(), // {} in JSON
            ],
            'tools' => $this->buildToolList(),
        ];
    }

    /**
     * Handle 'tools/list' — return registered tools.
     *
     * @return array<string, mixed>
     */
    public function handleToolsList(): array
    {
        return [
            'tools' => $this->buildToolList(),
        ];
    }

    /**
     * Handle 'tools/call' — dispatch to registered handler.
     *
     * @param  array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function handleToolsCall(array $params): array
    {
        $name      = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        if (!isset($this->tools[$name])) {
            throw new \RuntimeException("Tool not found: {$name}");
        }

        $result = ($this->tools[$name]['handler'])($arguments);

        return [
            'content' => [
                ['type' => 'text', 'text' => json_encode(
                    $result,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                )],
            ],
        ];
    }

    // ─── I/O helpers ──────────────────────────────────────────────

    /**
     * Build the tools array for initialize / tools/list responses.
     *
     * @return list<array{name: string, description: string, inputSchema: array}>
     */
    private function buildToolList(): array
    {
        $list = [];

        foreach ($this->tools as $name => $tool) {
            $list[] = [
                'name'        => $name,
                'description' => $tool['description'],
                'inputSchema' => $tool['inputSchema'],
            ];
        }

        return $list;
    }

    /**
     * Send a JSON-RPC success response to stdout.
     *
     * @param mixed $id     Request id
     * @param mixed $result Result payload
     */
    private function sendResponse(mixed $id, mixed $result): void
    {
        $payload = json_encode(
            ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        fwrite(STDOUT, $payload . "\n");
        fflush(STDOUT);
    }

    /**
     * Send a JSON-RPC error response to stdout.
     *
     * @param mixed  $id      Request id
     * @param int    $code    Error code
     * @param string $message Error message
     */
    private function sendError(mixed $id, int $code, string $message): void
    {
        $payload = json_encode(
            [
                'jsonrpc' => '2.0',
                'id'      => $id,
                'error'   => ['code' => $code, 'message' => $message],
            ],
            JSON_UNESCAPED_UNICODE,
        );

        fwrite(STDOUT, $payload . "\n");
        fflush(STDOUT);
    }
}
