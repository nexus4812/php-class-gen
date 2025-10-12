<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Blueprint;

use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\TraitType;
use Nette\PhpGenerator\TraitUse;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Property;

/**
 * Extracts class dependencies from Nette PHP Generator ClassLike objects
 *
 * This class analyzes ClassLike objects (classes, interfaces, traits) and extracts
 * all dependencies that should be included as use statements, including:
 * - Inheritance relationships (extends, implements)
 * - Trait usage
 * - Method parameter and return types
 * - Property types
 * - Attributes
 */
final class DependencyAnalyzer
{
    /**
     * Extract all dependencies from a ClassLike object
     *
     * @param ClassLike $classLike The ClassLike object to analyze
     * @return array<string> Array of fully qualified class names
     */
    public function extractDependencies(ClassLike $classLike): array
    {
        $dependencies = [];

        // Extract type-specific dependencies
        if ($classLike instanceof InterfaceType) {
            $dependencies = array_merge($dependencies, $this->extractFromInterface($classLike));
        }

        if ($classLike instanceof ClassType) {
            $dependencies = array_merge($dependencies, $this->extractFromClass($classLike));
        }

        if ($classLike instanceof TraitType) {
            $dependencies = array_merge($dependencies, $this->extractFromTrait($classLike));
        }

        // Extract common dependencies (methods, properties, attributes)
        $dependencies = array_merge($dependencies, $this->extractCommonDependencies($classLike));

        return array_unique(array_filter($dependencies, fn ($dep) => !empty($dep)));
    }

    /**
     * Extract dependencies specific to interfaces
     *
     * @param InterfaceType $interface
     * @return array<string>
     */
    private function extractFromInterface(InterfaceType $interface): array
    {
        $dependencies = [];

        // Interface extends
        $extends = $interface->getExtends();
        if (!empty($extends)) {
            $dependencies = array_merge($dependencies, $extends);
        }

        return $dependencies;
    }

    /**
     * Extract dependencies specific to classes
     *
     * @param ClassType $class
     * @return array<string>
     */
    private function extractFromClass(ClassType $class): array
    {
        $dependencies = [];

        // Class extends
        $extends = $class->getExtends();
        if (!empty($extends)) {
            $dependencies[] = $extends;
        }

        // Class implements
        $implements = $class->getImplements();
        if (!empty($implements)) {
            $dependencies = array_merge($dependencies, $implements);
        }

        // Class uses traits
        $traitUses = $class->getTraits();
        if (!empty($traitUses)) {
            foreach ($traitUses as $traitUse) {
                // TraitUse object - get the trait name
                $dependencies[] = $traitUse->getName();
            }
        }

        return $dependencies;
    }

    /**
     * Extract dependencies specific to traits
     *
     * @param TraitType $trait
     * @return array<string>
     */
    private function extractFromTrait(TraitType $trait): array
    {
        $dependencies = [];

        // Trait uses other traits
        $traitUses = $trait->getTraits();
        if (!empty($traitUses)) {
            foreach ($traitUses as $traitUse) {
                // TraitUse object - get the trait name
                $dependencies[] = $traitUse->getName();
            }
        }

        return $dependencies;
    }

    /**
     * Extract common dependencies from methods, properties, and attributes
     *
     * @param ClassLike $classLike
     * @return array<string>
     */
    private function extractCommonDependencies(ClassLike $classLike): array
    {
        $dependencies = [];

        // Extract from methods
        $dependencies = array_merge($dependencies, $this->extractFromMethods($classLike));

        // Extract from properties
        $dependencies = array_merge($dependencies, $this->extractFromProperties($classLike));

        // Extract from attributes
        $dependencies = array_merge($dependencies, $this->extractFromAttributes($classLike));

        return $dependencies;
    }

    /**
     * Extract dependencies from method signatures
     *
     * @param ClassLike $classLike
     * @return array<string>
     */
    private function extractFromMethods(ClassLike $classLike): array
    {
        $dependencies = [];

        if (!method_exists($classLike, 'getMethods')) {
            return $dependencies;
        }

        $methods = $classLike->getMethods();

        if (is_array($methods)) {
            foreach ($methods as $method) {
                if ($method instanceof Method) {
                    // Method parameters
                    $parameters = $method->getParameters();
                    foreach ($parameters as $param) {
                        $type = $param->getType();
                        if ($type && $this->isQualifiedClassName((string)$type)) {
                            $dependencies[] = (string)$type;
                        }
                    }

                    // Method return type
                    $returnType = $method->getReturnType();
                    if ($returnType && $this->isQualifiedClassName((string)$returnType)) {
                        $dependencies[] = (string)$returnType;
                    }
                }
            }
        }

        return $dependencies;
    }

    /**
     * Extract dependencies from property types
     *
     * @param ClassLike $classLike
     * @return array<string>
     */
    private function extractFromProperties(ClassLike $classLike): array
    {
        $dependencies = [];

        if (!method_exists($classLike, 'getProperties')) {
            return $dependencies;
        }

        $properties = $classLike->getProperties();

        if (is_array($properties)) {
            foreach ($properties as $property) {
                if ($property instanceof Property) {
                    $type = $property->getType();
                    if ($type && $this->isQualifiedClassName((string)$type)) {
                        $dependencies[] = (string)$type;
                    }
                }
            }
        }

        return $dependencies;
    }

    /**
     * Extract dependencies from attributes
     *
     * @param ClassLike $classLike
     * @return array<string>
     */
    private function extractFromAttributes(ClassLike $classLike): array
    {
        $dependencies = [];

        foreach ($classLike->getAttributes() as $attribute) {
            $attributeName = $attribute->getName();
            if ($this->isQualifiedClassName($attributeName)) {
                $dependencies[] = $attributeName;
            }
        }

        return $dependencies;
    }

    /**
     * Check if a string represents a qualified class name that should be imported
     *
     * @param string $className
     * @return bool
     */
    private function isQualifiedClassName(string $className): bool
    {
        return !empty($className) && str_contains($className, '\\');
    }
}
