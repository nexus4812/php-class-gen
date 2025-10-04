<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Mcp;

/**
 * Metadata structure for a command argument
 */
final readonly class ArgumentMetadata
{
    /**
     * @param string $name Argument name
     * @param string|null $description Argument description
     * @param bool $isRequired Whether the argument is required
     * @param bool $isArray Whether the argument accepts multiple values
     * @param mixed $default Default value
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public bool $isRequired,
        public bool $isArray,
        public mixed $default
    ) {
    }
}