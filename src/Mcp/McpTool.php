<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Mcp;

use Closure;

/**
 * Represents an MCP tool
 */
final readonly class McpTool
{
    /**
     * @param string $name Tool name
     * @param string $description Tool description
     * @param array<string, mixed> $inputSchema JSON schema for input parameters
     * @param Closure(array<string, mixed>): McpToolResult $executor Function to execute the tool
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $inputSchema,
        public Closure $executor
    ) {
    }

    /**
     * Execute the tool with given parameters
     *
     * @param array<string, mixed> $parameters Input parameters
     * @return McpToolResult Execution result
     */
    public function execute(array $parameters): McpToolResult
    {
        return ($this->executor)($parameters);
    }

    /**
     * Get tool schema for MCP protocol
     *
     * @return array<string, mixed> Tool schema
     */
    public function getSchema(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'inputSchema' => $this->inputSchema
        ];
    }
}