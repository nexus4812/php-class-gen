<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Builder;

use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\TraitType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\ClassLike;
use InvalidArgumentException;

/**
 * Universal builder for creating PHP code elements (classes, interfaces, traits)
 *
 * This class provides a fluent interface for building PHP code elements
 * with use statements and configuration callbacks. It acts as a facade
 * over ElementSpec and FileAssembler classes.
 */
final class BluePrint implements BuilderInterface
{
    private ElementSpec $spec;
    private bool $autoGenerateUses = true;
    private DependencyExtractor $dependencyExtractor;

    /**
     * Create a new Builder instance
     *
     * @param string $fullyQualifiedName The fully qualified name of the element to generate
     * @param string $netteTypeClass The Nette type class (InterfaceType::class, ClassType::class, TraitType::class)
     * @param DependencyExtractor|null $dependencyExtractor Optional dependency extractor (creates new instance if null)
     */
    public function __construct(string $fullyQualifiedName, string $netteTypeClass, ?DependencyExtractor $dependencyExtractor = null)
    {
        $this->spec = new ElementSpec($fullyQualifiedName, $netteTypeClass);
        $this->dependencyExtractor = $dependencyExtractor ?? new DependencyExtractor();
    }

    public static function createClass(string $fullyQualifiedName): self
    {
        return new BluePrint($fullyQualifiedName, ClassType::class);
    }

    public static function createInterface(string $fullyQualifiedName): self
    {
        return new BluePrint($fullyQualifiedName, InterfaceType::class);
    }

    public static function createEnum(string $fullyQualifiedName): self
    {
        return new BluePrint($fullyQualifiedName, EnumType::class);
    }

    /**
     * Add a use statement to the file
     */
    public function addUse(string $use): self
    {
        $this->spec = $this->spec->addUse($use);
        return $this;
    }

    /**
     * Add multiple use statements, automatically removing duplicates
     * @param array<string> $classNames
     */
    public function addUsesForClasses(array $classNames): self
    {
        $filteredClasses = array_filter($classNames, fn ($class) => !empty($class));
        $uniqueClasses = array_unique($filteredClasses);

        foreach ($uniqueClasses as $className) {
            $this->addUse($className);
        }

        return $this;
    }

    /**
     * Enable automatic use statement generation from defineStructure callback
     */
    public function enableAutoUseGeneration(bool $enable = true): self
    {
        $this->autoGenerateUses = $enable;
        return $this;
    }


    /**
     * Configure the element with a callback
     *
     * This method allows you to configure the generated element using a callback function.
     * The callback receives the appropriate Nette type instance and must return the same instance.
     * If auto use generation is enabled, it will automatically extract dependencies from the configured element.
     *
     * @param callable $configurator A closure that receives and returns the appropriate Nette type instance
     * @return static Returns the builder instance for method chaining
     *
     * @example Interface configuration:
     * $builder->configure(function (InterfaceType $interface): InterfaceType {
     *     $interface->addMethod('process')->addParameter('data')->setType('array');
     *     return $interface;
     * });
     *
     * @example Class configuration:
     * $builder->configure(function (ClassType $class): ClassType {
     *     $class->setFinal()->addImplement('SomeInterface');
     *     $class->addMethod('execute')->setBody('// implementation');
     *     return $class;
     * });
     */
    public function defineStructure(callable $configurator): self
    {
        if ($this->autoGenerateUses) {
            // Create a temporary ClassLike instance to extract dependencies
            $tempInstance = $this->createTempClassLikeInstance();
            $result = $configurator($tempInstance);

            // Extract dependencies and add use statements
            if ($result instanceof ClassLike) {
                $dependencies = $this->dependencyExtractor->extractDependencies($result);
                $this->addUsesForClasses($dependencies);
            }
        }

        $this->spec = $this->spec->configure($configurator);
        return $this;
    }

    /**
     * Create a temporary ClassLike instance for dependency extraction
     */
    private function createTempClassLikeInstance(): ClassLike
    {
        $netteTypeClass = $this->spec->getNetteTypeClass();

        return match ($netteTypeClass) {
            ClassType::class => new ClassType('TempClass'),
            InterfaceType::class => new InterfaceType('TempInterface'),
            TraitType::class => new TraitType('TempTrait'),
            EnumType::class => new EnumType('TempEnum'),
            default => throw new InvalidArgumentException("Unsupported Nette type class: {$netteTypeClass}"),
        };
    }

    /**
     * Build and return the complete PHP file
     *
     * @param FileAssembler $assembler The file assembler to use for building
     */
    public function build(FileAssembler $assembler): PhpFile
    {
        return $assembler->assemble($this->spec);
    }

    /**
     * Get the fully qualified name of the element being built
     */
    public function getFullyQualifiedName(): string
    {
        return $this->spec->getFullyQualifiedName();
    }
}
