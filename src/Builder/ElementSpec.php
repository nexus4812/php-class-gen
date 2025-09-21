<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Builder;

use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\TraitType;
use InvalidArgumentException;

/**
 * Specification for a code element to be generated
 *
 * This class encapsulates all the configuration needed to generate a single PHP element
 * (class, interface, or trait) including its fully qualified name, use statements,
 * and configuration callback.
 */
final class ElementSpec
{
    /**
     * @param string $fullyQualifiedName The fully qualified name of the element
     * @param string $netteTypeClass The Nette type class (InterfaceType::class, ClassType::class, TraitType::class)
     * @param array<string> $uses Array of use statements
     * @param callable|null $configurator Configuration callback
     */
    public function __construct(
        private string $fullyQualifiedName,
        private string $netteTypeClass,
        private array $uses = [],
        private $configurator = null
    ) {
        $this->validateNetteTypeClass($netteTypeClass);
    }

    /**
     * Add a use statement
     */
    public function addUse(string $use): self
    {
        $uses = $this->uses;
        $uses[] = $use;

        return new self(
            $this->fullyQualifiedName,
            $this->netteTypeClass,
            $uses,
            $this->configurator
        );
    }

    /**
     * Set the configuration callback
     */
    public function configure(callable $configurator): self
    {
        return new self(
            $this->fullyQualifiedName,
            $this->netteTypeClass,
            $this->uses,
            $configurator
        );
    }

    /**
     * Get the fully qualified name
     */
    public function getFullyQualifiedName(): string
    {
        return $this->fullyQualifiedName;
    }

    /**
     * Get the Nette type class
     */
    public function getNetteTypeClass(): string
    {
        return $this->netteTypeClass;
    }

    /**
     * Get all use statements
     *
     * @return array<string>
     */
    public function getUses(): array
    {
        return $this->uses;
    }

    /**
     * Get the configuration callback
     */
    public function getConfigurator(): ?callable
    {
        return $this->configurator;
    }

    /**
     * Extract the namespace from the fully qualified name
     */
    public function getNamespace(): string
    {
        $parts = explode('\\', $this->fullyQualifiedName);
        array_pop($parts); // Remove class name
        return implode('\\', $parts);
    }

    /**
     * Extract the class name from the fully qualified name
     */
    public function getClassName(): string
    {
        $parts = explode('\\', $this->fullyQualifiedName);
        return end($parts);
    }

    /**
     * Validate the Nette type class
     *
     * @throws InvalidArgumentException If the type class is not supported
     */
    private function validateNetteTypeClass(string $netteTypeClass): void
    {
        $supportedTypes = [
            InterfaceType::class,
            ClassType::class,
            TraitType::class,
        ];

        // Normalize the class name (remove leading backslashes)
        $normalizedClass = ltrim($netteTypeClass, '\\');

        // Check if it's in our supported types (with or without leading backslash)
        foreach ($supportedTypes as $supportedType) {
            if ($normalizedClass === ltrim($supportedType, '\\')) {
                return; // Valid type found
            }
        }

        throw new InvalidArgumentException(
            "Unsupported Nette type class: {$netteTypeClass}. " .
            "Supported types: " . implode(', ', $supportedTypes)
        );
    }
}
