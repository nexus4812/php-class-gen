<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Mcp;

use Throwable;

/**
 * Handles JSON-RPC 2.0 protocol for MCP
 */
final class JsonRpcHandler
{
    /**
     * Process JSON-RPC request
     *
     * @param string $input JSON-RPC request
     * @return string JSON-RPC response
     */
    public function processRequest(string $input): string
    {
        try {
            $request = json_decode($input, true, 512, JSON_THROW_ON_ERROR);

            if (!$this->isValidRequest($request)) {
                return json_encode($this->createErrorResponse(null, -32600, 'Invalid Request'), JSON_THROW_ON_ERROR);
            }

            /** @var array<string, mixed> $request */
            $method = $request['method'];
            $params = $request['params'] ?? [];
            $id = $request['id'] ?? null;

            // Handle different MCP methods
            $response = match ($method) {
                'initialize' => $this->handleInitialize($params),
                'tools/list' => $this->handleListTools(),
                'tools/call' => $this->handleCallTool($params),
                default => $this->createErrorResponse($id, -32601, 'Method not found')
            };

            // Add request ID to response
            if ($id !== null) {
                $response['id'] = $id;
            }

            return json_encode($response, JSON_THROW_ON_ERROR);

        } catch (Throwable $e) {
            return json_encode($this->createErrorResponse(null, -32603, 'Internal error: ' . $e->getMessage()), JSON_THROW_ON_ERROR);
        }
    }

    /**
     * Validate JSON-RPC request structure
     *
     * @param mixed $request Decoded request
     * @return bool Whether the request is valid
     */
    private function isValidRequest(mixed $request): bool
    {
        return is_array($request) &&
               isset($request['jsonrpc']) &&
               $request['jsonrpc'] === '2.0' &&
               isset($request['method']) &&
               is_string($request['method']);
    }

    /**
     * Handle initialize method
     *
     * @param array<string, mixed> $params Parameters
     * @return array<string, mixed> Response
     */
    private function handleInitialize(array $params): array
    {
        return [
            'jsonrpc' => '2.0',
            'result' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => []
                ],
                'serverInfo' => [
                    'name' => 'phpgen-mcp-server',
                    'version' => '1.0.0'
                ]
            ]
        ];
    }

    /**
     * Handle tools/list method
     *
     * @return array<string, mixed> Response
     */
    private function handleListTools(): array
    {
        // This will be implemented by the main server
        return [
            'jsonrpc' => '2.0',
            'result' => [
                'tools' => []
            ]
        ];
    }

    /**
     * Handle tools/call method
     *
     * @param array<string, mixed> $params Parameters
     * @return array<string, mixed> Response
     */
    private function handleCallTool(array $params): array
    {
        // This will be implemented by the main server
        return [
            'jsonrpc' => '2.0',
            'result' => [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Tool execution not implemented'
                    ]
                ]
            ]
        ];
    }

    /**
     * Create error response
     *
     * @param mixed $id Request ID
     * @param int $code Error code
     * @param string $message Error message
     * @return array<string, mixed> Error response
     */
    private function createErrorResponse(mixed $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => $code,
                'message' => $message
            ],
            'id' => $id
        ];
    }
}