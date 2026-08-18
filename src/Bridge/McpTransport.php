<?php

declare(strict_types=1);

namespace Graphify\Sdk\Bridge;

use Graphify\Sdk\Exception\EngineException;
use Graphify\Sdk\Exception\ProtocolException;
use Graphify\Sdk\Exception\TransportException;

/**
 * Stdio/JSON-RPC transport for communicating with graphify-mcp.
 *
 * Manages the lifecycle of a graphify-mcp subprocess and provides
 * JSON-RPC 2.0 request/response over stdin/stdout pipes.
 */
final class McpTransport
{
    /** @var resource|null */
    private $process = null;

    /** @var resource|null */
    private $stdin = null;

    /** @var resource|null */
    private $stdout = null;

    /** @var resource|null */
    private $stderr = null;

    private bool $started = false;
    private int $requestId = 0;
    private float $timeout;

    /**
     * @param string      $binaryPath Path to the graphify-mcp binary
     * @param float       $timeout    I/O timeout in seconds (default 30.0)
     * @param string|null $cwd        Working directory for the process (optional)
     */
    public function __construct(
        public readonly string $binaryPath = 'graphify-mcp',
        float $timeout = 30.0,
        private readonly ?string $cwd = null,
    ) {
        $this->timeout = max(1.0, $timeout);
    }

    /**
     * Start the graphify-mcp subprocess.
     *
     * @throws TransportException if the process cannot be spawned
     */
    public function start(): void
    {
        if ($this->started) {
            return;
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        $this->process = @proc_open(
            $this->binaryPath,
            $descriptorSpec,
            $pipes,
            $this->cwd,
        );

        if (!is_resource($this->process)) {
            throw new TransportException(
                "Failed to spawn graphify-mcp process: '{$this->binaryPath}'"
            );
        }

        [$this->stdin, $this->stdout, $this->stderr] = $pipes;

        // Set all streams to non-blocking for timeout handling
        foreach ([$this->stdin, $this->stdout, $this->stderr] as $pipe) {
            if (is_resource($pipe)) {
                stream_set_blocking($pipe, false);
            }
        }

        $this->started = true;
    }

    /**
     * Send a JSON-RPC 2.0 request and return the decoded response.
     *
     * @param  string               $method     The MCP tool name
     * @param  array<string, mixed> $arguments  Tool arguments
     * @return array<string, mixed>             Decoded JSON response
     * @throws TransportException   If I/O fails
     * @throws ProtocolException    If the JSON-RPC response is malformed
     * @throws EngineException      If graphify-mcp returns an error
     */
    public function sendRequest(string $method, array $arguments = []): array
    {
        $this->start();

        $id = ++$this->requestId;

        $request = json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => 'tools/call',
            'params' => [
                'name' => $method,
                'arguments' => $arguments,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($request === false) {
            throw new ProtocolException('Failed to encode JSON-RPC request');
        }

        // Write request to stdin
        $written = @fwrite($this->stdin, $request . "\n");
        if ($written === false) {
            throw new TransportException('Failed to write to graphify-mcp stdin');
        }

        // Read response from stdout
        $response = $this->readResponse();

        return $response;
    }

    /**
     * Read a JSON-RPC response from stdout with timeout.
     *
     * @return array<string, mixed>
     * @throws TransportException
     * @throws ProtocolException
     * @throws EngineException
     */
    private function readResponse(): array
    {
        $buffer = '';
        $startTime = microtime(true);

        while (true) {
            // Check timeout
            if ((microtime(true) - $startTime) > $this->timeout) {
                throw new TransportException(
                    "Timeout reading response from graphify-mcp after {$this->timeout}s"
                );
            }

            $chunk = @fread($this->stdout, 8192);

            if ($chunk === false) {
                throw new TransportException('Failed to read from graphify-mcp stdout');
            }

            if ($chunk !== '') {
                $buffer .= $chunk;

                // Try to parse a complete JSON response
                $response = $this->tryParseResponse($buffer);
                if ($response !== null) {
                    return $response;
                }
            } else {
                // No data available yet — sleep briefly
                usleep(10_000); // 10ms
            }
        }
    }

    /**
     * Try to parse a complete JSON-RPC response from the buffer.
     *
     * @param  string $buffer Raw response buffer
     * @return array<string, mixed>|null Parsed response, or null if incomplete
     * @throws ProtocolException
     * @throws EngineException
     */
    private function tryParseResponse(string $buffer): ?array
    {
        // Find JSON boundaries (newline-delimited JSON)
        $newlinePos = strpos($buffer, "\n");
        if ($newlinePos === false) {
            return null; // Wait for more data
        }

        $line = substr($buffer, 0, $newlinePos);
        $remaining = substr($buffer, $newlinePos + 1);

        $decoded = json_decode($line, true);

        if (!is_array($decoded)) {
            throw new ProtocolException(
                'Failed to parse JSON-RPC response: ' . json_last_error_msg()
            );
        }

        // Check for JSON-RPC error
        if (isset($decoded['error'])) {
            $error = $decoded['error'];
            $message = (string) ($error['message'] ?? 'Unknown engine error');
            $code = (int) ($error['code'] ?? 0);
            throw new EngineException($message, $code, $error);
        }

        // Validate result structure
        if (!isset($decoded['result'])) {
            throw new ProtocolException('JSON-RPC response missing "result" field');
        }

        $result = $decoded['result'];

        // MCP content array: extract text from content items
        if (isset($result['content']) && is_array($result['content'])) {
            $texts = [];
            foreach ($result['content'] as $contentItem) {
                if (isset($contentItem['text'])) {
                    $texts[] = $contentItem['text'];
                }
            }

            // Try to parse combined text as JSON
            $combined = implode('', $texts);
            $parsed = json_decode($combined, true);

            if (is_array($parsed)) {
                return $parsed;
            }

            // Return raw text result
            return ['text' => $combined];
        }

        return $result;
    }

    /**
     * Stop the graphify-mcp process and clean up pipes.
     */
    public function stop(): void
    {
        if (!$this->started) {
            return;
        }

        $this->closePipe($this->stdin);
        $this->closePipe($this->stdout);
        $this->closePipe($this->stderr);

        if (is_resource($this->process)) {
            proc_terminate($this->process, 15); // SIGTERM
            $status = proc_get_status($this->process);
            if ($status !== false && $status['running']) {
                usleep(100_000); // 100ms
                proc_terminate($this->process, 9); // SIGKILL
            }
            proc_close($this->process);
        }

        $this->process = null;
        $this->stdin = null;
        $this->stdout = null;
        $this->stderr = null;
        $this->started = false;
    }

    /**
     * Cleanup on destruct.
     */
    public function __destruct()
    {
        $this->stop();
    }

    /**
     * @param resource|null $pipe
     */
    private function closePipe(mixed $pipe): void
    {
        if (is_resource($pipe)) {
            @fclose($pipe);
        }
    }

    public function isRunning(): bool
    {
        if (!$this->started || !is_resource($this->process)) {
            return false;
        }

        $status = @proc_get_status($this->process);
        return $status !== false && $status['running'];
    }
}
