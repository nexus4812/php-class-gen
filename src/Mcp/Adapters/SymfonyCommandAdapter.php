<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Mcp\Adapters;

use PhpGen\ClassGenerator\Console\Commands\Command;
use PhpGen\ClassGenerator\Mcp\CommandDiscovery;
use PhpGen\ClassGenerator\Mcp\CommandMetadata;
use PhpGen\ClassGenerator\Mcp\ValidationResult;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

/**
 * Adapter to convert Symfony Console Commands to MCP tool handlers
 *
 * This adapter bridges the gap between PhpGen's existing Symfony Command infrastructure
 * and the php-mcp/server library. It reuses the CommandDiscovery logic to maintain
 * compatibility with the existing implementation.
 */
final class SymfonyCommandAdapter
{
    public function __construct(
        private readonly CommandDiscovery $discovery
    ) {
    }

    /**
     * Create a callable handler for php-mcp/server from a Symfony Command
     *
     * @param Command $command The Symfony command to adapt
     * @return callable(array<string, mixed>): array<string, mixed> Handler function that executes the command
     */
    public function createToolHandler(Command $command): callable
    {
        $metadata = $this->discovery->extractCommandMetadata($command);

        /**
         * @param array<string, mixed> $parameters
         * @return array<string, mixed>
         */
        return function (array $parameters) use ($command, $metadata): array {
            /** @var array<string, mixed> $parameters */
            return $this->executeCommand($command, $metadata, $parameters);
        };
    }

    /**
     * Generate MCP-compatible JSON schema for a Symfony Command
     *
     * @param Command $command The command to generate schema for
     * @return array<string, mixed> JSON schema definition
     */
    public function generateSchema(Command $command): array
    {
        $metadata = $this->discovery->extractCommandMetadata($command);
        $schema = $this->discovery->generateMcpSchema($metadata);

        // Return only the inputSchema part (php-mcp/server handles name/description separately)
        $inputSchema = $schema['inputSchema'] ?? [];

        /** @var array<string, mixed> */
        return is_array($inputSchema) ? $inputSchema : [];
    }

    /**
     * Get the MCP tool name for a command
     *
     * @param Command $command The command
     * @return string MCP tool name (e.g., "dto:create" -> "dto_create")
     */
    public function getToolName(Command $command): string
    {
        $commandName = $command->getName();
        if ($commandName === null) {
            return '';
        }

        // Replace colons with underscores for MCP compatibility
        return str_replace(':', '_', $commandName);
    }

    /**
     * Execute a Symfony command with the given parameters
     *
     * @param Command $command The command to execute
     * @param CommandMetadata $metadata Command metadata
     * @param array<string, mixed> $parameters Input parameters from MCP
     * @return array<string, mixed> Execution result
     * @throws \Exception If command execution fails
     */
    private function executeCommand(Command $command, CommandMetadata $metadata, array $parameters): array
    {
        try {
            // Validate parameters
            $validationResult = $this->validateParameters($metadata, $parameters);
            if (!$validationResult->isValid) {
                throw new \InvalidArgumentException(
                    $validationResult->errorMessage ?? 'Validation failed'
                );
            }

            // Convert parameters to Symfony Console input format
            $input = $this->createInput($metadata, $parameters);
            $output = new BufferedOutput();

            // Check if this is a dry run
            $isDryRun = $parameters['dryRun'] ?? false;

            // Execute the command
            $exitCode = $command->run($input, $output);

            if ($exitCode !== 0) {
                throw new \RuntimeException(
                    "Command execution failed with exit code: {$exitCode}\nOutput: " . $output->fetch()
                );
            }

            return [
                'command' => $metadata->name,
                'parameters' => $parameters,
                'output' => $output->fetch(),
                'exitCode' => $exitCode,
                'dryRun' => $isDryRun,
                'success' => true,
            ];

        } catch (Throwable $e) {
            throw new \RuntimeException(
                "Command execution failed: " . $e->getMessage(),
                0,
                $e
            );
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
