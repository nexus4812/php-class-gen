<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Config;

use InvalidArgumentException;

/**
 * Main configuration class for PhpGen - Rector-style configuration
 *
 * This class provides a fluent interface for configuring the PhpGen tool,
 * allowing users to specify which commands to load, default settings,
 * and custom configurations in a project-root configuration file.
 *
 * @example Basic usage in phpgen.php:
 * use PhpGen\ClassGenerator\Config\PhpGenConfig;
 * use PhpGen\ClassGenerator\Commands\LaravelCqrsQueryCommand;
 *
 * return PhpGenConfig::configure()
 *     ->withCommands([
 *         LaravelCqrsQueryCommand::class,
 *     ])
 *     ->withDefaultNamespace('App\\')
 *     ->withPsr4Mapping('App\\', 'src/');
 */
final class PhpGenConfig
{
    /**
     * Array of command class names to register
     * @var array<class-string>
     */
    private array $commands = [];

    /**
     * PSR-4 namespace to directory mappings (normal priority)
     * @var array<string, string>
     */
    private array $psr4Mappings = [];

    /**
     * PSR-4 namespace to directory mappings (high priority)
     * @var array<string, string>
     */
    private array $priorityPsr4Mappings = [];

    /**
     * Whether to use strict_types declaration in generated files
     */
    private bool $strictTypes = true;

    /**
     * Get the registered command class names
     *
     * @return array<class-string> Array of command class names
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get all PSR-4 namespace to directory mappings
     *
     * Returns merged mappings with priority mappings taking precedence over normal mappings.
     *
     * @return array<string, string> Array of namespace => directory mappings
     */
    public function getPsr4Mappings(): array
    {
        // Merge with priority mappings overriding normal mappings
        return array_merge($this->psr4Mappings, $this->priorityPsr4Mappings);
    }

    /**
     * Get whether strict_types declaration should be used
     *
     * @return bool True if strict types should be used, false otherwise
     */
    public function getStrictTypes(): bool
    {
        return $this->strictTypes;
    }

    /**
     * Create a new PhpGenConfig instance for configuration
     *
     * This is the starting point for configuring code generation.
     *
     * @return self A new PhpGenConfig instance ready for configuration
     */
    public static function configure(): self
    {
        return new self();
    }

    /**
     * Set the commands to be registered
     *
     * @param array<class-string> $commands Array of command class names
     * @return self
     */
    public function withCommands(array $commands): self
    {
        $this->commands = $commands;
        return $this;
    }

    /**
     * Add a PSR-4 mapping (normal priority)
     *
     * @param string $namespace The namespace prefix
     * @param string $directory The directory path
     * @return self
     */
    public function withPsr4Mapping(string $namespace, string $directory): self
    {
        $this->psr4Mappings[$namespace] = $directory;
        return $this;
    }

    /**
     * Add a PSR-4 mapping with high priority
     *
     * Priority mappings take precedence over normal mappings when resolving namespaces.
     * This is useful for legacy code or when you need to override composer.json mappings.
     *
     * @param string $namespace The namespace prefix
     * @param string $directory The directory path
     * @return self
     */
    public function withPriorityPsr4Mapping(string $namespace, string $directory): self
    {
        $this->priorityPsr4Mappings[$namespace] = $directory;
        return $this;
    }

    /**
     * Load PSR-4 mappings from composer.json
     *
     * This method reads the composer.json file and automatically imports
     * all PSR-4 namespace mappings from both autoload and autoload-dev sections.
     *
     * @param string $composerPath Path to composer.json file (default: 'composer.json')
     * @return self
     * @throws InvalidArgumentException If composer.json doesn't exist or is invalid
     */
    public function withComposerAutoload(string $composerPath = 'composer.json'): self
    {
        $composerMappings = ComposerReader::readPsr4Mappings($composerPath);

        foreach ($composerMappings as $namespace => $directory) {
            $this->withPsr4Mapping($namespace, $directory);
        }

        return $this;
    }

    /**
     * Enable or disable strict types
     *
     * @param bool $strictTypes
     * @return self
     */
    public function withStrictTypes(bool $strictTypes): self
    {
        $this->strictTypes = $strictTypes;
        return $this;
    }

    /**
     * Validate the configuration
     *
     * This method performs validation checks on the configuration to ensure
     * it's valid and all required settings are present.
     *
     * @return void
     * @throws InvalidArgumentException If the configuration is invalid
     */
    public function validate(): void
    {
        if (empty($this->commands)) {
            throw new InvalidArgumentException('At least one command must be specified using withCommands()');
        }

        foreach ($this->commands as $commandClass) {
            if (!class_exists($commandClass)) {
                throw new InvalidArgumentException("Command class '{$commandClass}' does not exist");
            }
        }

        if (empty($this->psr4Mappings) && empty($this->priorityPsr4Mappings)) {
            throw new InvalidArgumentException('At least one PSR-4 mapping must be specified using withPsr4Mapping() or withPriorityPsr4Mapping()');
        }

        foreach ($this->psr4Mappings as $namespace => $directory) {
            if (empty($namespace)) {
                throw new InvalidArgumentException('PSR-4 namespace cannot be empty');
            }

            if (empty($directory)) {
                throw new InvalidArgumentException("PSR-4 directory for namespace '{$namespace}' cannot be empty");
            }
        }

        foreach ($this->priorityPsr4Mappings as $namespace => $directory) {
            if (empty($namespace)) {
                throw new InvalidArgumentException('Priority PSR-4 namespace cannot be empty');
            }

            if (empty($directory)) {
                throw new InvalidArgumentException("Priority PSR-4 directory for namespace '{$namespace}' cannot be empty");
            }
        }

        // Check for mapping conflicts
        $this->validateMappingConflicts();
    }

    /**
     * Validate PSR-4 mapping conflicts
     *
     * Rules:
     * - Priority mappings cannot conflict with each other (same directory)
     * - Normal mappings cannot conflict with each other (same directory)
     * - Priority vs Normal conflicts are allowed (priority wins)
     *
     * @throws InvalidArgumentException If conflicts are found
     */
    private function validateMappingConflicts(): void
    {
        // Check conflicts within priority mappings
        $this->checkDuplicateDirectories($this->priorityPsr4Mappings, 'priority');

        // Check conflicts within normal mappings
        $this->checkDuplicateDirectories($this->psr4Mappings, 'normal');

        // Priority vs Normal conflicts are allowed (priority wins)
    }

    /**
     * Check for duplicate directories within a mapping group
     *
     * @param array<string, string> $mappings The mappings to check
     * @param string $type The type of mappings (for error messages)
     * @throws InvalidArgumentException If duplicate directories are found
     */
    private function checkDuplicateDirectories(array $mappings, string $type): void
    {
        $directoryToNamespace = [];

        foreach ($mappings as $namespace => $directory) {
            $normalizedDir = rtrim($directory, '/\\');

            if (isset($directoryToNamespace[$normalizedDir])) {
                throw new InvalidArgumentException(
                    "Duplicate directory '{$directory}' found in {$type} PSR-4 mappings. " .
                    "Namespaces '{$directoryToNamespace[$normalizedDir]}' and '{$namespace}' " .
                    "both map to the same directory."
                );
            }

            $directoryToNamespace[$normalizedDir] = $namespace;
        }
    }
}
