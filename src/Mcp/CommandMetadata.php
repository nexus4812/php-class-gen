<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Mcp;

/**
 * Metadata structure for a command
 */
final readonly class CommandMetadata
{
    /**
     * @param string $name Command name
     * @param string $description Command description
     * @param array<ArgumentMetadata> $arguments Command arguments
     * @param array<OptionMetadata> $options Command options
     * @param string $className Full class name
     * @param string $namespace Class namespace
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $arguments,
        public array $options,
        public string $className,
        public string $namespace
    ) {
    }
}
