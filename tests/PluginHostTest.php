<?php

declare(strict_types=1);

use Graphify\Sdk\Plugin\PluginHost;

/**
 * PluginHost test: JSON-RPC stdio host for Graphify plugins.
 */
final class PluginHostTest extends \PHPUnit\Framework\TestCase
{
    // ─── initialize ───────────────────────────────────────────────

    public function testInitializeEmpty(): void
    {
        $host = new PluginHost();
        $result = $host->handleInitialize();

        $this->assertSame('2024-11-05', $result['protocolVersion']);
        $this->assertArrayHasKey('capabilities', $result);
        $this->assertArrayHasKey('tools', $result);
        $this->assertCount(0, $result['tools']);
    }

    public function testInitializeWithTools(): void
    {
        $host = $this->createHostWithTools();
        $result = $host->handleInitialize();

        $this->assertCount(2, $result['tools']);
        $names = array_column($result['tools'], 'name');
        $this->assertContains('analyze_schema', $names);
        $this->assertContains('trace_column', $names);
    }

    // ─── tools/list ───────────────────────────────────────────────

    public function testToolsListEmpty(): void
    {
        $host = new PluginHost();
        $result = $host->handleToolsList();

        $this->assertArrayHasKey('tools', $result);
        $this->assertCount(0, $result['tools']);
    }

    public function testToolsListWithTools(): void
    {
        $host = $this->createHostWithTools();
        $result = $host->handleToolsList();

        $this->assertCount(2, $result['tools']);
        $names = array_column($result['tools'], 'name');
        $this->assertContains('analyze_schema', $names);
        $this->assertContains('trace_column', $names);
    }

    public function testToolSchemaPreserved(): void
    {
        $host = $this->createHostWithTools();
        $result = $host->handleToolsList();

        $schema = array_values(array_filter($result['tools'], fn($t) => $t['name'] === 'analyze_schema'))[0];

        $this->assertSame('Analyze Laravel database schema', $schema['description']);
        $this->assertArrayHasKey('inputSchema', $schema);
        $this->assertSame('object', $schema['inputSchema']['type']);
        $this->assertArrayHasKey('migration_path', $schema['inputSchema']['properties']);
    }

    // ─── tools/call ───────────────────────────────────────────────

    public function testToolsCallDispatchesHandler(): void
    {
        $host = $this->createHostWithTools();
        $result = $host->handleToolsCall([
            'name' => 'analyze_schema',
            'arguments' => ['migration_path' => '/app/migrations'],
        ]);

        $this->assertArrayHasKey('content', $result);
        $this->assertCount(1, $result['content']);
        $this->assertSame('text', $result['content'][0]['type']);

        $text = json_decode($result['content'][0]['text'], true);
        $this->assertIsArray($text);
        $this->assertSame('/app/migrations', $text['migration_path']);
    }

    public function testToolsCallPassesArguments(): void
    {
        $host = $this->createHostWithTools();
        $result = $host->handleToolsCall([
            'name' => 'trace_column',
            'arguments' => ['table' => 'users', 'column' => 'status'],
        ]);

        $text = json_decode($result['content'][0]['text'], true);
        $this->assertSame('users', $text['table']);
        $this->assertSame('status', $text['column']);
    }

    public function testToolsCallUnknownTool(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tool not found: nonexistent');

        $host = $this->createHostWithTools();
        $host->handleToolsCall(['name' => 'nonexistent', 'arguments' => []]);
    }

    // ─── handler errors ───────────────────────────────────────────

    public function testHandlerExceptionPropagates(): void
    {
        $host = new PluginHost();
        $host->registerTool('failing_tool', [
            'description' => 'Always fails',
            'inputSchema' => ['type' => 'object', 'properties' => []],
        ], function (array $args): array {
            throw new \RuntimeException('Handler crashed: invalid data');
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Handler crashed');

        $host->handleToolsCall(['name' => 'failing_tool', 'arguments' => []]);
    }

    // ─── registerTool ─────────────────────────────────────────────

    public function testRegisterToolReturnsSelf(): void
    {
        $host = new PluginHost();
        $result = $host->registerTool('test', [
            'description' => 'Test tool',
            'inputSchema' => ['type' => 'object', 'properties' => []],
        ], fn(array $args): array => $args);

        $this->assertSame($host, $result);
    }

    public function testRegisterToolDefaults(): void
    {
        $host = new PluginHost();
        $host->registerTool('minimal', [], fn(array $args): array => $args);

        $tools = $host->handleToolsList()['tools'];
        $this->assertCount(1, $tools);
        $this->assertSame('minimal', $tools[0]['name']);
        $this->assertSame('', $tools[0]['description']);
        $this->assertArrayHasKey('inputSchema', $tools[0]);
    }

    // ─── buildToolList ────────────────────────────────────────────

    public function testBuildToolListFormat(): void
    {
        $host = $this->createHostWithTools();
        $tools = $host->handleToolsList()['tools'];

        foreach ($tools as $tool) {
            $this->assertArrayHasKey('name', $tool);
            $this->assertArrayHasKey('description', $tool);
            $this->assertArrayHasKey('inputSchema', $tool);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function createHostWithTools(): PluginHost
    {
        $host = new PluginHost();

        $host->registerTool('analyze_schema', [
            'description' => 'Analyze Laravel database schema',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'migration_path' => ['type' => 'string'],
                ],
                'required' => ['migration_path'],
            ],
        ], function (array $args): array {
            return ['migration_path' => $args['migration_path'] ?? ''];
        });

        $host->registerTool('trace_column', [
            'description' => 'Trace column dependencies across models',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'table'  => ['type' => 'string'],
                    'column' => ['type' => 'string'],
                ],
                'required' => ['table', 'column'],
            ],
        ], function (array $args): array {
            return ['table' => $args['table'] ?? '', 'column' => $args['column'] ?? ''];
        });

        return $host;
    }
}
