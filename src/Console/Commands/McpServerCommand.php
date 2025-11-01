<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Console\Commands;

use PhpGen\ClassGenerator\Mcp\Server\PhpGenMcpServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * MCP Server Command (using php-mcp/server)
 *
 * PhpGen's MCP server that uses the php-mcp/server library as its foundation
 * while maintaining full compatibility with PhpGen's existing Symfony Console
 * command infrastructure.
 *
 * Features:
 * - Built on a mature, well-tested MCP server library
 * - Automatic protocol updates and bug fixes
 * - Better error handling and validation
 * - Easy to extend with HTTP/SSE support in the future
 * - Full compatibility with existing CommandDiscovery logic
 *
 * @example
 * ./bin/php-gen mcp:server
 * ./bin/php-gen mcp:server --config=/path/to/phpgen.php
 */
#[AsCommand(
    name: 'mcp:server',
    description: 'Start the PhpGen MCP server for Claude Code integration'
)]
class McpServerCommand extends SymfonyCommand
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'config-path',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Path to phpgen.php configuration file',
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get configuration path
        $configPath = $input->getOption('config-path');
        if (!is_string($configPath) && $configPath !== null) {
            $io->error('Config path must be a string');
            return SymfonyCommand::FAILURE;
        }

        try {
            // Display startup info to stderr (stdout is used for JSON-RPC)
            $this->displayStartupInfo($configPath);

            // Create and start the MCP server
            $server = PhpGenMcpServer::create($configPath);
            $server->listen();

        } catch (Throwable $e) {
            // Errors go to stderr
            fwrite(STDERR, "\n[ERROR] Failed to start MCP server:\n");
            fwrite(STDERR, $e->getMessage() . "\n");

            if ($output->isVerbose()) {
                fwrite(STDERR, "\nStack trace:\n");
                fwrite(STDERR, $e->getTraceAsString() . "\n");
            }

            return SymfonyCommand::FAILURE;
        }

        // This line should never be reached as the server blocks
        return SymfonyCommand::SUCCESS;
    }

    /**
     * Display startup information to stderr (not stdout which is used for JSON-RPC)
     */
    private function displayStartupInfo(?string $configPath): void
    {
        fwrite(STDERR, "╔══════════════════════════════════════════════════════╗\n");
        fwrite(STDERR, "║  PhpGen MCP Server (powered by php-mcp/server)     ║\n");
        fwrite(STDERR, "╚══════════════════════════════════════════════════════╝\n");
        fwrite(STDERR, "\n");
        fwrite(STDERR, "Version:  0.0.2\n");
        fwrite(STDERR, "Protocol: JSON-RPC 2.0 over stdio\n");
        fwrite(STDERR, "Config:   " . ($configPath ?? 'Auto-detected') . "\n");
        fwrite(STDERR, "\n");
        fwrite(STDERR, "Server is ready to receive MCP requests...\n");
        fwrite(STDERR, "\n");
    }
}
