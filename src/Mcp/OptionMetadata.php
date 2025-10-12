<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Mcp;

/**
 * Metadata structure for a command option
 */
final readonly class OptionMetadata
{
    /**
     * @param string $name Option name
     * @param string|null $description Option description
     * @param string|null $shortcut Option shortcut
     * @param bool $acceptValue Whether the option accepts a value
     * @param bool $isArray Whether the option accepts multiple values
     * @param mixed $default Default value
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $shortcut,
        public bool $acceptValue,
        public bool $isArray,
        public mixed $default
    ) {
    }
}