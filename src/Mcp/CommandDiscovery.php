<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Mcp;

use PhpGen\ClassGenerator\Config\PhpGenConfig;
use PhpGen\ClassGenerator\Console\Commands\Command;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * Discovers and analyzes PhpGen commands for MCP integration
 *
 * This class automatically discovers commands from PhpGenConfig and extracts
 * their metadata for dynamic MCP tool generation.
 */
final class CommandDiscovery
{
    /**
     * Discover all commands from the configuration
     *
     * @param PhpGenConfig $config The configuration containing command definitions
     * @return array<string, Command> Array of command instances keyed by command name
     * @throws ReflectionException If command class cannot be reflected
     */
    public function discoverCommands(PhpGenConfig $config): array
    {
        $commands = [];

        foreach ($config->getCommands() as $commandClass) {
            if (!class_exists($commandClass)) {
                continue;
            }

            // Skip MCP server command itself
            if ($commandClass === \PhpGen\ClassGenerator\Console\Commands\McpServerCommand::class) {
                continue;
            }

            $command = new $commandClass($config);

            if (!$command instanceof Command) {
                continue;
            }

            $commandName = $command->getName();
            if ($commandName !== null) {
                $commands[$commandName] = $command;
            }
        }

        return $commands;
    }

    /**
     * Extract metadata from a command for MCP tool generation
     *
     * @param Command $command The command to analyze
     * @return CommandMetadata Metadata structure for the command
     * @throws ReflectionException If command class cannot be reflected
     */
    public function extractCommandMetadata(Command $command): CommandMetadata
    {
        $reflection = new ReflectionClass($command);

        // Ensure the command is fully configured by using a mock application
        $app = new \Symfony\Component\Console\Application();
        $app->add($command);
        $definition = $command->getDefinition();

        return new CommandMetadata(
            name: $command->getName() ?? '',
            description: $command->getDescription(),
            arguments: $this->extractArguments($definition),
            options: $this->extractOptions($definition),
            className: $reflection->getName(),
            namespace: $reflection->getNamespaceName()
        );
    }

    /**
     * Generate MCP tool schema from command metadata
     *
     * @param CommandMetadata $metadata Command metadata
     * @return array<string, mixed> MCP tool schema
     */
    public function generateMcpSchema(CommandMetadata $metadata): array
    {
        $properties = [];
        $required = [];

        // Add arguments as required properties
        foreach ($metadata->arguments as $argument) {
            $paramName = $this->camelCase($argument->name);
            $properties[$paramName] = [
                'type' => $this->mapArgumentType($argument),
                'description' => $argument->description ?? "Argument: {$argument->name}"
            ];

            if ($argument->isRequired) {
                $required[] = $paramName;
            }
        }

        // Add options as optional properties
        foreach ($metadata->options as $option) {
            $properties[$this->camelCase($option->name)] = [
                'type' => $this->mapOptionType($option),
                'description' => $option->description ?? "Option: {$option->name}",
                'default' => $option->default
            ];
        }

        // Always add dryRun option
        $properties['dryRun'] = [
            'type' => 'boolean',
            'description' => 'Preview generated files without writing them',
            'default' => false
        ];

        return [
            'name' => 'phpgen_' . str_replace(':', '_', $metadata->name),
            'description' => $metadata->description,
            'inputSchema' => [
                'type' => 'object',
                'properties' => $properties,
                'required' => $required
            ]
        ];
    }

    /**
     * Extract arguments from command definition
     *
     * @param InputDefinition $definition Command definition
     * @return array<ArgumentMetadata> Array of argument metadata
     */
    private function extractArguments(InputDefinition $definition): array
    {
        $arguments = [];

        foreach ($definition->getArguments() as $argument) {
            $arguments[] = new ArgumentMetadata(
                name: $argument->getName(),
                description: $argument->getDescription(),
                isRequired: $argument->isRequired(),
                isArray: $argument->isArray(),
                default: $argument->getDefault()
            );
        }

        return $arguments;
    }

    /**
     * Extract options from command definition
     *
     * @param InputDefinition $definition Command definition
     * @return array<OptionMetadata> Array of option metadata
     */
    private function extractOptions(InputDefinition $definition): array
    {
        $options = [];

        foreach ($definition->getOptions() as $option) {
            // Skip standard Symfony Console options
            if (in_array($option->getName(), ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'silent'], true)) {
                continue;
            }

            $options[] = new OptionMetadata(
                name: $option->getName(),
                description: $option->getDescription(),
                shortcut: $option->getShortcut(),
                acceptValue: $option->acceptValue(),
                isArray: $option->isArray(),
                default: $option->getDefault()
            );
        }

        return $options;
    }

    /**
     * Map argument type for JSON schema
     *
     * @param ArgumentMetadata $argument Argument metadata
     * @return string JSON schema type
     */
    private function mapArgumentType(ArgumentMetadata $argument): string
    {
        if ($argument->isArray) {
            return 'array';
        }

        // Default to string for arguments
        return 'string';
    }

    /**
     * Map option type for JSON schema
     *
     * @param OptionMetadata $option Option metadata
     * @return string JSON schema type
     */
    private function mapOptionType(OptionMetadata $option): string
    {
        if (!$option->acceptValue) {
            return 'boolean';
        }

        if ($option->isArray) {
            return 'array';
        }

        // Determine type from default value
        if ($option->default !== null) {
            return match (gettype($option->default)) {
                'boolean' => 'boolean',
                'integer' => 'integer',
                'double' => 'number',
                default => 'string'
            };
        }

        return 'string';
    }

    /**
     * Convert kebab-case to camelCase
     *
     * @param string $string The string to convert
     * @return string Converted string
     */
    private function camelCase(string $string): string
    {
        return lcfirst(str_replace('-', '', ucwords($string, '-')));
    }
}