<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Mcp\Server;

use PhpGen\ClassGenerator\Console\Commands\McpServerCommand;
use PhpGen\ClassGenerator\Mcp\Adapters\PhpGenConfigLoader;
use PhpGen\ClassGenerator\Mcp\Adapters\SymfonyCommandAdapter;
use PhpGen\ClassGenerator\Mcp\CommandDiscovery;
use PhpMcp\Server\Server;
use PhpMcp\Server\Transports\StdioServerTransport;

/**
 * PhpGen MCP Server using php-mcp/server library
 *
 * This server bridges PhpGen's Symfony Console commands with the MCP protocol
 * using the php-mcp/server library as the foundation. It maintains compatibility
 * with the existing CommandDiscovery infrastructure while gaining the benefits
 * of a mature, well-tested MCP server implementation.
 *
 * Key features:
 * - Automatic discovery of Symfony Console commands
 * - Dynamic conversion to MCP tools
 * - Full compatibility with existing PhpGen configuration
 * - Support for stdio transport (HTTP/SSE support can be added later)
 */
final class PhpGenMcpServer
{
    private Server $server;

    /**
     * Create a new PhpGen MCP server
     *
     * @param string|null $configPath Path to phpgen.php configuration file
     * @return self The server instance
     */
    public static function create(?string $configPath = null): self
    {
        $instance = new self();
        $instance->initialize($configPath);
        return $instance;
    }

    /**
     * Initialize the MCP server
     *
     * @param string|null $configPath Path to phpgen.php configuration file
     */
    private function initialize(?string $configPath): void
    {
        // Load PhpGen configuration
        $configLoader = new PhpGenConfigLoader();
        $config = $configLoader->load($configPath);

        // Create adapters
        $discovery = new CommandDiscovery();
        $adapter = new SymfonyCommandAdapter($discovery);

        // Build the php-mcp/server instance
        $builder = Server::make()
            ->withServerInfo(
                name: 'PhpGen Class Generator',
                version: '0.0.2'
            );

        // Discover and register all commands as MCP tools
        $commands = $discovery->discoverCommands($config);

        foreach ($commands as $command) {
            $toolName = $adapter->getToolName($command);
            if ($toolName === '') {
                continue;
            }

            // Register each command as an MCP tool
            $builder->withTool(
                handler: $adapter->createToolHandler($command),
                name: $toolName,
                description: $command->getDescription() ?: 'No description available',
                inputSchema: $adapter->generateSchema($command)
            );
        }

        $this->server = $builder->build();
    }

    /**
     * Start the MCP server in stdio mode
     *
     * This method will block indefinitely, processing JSON-RPC requests
     * from stdin and sending responses to stdout.
     */
    public function listen(): void
    {
        $transport = new StdioServerTransport();
        $this->server->listen($transport);
    }

    /**
     * Get the underlying php-mcp/server Server instance
     *
     * This allows advanced usage and testing scenarios.
     *
     * @return Server The server instance
     */
    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * Process a single JSON-RPC request (for testing/debugging)
     *
     * @param string $request JSON-RPC request string
     * @return string JSON-RPC response string
     */
    public function processRequest(string $request): string
    {
        // This is a helper method for testing
        // The actual request processing is handled by the transport layer
        // when listen() is called

        // For now, we'll throw an exception as this needs the transport layer
        throw new \RuntimeException(
            'Direct request processing is not supported. Use listen() method instead.'
        );
    }
}
