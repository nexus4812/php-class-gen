<?php

declare(strict_types=1);

use PhpGen\ClassGenerator\Config\PhpGenConfig;
use PhpGen\ClassGenerator\Console\Commands\CreateDtoCommand;
use PhpGen\ClassGenerator\Console\Commands\LaravelCqrsQueryCommand;
use PhpGen\ClassGenerator\Console\Commands\McpServerCommand;

/**
 * PhpGen Configuration File
 *
 * This file defines the configuration for the PhpGen code generator.
 * It registers generators and configures PSR-4 mappings for your project.
 */
return PhpGenConfig::configure()
    ->withCommands([
        LaravelCqrsQueryCommand::class,
        CreateDtoCommand::class,
        McpServerCommand::class,  // Default: MCP server for Claude Code integration
    ])
    ->withComposerAutoload() // Automatically loads PSR-4 mappings from composer.json
    ->withPsr4Mapping('App\\', 'app') // Additional custom mapping (overrides composer.json if needed)
    ->withPriorityPsr4Mapping('Tests\\', 'tests') // Priority mapping to override composer.json mapping
    ->withStrictTypes(true);
