<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Blueprint;

use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\TraitType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\ClassLike;
use InvalidArgumentException;
use PhpGen\ClassGenerator\Generation\FileComposer;

/**
 * Universal builder for creating PHP code elements (classes, interfaces, traits)
 *
 * This class provides a fluent interface for building PHP code elements
 * with use statements and configuration callbacks. It acts as a facade
 * over ElementBlueprint and FileComposer classes.
 */
final class FileBlueprint
{
    private ElementBlueprint $spec;
    private bool $autoGenerateUses = true;
    private DependencyAnalyzer $dependencyAnalyzer;

    /**
     * @param string $fullyQualifiedName
     * @param class-string<ClassLike> $netteTypeClass
     * @param DependencyAnalyzer|null $dependencyAnalyzer
     */
    public function __construct(string $fullyQualifiedName, string $netteTypeClass, ?DependencyAnalyzer $dependencyAnalyzer = null)
    {
        $this->spec = new ElementBlueprint($fullyQualifiedName, $netteTypeClass);
        $this->dependencyAnalyzer = $dependencyAnalyzer ?? new DependencyAnalyzer();
    }

    public static function createEmptyClass(string $fullyQualifiedName): self
    {
        return new FileBlueprint($fullyQualifiedName, ClassType::class);
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
        return new FileBlueprint($fullyQualifiedName, InterfaceType::class);
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

    public static function createEmptyEnum(string $fullyQualifiedName): self
    {
        return new FileBlueprint($fullyQualifiedName, EnumType::class);
    }

    /**
     * @param string $fullyQualifiedName
     * @param callable(EnumType): EnumType $structure
     * @return self
     */
    public static function createEnum(string $fullyQualifiedName, callable $structure): self
    {
        $bluePrint = self::createEmptyEnum($fullyQualifiedName);
        $bluePrint->defineStructure($structure);

        return $bluePrint;
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
        $filteredClasses = array_filter($classNames, fn($class) => !empty($class));
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
                $dependencies = $this->dependencyAnalyzer->extractDependencies($result);
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
     * @param FileComposer $assembler The file composer to use for building
     */
    public function build(FileComposer $assembler): PhpFile
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
