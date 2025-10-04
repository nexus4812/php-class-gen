<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Config;

use InvalidArgumentException;

/**
 * Reads PSR-4 mappings from composer.json file
 */
final class ComposerReader
{
    /**
     * Read PSR-4 mappings from composer.json
     *
     * @param string $composerPath Path to composer.json file
     * @return array<string, string> Array of namespace => directory mappings
     * @throws InvalidArgumentException If composer.json doesn't exist or is invalid
     */
    public static function readPsr4Mappings(string $composerPath = 'composer.json'): array
    {
        if (!file_exists($composerPath)) {
            throw new InvalidArgumentException("Composer file not found: {$composerPath}");
        }

        $content = file_get_contents($composerPath);
        if ($content === false) {
            throw new InvalidArgumentException("Failed to read composer file: {$composerPath}");
        }

        $composer = json_decode($content, true);
        if (!is_array($composer)) {
            throw new InvalidArgumentException("Invalid JSON in composer file: {$composerPath}");
        }

        $psr4Mappings = [];

        // Read from autoload.psr-4
        if (isset($composer['autoload']['psr-4']) && is_array($composer['autoload']) && is_array($composer['autoload']['psr-4'])) {
            foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
                if (is_string($namespace)) {
                    $psr4Mappings[$namespace] = self::normalizePath($path);
                }
            }
        }

        // Read from autoload-dev.psr-4
        if (isset($composer['autoload-dev']['psr-4']) && is_array($composer['autoload-dev']) && is_array($composer['autoload-dev']['psr-4'])) {
            foreach ($composer['autoload-dev']['psr-4'] as $namespace => $path) {
                if (is_string($namespace)) {
                    $psr4Mappings[$namespace] = self::normalizePath($path);
                }
            }
        }

        return $psr4Mappings;
    }

    /**
     * Normalize path format
     *
     * @param mixed $path Path or array of paths
     * @return string Normalized path
     */
    private static function normalizePath($path): string
    {
        // Handle array of paths by taking the first one
        if (is_array($path)) {
            $path = $path[0] ?? '';
        }

        // Ensure it's a string
        if (!is_string($path)) {
            return '';
        }

        // Remove trailing slash
        return rtrim($path, '/');
    }
}
