<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Builder;

use InvalidArgumentException;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\TraitType;
use PhpGen\ClassGenerator\Config\PhpGenConfig;
use LogicException;

/**
 * Assembles PHP files from element specifications
 *
 * This class is responsible for the actual construction of PhpFile instances
 * based on the configuration provided in ElementSpec objects.
 */
final class FileAssembler
{
    public function __construct(
        private PhpGenConfig $config
    ) {
    }

    /**
     * Assemble a PHP file from the given element specification
     */
    public function assemble(ElementSpec $spec): PhpFile
    {
        $file = new PhpFile();

        if ($this->config->getStrictTypes()) {
            $file->setStrictTypes();
        }

        $namespace = $file->addNamespace($spec->getNamespace());

        // Add use statements
        foreach ($spec->getUses() as $use) {
            $namespace->addUse($use);
        }

        // Create and configure the specific type (class, interface, trait, etc.)
        $element = $this->createElement($namespace, $spec);

        $configurator = $spec->getConfigurator();
        if ($configurator !== null) {
            $configuredElement = $configurator($element);
            $this->validateConfiguredElement($configuredElement, $spec->getNetteTypeClass());
        }

        return $file;
    }

    /**
     * Create the appropriate Nette type instance within the given namespace
     * based on the element specification.
     *
     * @return ClassType|InterfaceType|TraitType
     */
    private function createElement(PhpNamespace $namespace, ElementSpec $spec)
    {
        $className = $spec->getClassName();
        $netteTypeClass = $spec->getNetteTypeClass();

        return match ($netteTypeClass) {
            InterfaceType::class => $namespace->addInterface($className),
            ClassType::class => $namespace->addClass($className),
            TraitType::class => $namespace->addTrait($className),
            default => throw new LogicException("Unsupported Nette type class: {$netteTypeClass}")
        };
    }

    /**
     * Validate that the configurator callback returned an instance of the expected type.
     *
     * @param mixed $element The element returned by the configurator callback
     * @param string $expectedType The expected Nette type class
     * @throws InvalidArgumentException If the element is not an instance of the expected type
     */
    private function validateConfiguredElement($element, string $expectedType): void
    {
        if (!$element instanceof $expectedType) {
            $actualType = is_object($element) ? $element::class : gettype($element);
            throw new InvalidArgumentException(
                "Configurator must return an instance of {$expectedType}, got {$actualType}"
            );
        }
    }
}
