<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Mcp\Adapters;

use PhpGen\ClassGenerator\Config\PhpGenConfig;
use InvalidArgumentException;

/**
 * Loader for PhpGen configuration files
 *
 * Handles finding and loading the phpgen.php configuration file
 * using the same logic as the legacy MCP server.
 */
final class PhpGenConfigLoader
{
    /**
     * Load PhpGen configuration from a file
     *
     * @param string|null $configPath Path to the config file, or null to auto-detect
     * @return PhpGenConfig The loaded configuration
     * @throws InvalidArgumentException If config file not found or invalid
     */
    public function load(?string $configPath = null): PhpGenConfig
    {
        $configFile = $configPath ?? $this->findPhpGenConfigFile();

        if ($configFile === null || !file_exists($configFile)) {
            throw new InvalidArgumentException(
                'PhpGen configuration file not found. Please create a phpgen.php file in your project root.'
            );
        }

        $config = require $configFile;

        if (!$config instanceof PhpGenConfig) {
            throw new InvalidArgumentException(
                'Configuration file must return a PhpGenConfig instance.'
            );
        }

        return $config;
    }

    /**
     * Find the PhpGen configuration file
     *
     * Searches in the following order:
     * 1. Current working directory (phpgen.php)
     * 2. Current working directory (.phpgen.php hidden file)
     * 3. Project root (relative to this file)
     *
     * @return string|null Path to config file, or null if not found
     */
    private function findPhpGenConfigFile(): ?string
    {
        // Check current working directory
        $cwd = getcwd();
        if ($cwd !== false) {
            $configInCwd = $cwd . '/phpgen.php';
            if (file_exists($configInCwd)) {
                return $configInCwd;
            }

            $hiddenConfigInCwd = $cwd . '/.phpgen.php';
            if (file_exists($hiddenConfigInCwd)) {
                return $hiddenConfigInCwd;
            }
        }

        // Check project root (going up from src/Mcp/Adapters/)
        $projectRoot = dirname(__DIR__, 3);
        $configInRoot = $projectRoot . '/phpgen.php';
        if (file_exists($configInRoot)) {
            return $configInRoot;
        }

        $hiddenConfigInRoot = $projectRoot . '/.phpgen.php';
        if (file_exists($hiddenConfigInRoot)) {
            return $hiddenConfigInRoot;
        }

        return null;
    }
}
