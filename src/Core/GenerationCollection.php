<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Core;

use PhpGen\ClassGenerator\Config\PhpGenConfig;
use PhpGen\ClassGenerator\Builder\Builder;
use PhpGen\ClassGenerator\Builder\BuilderInterface;
use RuntimeException;
use Throwable;

/**
 * Builder for fine-grained control over file generation
 *
 * This class implements the Builder pattern to provide flexible control
 * over which files are generated and under what conditions. It allows
 * generators to define multiple file types and conditional generation logic.
 *
 * @example Usage in a generator:
 * protected function defineGeneration(GenerationBuilder $builder): void
 * {
 *     $builder
 *         ->addClass('interface', fn() => $this->createInterface())
 *         ->addClass('implementation', fn() => $this->createImplementation())
 *         ->addClass('test', fn() => $this->createTest())
 *         ->when('test', fn() => !$this->context->getOption('skip-tests'))
 *         ->when('interface', fn() => $this->context->getOption('with-interface'));
 * }
 */
class GenerationCollection
{
    private PhpGenConfig $config;

    /**
     * @var array<string, callable> Factory functions for each file type
     */
    private array $factories = [];


    public function __construct(PhpGenConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Add a pre-configured builder directly to the generation
     *
     * @param Builder $builder Pre-configured UniversalBuilder instance
     * @return void
     */
    private function addBuilder(Builder $builder): void
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

    /**
     * Add a pre-configured builder to the generation
     *
     * This is the unified API for adding any type of code generation.
     * The UniversalBuilder already contains the type information (interface, class, trait)
     * so no additional type specification is needed.
     *
     * @param Builder $builder Pre-configured UniversalBuilder instance
     * @return self Returns the builder instance for method chaining
     */
    public function add(Builder $builder): self
    {
        $this->addBuilder($builder);
        return $this;
    }

    /**
     * Build the Generator with all configured files
     *
     * @return Generator The configured generator ready for execution
     */
    public function build(): Generator
    {
        $generator = Generator::create($this->config);

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
