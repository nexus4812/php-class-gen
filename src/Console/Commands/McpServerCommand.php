<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Console\Commands;

use PhpGen\ClassGenerator\Config\PhpGenConfig;
use PhpGen\ClassGenerator\Core\Project;
use PhpGen\ClassGenerator\Mcp\McpServer;
use Symfony\Component\Console\Attribute\AsCommand;
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
    protected function configureCommand(): void
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

    protected function handle(InputInterface $input, OutputInterface $output): Project
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
                return new Project(); // Return empty project
            }

            // Display startup information
            $this->displayStartupInfo($io, $configPath);

            // Check port option (for future HTTP support)
            $port = $input->getOption('port');
            if ($port !== null) {
                $io->warning('HTTP mode is not yet implemented. Starting in stdio mode.');
            }

            // Start the server in stdio mode
            $io->info('Starting MCP server in stdio mode...');
            $io->comment('The server will read JSON-RPC requests from stdin and write responses to stdout.');
            $io->comment('Press Ctrl+C to stop the server.');
            $io->newLine();

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

            throw $e;
        }

        // This line should never be reached as the server blocks
        return new Project();
    }

    /**
     * Display startup information
     */
    private function displayStartupInfo(SymfonyStyle $io, ?string $configPath): void
    {
        $io->title('PhpGen MCP Server');

        $info = [
            ['Config File', $configPath ?? 'Auto-detected'],
            ['Protocol', 'JSON-RPC 2.0 over stdio'],
            ['Mode', 'MCP (Model Context Protocol)'],
        ];

        $io->table(['Setting', 'Value'], $info);
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

        if (!isset($data['result']['tools']) || !is_array($data['result']['tools'])) {
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
            $io->section($tool['name']);
            $io->writeln("<info>Description:</info> {$tool['description']}");

            if (isset($tool['inputSchema']['properties'])) {
                $io->writeln('<info>Parameters:</info>');

                $required = $tool['inputSchema']['required'] ?? [];

                foreach ($tool['inputSchema']['properties'] as $param => $schema) {
                    $type = $schema['type'] ?? 'unknown';
                    $desc = $schema['description'] ?? 'No description';
                    $isRequired = in_array($param, $required, true) ? ' (required)' : ' (optional)';

                    $io->writeln("  • <comment>{$param}</comment> ({$type}){$isRequired}: {$desc}");
                }
            }

            $io->newLine();
        }

        $io->note('Use these tools in Claude Code by referencing them with the "mcp__phpgen__" prefix.');
    }
}