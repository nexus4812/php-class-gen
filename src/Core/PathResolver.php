<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Core;

use PhpGen\ClassGenerator\Config\PhpGenConfig;

final class PathResolver
{
    public function __construct(
        private PhpGenConfig $config,
        private string $fallbackRoot = 'src'
    ) {
    }

    public function resolveFromNamespace(string $namespace): string
    {
        // Remove class name from namespace if it exists
        // This handles cases where full qualified class name is passed instead of just namespace
        $lastBackslash = strrpos($namespace, '\\');
        if ($lastBackslash !== false) {
            $possibleClassName = substr($namespace, $lastBackslash + 1);
            // If last part starts with uppercase, it might be a class name
            if (ctype_upper($possibleClassName[0] ?? '')) {
                $namespace = substr($namespace, 0, $lastBackslash);
            }
        }

        $match = $this->findBestMatch($namespace);

        if ($match === null) {
            // Fallback to default behavior
            $path = str_replace('\\', '/', $namespace);
            return $this->fallbackRoot . '/' . $path;
        }

        // Calculate remaining namespace after removing matched prefix
        $matchedNamespace = rtrim($match['namespace'], '\\');
        $remainingNamespace = str_replace($matchedNamespace, '', $namespace);
        $remainingNamespace = ltrim($remainingNamespace, '\\');
        $remainingPath = str_replace('\\', '/', $remainingNamespace);

        $basePath = $match['path'];

        if (empty($remainingPath)) {
            return $basePath;
        }

        return $basePath . '/' . $remainingPath;
    }

    /**
     * Find the best matching PSR-4 mapping for the given namespace
     *
     * @param string $namespace The namespace to find a match for
     * @return array{namespace: string, path: string}|null The best match or null if no match found
     */
    private function findBestMatch(string $namespace): ?array
    {
        $psr4Mappings = $this->config->getPsr4Mappings();
        $bestMatch = null;
        $bestMatchLength = 0;

        foreach ($psr4Mappings as $prefix => $path) {
            // Normalize the prefix by ensuring it ends with backslash
            $normalizedPrefix = rtrim($prefix, '\\') . '\\';

            // Check if the namespace starts with this prefix
            if (str_starts_with($namespace . '\\', $normalizedPrefix)) {
                $matchLength = strlen($normalizedPrefix);

                // Keep the longest match (most specific)
                if ($matchLength > $bestMatchLength) {
                    $bestMatch = [
                        'namespace' => $normalizedPrefix,
                        'path' => rtrim($path, '/'),
                    ];
                    $bestMatchLength = $matchLength;
                }
            }
        }

        return $bestMatch;
    }
}
