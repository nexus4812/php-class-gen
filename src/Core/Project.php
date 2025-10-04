<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Core;

use PhpGen\ClassGenerator\Config\PhpGenConfig;
use PhpGen\ClassGenerator\Builder\BluePrint;
use PhpGen\ClassGenerator\Builder\BuilderInterface;
use RuntimeException;
use Throwable;

class Project
{
    /**
     * @var array<string, callable> Factory functions for each file type
     */
    private array $factories = [];


    public function __construct()
    {
    }

    private function addBuilder(BluePrint $builder): void
    {
        $fullyQualifiedName = $builder->getFullyQualifiedName();

        // Generate a unique identifier for multiple instances
        $instanceCount = count(array_filter(
            array_keys($this->factories),
            fn ($key) => str_starts_with($key, $fullyQualifiedName)
        ));

        $uniqueKey = $instanceCount > 0 ? "{$fullyQualifiedName}_{$instanceCount}" : $fullyQualifiedName;

        // Store the builder directly as a factory that returns it
        $this->factories[$uniqueKey] = fn () => $builder;
    }

    public function add(BluePrint $builder): self
    {
        $this->addBuilder($builder);
        return $this;
    }

    /**
     * Build the Generator with all configured files
     *
     * @return Generator The configured generator ready for execution
     */
    public function build(PhpGenConfig $config): Generator
    {
        $generator = Generator::create($config);

        // Process in the order they were added
        foreach (array_keys($this->factories) as $type) {
            $builder = $this->createBuilder($type);
            if ($builder !== null) {
                $generator->addBuilder($builder);
            }
        }

        return $generator;
    }

    /**
     * Create a builder using the factory function
     *
     * @param string $type The file type identifier
     * @return BuilderInterface|null The created builder or null if creation failed
     */
    private function createBuilder(string $type): ?BuilderInterface
    {
        if (!isset($this->factories[$type])) {
            return null;
        }

        $factory = $this->factories[$type];

        try {
            $result = $factory();

            if ($result === null) {
                return null;
            }

            if (!$result instanceof BuilderInterface) {
                throw new RuntimeException(
                    "Factory for file type '{$type}' must return a BuilderInterface instance, got " .
                    (is_object($result) ? $result::class : gettype($result))
                );
            }

            return $result;
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Failed to create builder for file type '{$type}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
