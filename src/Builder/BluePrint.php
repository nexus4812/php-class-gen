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
     * @param string $fullyQualifiedName
     * @param string $netteTypeClass
     * @param DependencyExtractor|null $dependencyExtractor
     */
    public function __construct(string $fullyQualifiedName, string $netteTypeClass, ?DependencyExtractor $dependencyExtractor = null)
    {
        $this->spec = new ElementSpec($fullyQualifiedName, $netteTypeClass);
        $this->dependencyExtractor = $dependencyExtractor ?? new DependencyExtractor();
    }

    public static function createEmptyClass(string $fullyQualifiedName): self
    {
        return new BluePrint($fullyQualifiedName, ClassType::class);
    }

    /**
     * @param string $fullyQualifiedName
     * @param callable(ClassType): ClassType $structure
     * @return self
     */
    public static function createClass(string $fullyQualifiedName, callable $structure): self
    {
        $bluePrint = self::createEmptyClass($fullyQualifiedName);
        $bluePrint->defineStructure($structure);

        return $bluePrint;
    }

    public static function createEmptyInterface(string $fullyQualifiedName): self
    {
        return new BluePrint($fullyQualifiedName, InterfaceType::class);
    }

    /**
     * @param string $fullyQualifiedName
     * @param callable(InterfaceType): InterfaceType $structure
     * @return self
     */
    public static function createInterface(string $fullyQualifiedName, callable $structure): self
    {
        $bluePrint = self::createEmptyInterface($fullyQualifiedName);
        $bluePrint->defineStructure($structure);

        return $bluePrint;
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
     * @param callable $configurator
     * @return $this
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
