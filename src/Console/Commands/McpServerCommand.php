<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Console\Commands;

use PhpGen\ClassGenerator\Mcp\McpServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * MCP Server Command
 *
 * Starts the PhpGen MCP server that exposes all configured commands
 * as MCP tools for Claude Code integration.
 *
 * @example
 * ./bin/php-gen mcp:server
 * ./bin/php-gen mcp:server --port=8080
 * ./bin/php-gen mcp:server --config=/path/to/phpgen.php
 */
#[AsCommand(
    name: 'mcp:server',
    description: 'Start the PhpGen MCP server for Claude Code integration'
)]
class McpServerCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'port',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Port to listen on (for HTTP mode, currently stdio only)',
                null
            )
            ->addOption(
                'config-path',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Path to phpgen.php configuration file',
                null
            )
            ->addOption(
                'list-tools',
                'l',
                InputOption::VALUE_NONE,
                'List available MCP tools and exit'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get configuration path
        $configPath = $input->getOption('config-path');
        if (!is_string($configPath) && $configPath !== null) {
            throw new \InvalidArgumentException('Config path must be a string');
        }

        try {
            // Create MCP server
            $server = McpServer::create($configPath);

            // Check if user wants to list tools
            if ($input->getOption('list-tools')) {
                $this->listAvailableTools($server, $io);
                return Command::SUCCESS;
            }

            // For MCP server mode, suppress all output to stdout
            // since stdout is used for JSON-RPC communication
            // Only show startup info to stderr
            $this->displayStartupInfoToStderr($configPath);

            // Check port option (for future HTTP support)
            $port = $input->getOption('port');
            if ($port !== null) {
                fwrite(STDERR, "Warning: HTTP mode is not yet implemented. Starting in stdio mode.\n");
            }

            // Start the server (this will block)
            $server->start();

        } catch (\Throwable $e) {
            $io->error([
                'Failed to start MCP server:',
                $e->getMessage()
            ]);

            if ($output->isVerbose()) {
                $io->writeln('<comment>Stack trace:</comment>');
                $io->writeln($e->getTraceAsString());
            }

            return Command::FAILURE;
        }

        // This line should never be reached as the server blocks
        return Command::SUCCESS;
    }

    /**
     * Display startup information to stderr (not stdout which is used for JSON-RPC)
     */
    private function displayStartupInfoToStderr(?string $configPath): void
    {
        fwrite(STDERR, "PhpGen MCP Server starting...\n");
        fwrite(STDERR, "Config: " . ($configPath ?? 'Auto-detected') . "\n");
        fwrite(STDERR, "Protocol: JSON-RPC 2.0 over stdio\n");
        fwrite(STDERR, "Ready to receive requests.\n\n");
    }

    /**
     * List available MCP tools
     */
    private function listAvailableTools(McpServer $server, SymfonyStyle $io): void
    {
        $io->title('Available MCP Tools');

        // Get tools by simulating the tools/list request
        $response = $server->processRequest('{"jsonrpc":"2.0","method":"tools/list","params":{},"id":1}');
        $data = json_decode($response, true);

        if (!is_array($data) || !isset($data['result']) || !is_array($data['result']) || !isset($data['result']['tools']) || !is_array($data['result']['tools'])) {
            $io->warning('No tools available or unable to retrieve tools list.');
            return;
        }

        $tools = $data['result']['tools'];

        if (empty($tools)) {
            $io->warning('No MCP tools are currently configured.');
            $io->note('Add commands to your phpgen.php configuration file to expose them as MCP tools.');
            return;
        }

        $io->success(sprintf('Found %d MCP tool(s):', count($tools)));

        foreach ($tools as $tool) {
            if (!is_array($tool) || !isset($tool['name']) || !is_string($tool['name'])) {
                continue;
            }

            $io->section($tool['name']);

            $description = $tool['description'] ?? 'No description';
            if (is_string($description)) {
                $io->writeln("<info>Description:</info> {$description}");
            }

            if (isset($tool['inputSchema']) && is_array($tool['inputSchema']) && isset($tool['inputSchema']['properties']) && is_array($tool['inputSchema']['properties'])) {
                $io->writeln('<info>Parameters:</info>');

                $required = [];
                if (isset($tool['inputSchema']['required']) && is_array($tool['inputSchema']['required'])) {
                    $required = $tool['inputSchema']['required'];
                }

                foreach ($tool['inputSchema']['properties'] as $param => $schema) {
                    if (!is_string($param) || !is_array($schema)) {
                        continue;
                    }

                    $type = is_string($schema['type'] ?? null) ? $schema['type'] : 'unknown';
                    $desc = is_string($schema['description'] ?? null) ? $schema['description'] : 'No description';
                    $isRequired = in_array($param, $required, true) ? ' (required)' : ' (optional)';

                    $io->writeln("  • <comment>{$param}</comment> ({$type}){$isRequired}: {$desc}");
                }
            }

            $io->newLine();
        }

        $io->note('Use these tools in Claude Code by referencing them with the "mcp__phpgen__" prefix.');
    }
}