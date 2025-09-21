<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Console;

use PhpGen\ClassGenerator\Config\CommandRegistry;
use PhpGen\ClassGenerator\Config\PhpGenConfig;
use Symfony\Component\Console\Application as SymfonyApplication;
use InvalidArgumentException;
use Throwable;

/**
 * PhpGen Console Application
 *
 * This application uses the PhpGenConfig system
 * to register and manage code generators.
 */
final class Application extends SymfonyApplication
{
    /**
     * Create a new PhpGen application instance
     *
     * @param string|null $configPath Optional path to phpgen.php config file
     */
    public function __construct(?string $configPath = null)
    {
        parent::__construct('PHP Class Generator', '0.0.2');
        $this->loadPhpGenConfig($configPath);
    }
    /**
     * Load PhpGenConfig-based configuration
     *
     * @param string|null $configPath Path to phpgen.php config file
     * @return void
     * @throws InvalidArgumentException If configuration loading fails
     */
    private function loadPhpGenConfig(?string $configPath = null): void
    {
        // Determine config file path
        $configFile = $configPath ?? $this->findPhpGenConfigFile();

        if ($configFile === null || !file_exists($configFile)) {
            throw new InvalidArgumentException(
                'PhpGen configuration file not found. Please create a phpgen.php file in your project root.'
            );
        }

        try {
            // Load the configuration
            $config = require $configFile;

            if (!$config instanceof PhpGenConfig) {
                throw new InvalidArgumentException(
                    'Configuration file must return a PhpGenConfig instance.'
                );
            }

            // Validate configuration
            $config->validate();

            // Register commands from configuration
            $registry = new CommandRegistry($config);
            $registry->registerWithApplication($this);

        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                'Failed to load PhpGen configuration: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Find phpgen.php configuration file in common locations
     *
     * @return string|null Path to config file or null if not found
     */
    private function findPhpGenConfigFile(): ?string
    {
        $homeDir = $_SERVER['HOME'] ?? null;

        $possiblePaths = [
            getcwd() . '/phpgen.php',                    // Current working directory
            getcwd() . '/.phpgen.php',                   // Hidden config file
            __DIR__ . '/../../phpgen.php',               // Project root
        ];

        // Add home directory path only if HOME is set and is a string
        if (is_string($homeDir)) {
            $possiblePaths[] = $homeDir . '/.phpgen/phpgen.php';    // User home directory
        }

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
