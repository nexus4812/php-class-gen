<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Mcp;

use PhpGen\ClassGenerator\Config\PhpGenConfig;
use Throwable;

/**
 * MCP Server for PhpGen commands
 *
 * This server automatically discovers PhpGen commands and exposes them
 * as MCP tools that can be called by Claude Code.
 */
final class McpServer
{
    /** @var array<string, McpTool> */
    private array $tools = [];

    public function __construct(
        private readonly PhpGenConfig $config,
        private readonly CommandDiscovery $discovery,
        private readonly McpToolFactory $toolFactory
    ) {
        $this->initializeTools();
    }

    /**
     * Create server with default configuration
     *
     * @param string|null $configPath Path to phpgen.php config file
     * @return self
     */
    public static function create(?string $configPath = null): self
    {
        // Load PhpGen configuration
        $configFile = $configPath ?? self::findPhpGenConfigFile();
        if ($configFile === null || !file_exists($configFile)) {
            throw new \InvalidArgumentException(
                'PhpGen configuration file not found. Please create a phpgen.php file in your project root.'
            );
        }

        $config = require $configFile;
        if (!$config instanceof PhpGenConfig) {
            throw new \InvalidArgumentException(
                'Configuration file must return a PhpGenConfig instance.'
            );
        }

        $discovery = new CommandDiscovery();
        $toolFactory = new McpToolFactory($discovery);

        return new self($config, $discovery, $toolFactory);
    }

    /**
     * Start the MCP server (stdio mode)
     */
    public function start(): void
    {
        $buffer = '';

        while (($line = fgets(STDIN)) !== false) {
            $buffer .= $line;

            // Try to decode as JSON - if successful, process the request
            $trimmed = trim($buffer);
            if (empty($trimmed)) {
                continue;
            }

            try {
                $request = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($request) && isset($request['jsonrpc'])) {
                    $response = $this->processRequest($trimmed);
                    echo $response . "\n";
                    flush();
                    $buffer = ''; // Reset buffer after successful processing
                }
            } catch (\JsonException $e) {
                // Continue reading if JSON is incomplete
                continue;
            }
        }
    }

    /**
     * Process a JSON-RPC request
     *
     * @param string $input JSON-RPC request
     * @return string JSON-RPC response
     */
    public function processRequest(string $input): string
    {
        try {
            $request = json_decode($input, true, 512, JSON_THROW_ON_ERROR);

            if (!$this->isValidRequest($request)) {
                return json_encode($this->createErrorResponse(null, -32600, 'Invalid Request'), JSON_THROW_ON_ERROR);
            }

            /** @var array<string, mixed> $request */
            $method = $request['method'];
            $params = $request['params'] ?? [];
            $id = $request['id'] ?? null;

            // Ensure params is an array with string keys
            /** @var array<string, mixed> $params */
            $params = is_array($params) ? $params : [];

            $response = match ($method) {
                'initialize' => $this->handleInitialize($params),
                'tools/list' => $this->handleListTools(),
                'tools/call' => $this->handleCallTool($params),
                default => $this->createErrorResponse($id, -32601, 'Method not found')
            };

            if ($id !== null) {
                $response['id'] = $id;
            }

            return json_encode($response, JSON_THROW_ON_ERROR);

        } catch (Throwable $e) {
            return json_encode($this->createErrorResponse(null, -32603, 'Internal error: ' . $e->getMessage()), JSON_THROW_ON_ERROR);
        }
    }

    /**
     * Initialize tools from discovered commands
     */
    private function initializeTools(): void
    {
        $commands = $this->discovery->discoverCommands($this->config);

        foreach ($commands as $command) {
            $tool = $this->toolFactory->createToolFromCommand($command);
            $this->tools[$tool->name] = $tool;
        }
    }

    /**
     * Handle initialize method
     *
     * @param array<string, mixed> $params Parameters
     * @return array<string, mixed> Response
     */
    private function handleInitialize(array $params): array
    {
        return [
            'jsonrpc' => '2.0',
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => (object)[]  // Empty object indicates tools capability is supported
                ],
                'serverInfo' => [
                    'name' => 'phpgen-mcp-server',
                    'version' => '1.0.0',
                    'description' => 'MCP server for PhpGen code generation tools'
                ]
            ]
        ];
    }

    /**
     * Handle tools/list method
     *
     * @return array<string, mixed> Response
     */
    private function handleListTools(): array
    {
        $tools = [];
        foreach ($this->tools as $tool) {
            $tools[] = $tool->getSchema();
        }

        return [
            'jsonrpc' => '2.0',
            'result' => [
                'tools' => $tools
            ]
        ];
    }

    /**
     * Handle tools/call method
     *
     * @param array<string, mixed> $params Parameters
     * @return array<string, mixed> Response
     */
    private function handleCallTool(array $params): array
    {
        $toolName = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (!is_string($toolName)) {
            return $this->createErrorResponse(null, -32602, 'Invalid tool name');
        }

        if (!is_array($arguments)) {
            $arguments = [];
        }
        /** @var array<string, mixed> $arguments */

        if (!isset($this->tools[$toolName])) {
            return $this->createErrorResponse(null, -32602, "Tool '{$toolName}' not found");
        }

        try {
            $tool = $this->tools[$toolName];
            $result = $tool->execute($arguments);

            return [
                'jsonrpc' => '2.0',
                'result' => $result->toMcpResponse()
            ];

        } catch (Throwable $e) {
            return $this->createErrorResponse(null, -32603, 'Tool execution failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate JSON-RPC request structure
     *
     * @param mixed $request Decoded request
     * @return bool Whether the request is valid
     */
    private function isValidRequest(mixed $request): bool
    {
        return is_array($request) &&
               isset($request['jsonrpc']) &&
               $request['jsonrpc'] === '2.0' &&
               isset($request['method']) &&
               is_string($request['method']);
    }

    /**
     * Create error response
     *
     * @param mixed $id Request ID
     * @param int $code Error code
     * @param string $message Error message
     * @return array<string, mixed> Error response
     */
    private function createErrorResponse(mixed $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => $code,
                'message' => $message
            ],
            'id' => $id
        ];
    }

    /**
     * Find phpgen.php configuration file in common locations
     *
     * @return string|null Path to config file or null if not found
     */
    private static function findPhpGenConfigFile(): ?string
    {
        $homeDir = $_SERVER['HOME'] ?? null;

        $possiblePaths = [
            getcwd() . '/phpgen.php',
            getcwd() . '/.phpgen.php',
            __DIR__ . '/../../../phpgen.php',
        ];

        if (is_string($homeDir)) {
            $possiblePaths[] = $homeDir . '/.phpgen/phpgen.php';
        }

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
