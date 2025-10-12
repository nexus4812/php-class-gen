<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Mcp;

/**
 * Result of parameter validation
 */
final readonly class ValidationResult
{
    /**
     * @param bool $isValid Whether validation passed
     * @param string|null $errorMessage Error message if validation failed
     */
    public function __construct(
        public bool $isValid,
        public ?string $errorMessage = null
    ) {
    }

    /**
     * Create a valid result
     *
     * @return self
     */
    public static function valid(): self
    {
        return new self(true);
    }

    /**
     * Create an invalid result with error message
     *
     * @param string $message Error message
     * @return self
     */
    public static function invalid(string $message): self
    {
        return new self(false, $message);
    }
}