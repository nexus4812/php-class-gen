<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Mcp;

/**
 * Result of MCP tool execution
 */
final readonly class McpToolResult
{
    /**
     * @param bool $isSuccess Whether the execution was successful
     * @param mixed $data Result data or error message
     */
    public function __construct(
        public bool $isSuccess,
        public mixed $data
    ) {
    }

    /**
     * Create a successful result
     *
     * @param mixed $data Result data
     * @return self
     */
    public static function success(mixed $data): self
    {
        return new self(true, $data);
    }

    /**
     * Create an error result
     *
     * @param string $message Error message
     * @return self
     */
    public static function error(string $message): self
    {
        return new self(false, $message);
    }

    /**
     * Convert to MCP protocol response format
     *
     * @return array<string, mixed> MCP response
     */
    public function toMcpResponse(): array
    {
        if ($this->isSuccess) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => is_string($this->data) ? $this->data : json_encode($this->data, JSON_PRETTY_PRINT)
                    ]
                ]
            ];
        }

        $errorMessage = is_string($this->data) ? $this->data : json_encode($this->data);
        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => "Error: {$errorMessage}"
                ]
            ],
            'isError' => true
        ];
    }
}