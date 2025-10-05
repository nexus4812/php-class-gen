<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Mcp;

use PhpGen\ClassGenerator\Config\PhpGenConfig;
use PhpGen\ClassGenerator\Console\Commands\Command;
use PhpGen\ClassGenerator\Core\Project;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

/**
 * Factory for creating MCP tools from PhpGen commands
 *
 * This class dynamically creates MCP tools that can execute PhpGen commands
 * with proper parameter mapping and validation.
 */
final class McpToolFactory
{
    public function __construct(
        private readonly CommandDiscovery $discovery
    ) {
    }

    /**
     * Create an MCP tool from a command
     *
     * @param Command $command The command to wrap
     * @return McpTool MCP tool instance
     */
    public function createToolFromCommand(Command $command): McpTool
    {
        $metadata = $this->discovery->extractCommandMetadata($command);
        $schema = $this->discovery->generateMcpSchema($metadata);

        /** @var array<string, mixed> $inputSchema */
        $inputSchema = is_array($schema['inputSchema']) ? $schema['inputSchema'] : [];

        return new McpTool(
            name: is_string($schema['name']) ? $schema['name'] : '',
            description: is_string($schema['description']) ? $schema['description'] : '',
            inputSchema: $inputSchema,
            executor: function (array $parameters) use ($command, $metadata): McpToolResult {
                return $this->executeCommand($command, $metadata, $parameters);
            }
        );
    }

    /**
     * Execute a command with the given parameters
     *
     * @param Command $command The command to execute
     * @param CommandMetadata $metadata Command metadata
     * @param array<string, mixed> $parameters Input parameters
     * @return McpToolResult Execution result
     */
    private function executeCommand(Command $command, CommandMetadata $metadata, array $parameters): McpToolResult
    {
        try {
            // Validate parameters
            $validationResult = $this->validateParameters($metadata, $parameters);
            if (!$validationResult->isValid) {
                return McpToolResult::error($validationResult->errorMessage ?? 'Validation failed');
            }

            // Convert parameters to Symfony Console input format
            $input = $this->createInput($metadata, $parameters);
            $output = new BufferedOutput();

            // Check if this is a dry run
            $isDryRun = $parameters['dryRun'] ?? false;

            // Execute the command
            $exitCode = $command->run($input, $output);

            if ($exitCode !== 0) {
                return McpToolResult::error("Command execution failed with exit code: {$exitCode}");
            }

            $result = [
                'command' => $metadata->name,
                'parameters' => $parameters,
                'output' => $output->fetch(),
                'exitCode' => $exitCode,
                'dryRun' => $isDryRun
            ];

            return McpToolResult::success($result);

        } catch (Throwable $e) {
            return McpToolResult::error("Command execution failed: " . $e->getMessage());
        }
    }

    /**
     * Validate input parameters against command metadata
     *
     * @param CommandMetadata $metadata Command metadata
     * @param array<string, mixed> $parameters Input parameters
     * @return ValidationResult Validation result
     */
    private function validateParameters(CommandMetadata $metadata, array $parameters): ValidationResult
    {
        // Check required arguments
        foreach ($metadata->arguments as $argument) {
            $paramName = $this->camelCase($argument->name);
            if ($argument->isRequired && !isset($parameters[$paramName])) {
                return ValidationResult::invalid("Required argument '{$paramName}' is missing");
            }
        }

        // Validate parameter types
        foreach ($parameters as $name => $value) {
            if ($name === 'dryRun') {
                continue; // Skip internal parameter
            }

            // Find corresponding argument or option
            $parameterDef = $this->findParameterDefinition($metadata, $name);
            if ($parameterDef === null) {
                return ValidationResult::invalid("Unknown parameter '{$name}'");
            }

            // Basic type validation
            if ($parameterDef['type'] === 'boolean' && !is_bool($value)) {
                return ValidationResult::invalid("Parameter '{$name}' must be a boolean");
            }

            if ($parameterDef['type'] === 'integer' && !is_int($value)) {
                return ValidationResult::invalid("Parameter '{$name}' must be an integer");
            }

            if ($parameterDef['type'] === 'string' && !is_string($value)) {
                return ValidationResult::invalid("Parameter '{$name}' must be a string");
            }
        }

        return ValidationResult::valid();
    }

    /**
     * Create Symfony Console input from parameters
     *
     * @param CommandMetadata $metadata Command metadata
     * @param array<string, mixed> $parameters Input parameters
     * @return ArrayInput Console input
     */
    private function createInput(CommandMetadata $metadata, array $parameters): ArrayInput
    {
        $inputArray = ['command' => $metadata->name];

        // Add arguments
        foreach ($metadata->arguments as $argument) {
            $paramName = $this->camelCase($argument->name);
            if (isset($parameters[$paramName])) {
                $inputArray[$argument->name] = $parameters[$paramName];
            }
        }

        // Add options
        foreach ($metadata->options as $option) {
            $paramName = $this->camelCase($option->name);
            if (isset($parameters[$paramName])) {
                $inputArray['--' . $option->name] = $parameters[$paramName];
            }
        }

        // Handle dry-run option specially
        if (isset($parameters['dryRun']) && $parameters['dryRun']) {
            $inputArray['--dry-run'] = true;
        }

        return new ArrayInput($inputArray);
    }

    /**
     * Find parameter definition in metadata
     *
     * @param CommandMetadata $metadata Command metadata
     * @param string $name Parameter name
     * @return array<string, mixed>|null Parameter definition
     */
    private function findParameterDefinition(CommandMetadata $metadata, string $name): ?array
    {
        // Check arguments
        foreach ($metadata->arguments as $argument) {
            if ($this->camelCase($argument->name) === $name) {
                return [
                    'type' => $argument->isArray ? 'array' : 'string',
                    'required' => $argument->isRequired
                ];
            }
        }

        // Check options
        foreach ($metadata->options as $option) {
            if ($this->camelCase($option->name) === $name) {
                $type = $option->acceptValue ? 'string' : 'boolean';
                if ($option->isArray) {
                    $type = 'array';
                }
                return [
                    'type' => $type,
                    'required' => false
                ];
            }
        }

        return null;
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