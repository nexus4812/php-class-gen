#!/usr/bin/env php
<?php

declare(strict_types=1);

// Autoload dependencies
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../autoload.php',
    __DIR__ . '/../../autoload.php',
    __DIR__ . '/../../../autoload.php'
];

foreach ($autoloadPaths as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
        break;
    }
}

use PhpGen\ClassGenerator\Mcp\McpServer;

/**
 * PhpGen MCP Server
 *
 * This server exposes PhpGen commands as MCP tools for Claude Code integration.
 *
 * Usage:
 *   php mcp-phpgen-server.php
 *
 * The server reads JSON-RPC 2.0 requests from stdin and writes responses to stdout.
 *
 * To use with Claude Desktop, add this to your claude_desktop_config.json:
 *
 * {
 *   "mcpServers": {
 *     "phpgen": {
 *       "command": "php",
 *       "args": ["/path/to/your/project/mcp-phpgen-server.php"]
 *     }
 *   }
 * }
 */

try {
    // Determine config file path from command line argument or auto-detect
    $configPath = $argv[1] ?? null;

    // Create and start the MCP server
    $server = McpServer::create($configPath);
    $server->start();

} catch (Throwable $e) {
    // Write error to stderr
    fwrite(STDERR, "Error starting MCP server: " . $e->getMessage() . "\n");
    fwrite(STDERR, $e->getTraceAsString() . "\n");
    exit(1);
}