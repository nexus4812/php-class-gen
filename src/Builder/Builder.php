<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Builder;

use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\TraitType;
use Nette\PhpGenerator\PhpFile;

/**
 * Universal builder for creating PHP code elements (classes, interfaces, traits)
 *
 * This class provides a fluent interface for building PHP code elements
 * with use statements and configuration callbacks. It acts as a facade
 * over ElementSpec and FileAssembler classes.
 */
final class Builder implements BuilderInterface
{
    private ElementSpec $spec;

    /**
     * Create a new Builder instance
     *
     * @param string $fullyQualifiedName The fully qualified name of the element to generate
     * @param string $netteTypeClass The Nette type class (InterfaceType::class, ClassType::class, TraitType::class)
     */
    public function __construct(string $fullyQualifiedName, string $netteTypeClass)
    {
        $this->spec = new ElementSpec($fullyQualifiedName, $netteTypeClass);
    }

    public static function createClass(string $fullyQualifiedName): self
    {
        return new Builder($fullyQualifiedName, ClassType::class);
    }

    public static function createInterface(string $fullyQualifiedName): self
    {
        return new Builder($fullyQualifiedName, InterfaceType::class);
    }

    public static function createEnum(string $fullyQualifiedName): self
    {
        return new Builder($fullyQualifiedName, EnumType::class);
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
     * Configure the element with a callback
     *
     * This method allows you to configure the generated element using a callback function.
     * The callback receives the appropriate Nette type instance and must return the same instance.
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
        $this->spec = $this->spec->configure($configurator);
        return $this;
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
