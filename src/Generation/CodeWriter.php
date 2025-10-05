<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Generation;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use PhpGen\ClassGenerator\Core\PathResolver;

final class CodeWriter
{
    private PsrPrinter $printer;
    private PathResolver $pathResolver;

    public function __construct(PathResolver $pathResolver)
    {
        $this->printer = new PsrPrinter();
        $this->pathResolver = $pathResolver;
    }

    public function generateFile(string $namespace, PhpFile $file, bool $dryRun = false): void
    {
        if ($dryRun) {
            return;
        }

        $path = $this->pathResolver->resolveFromNamespace($namespace);

        // Get the first class name for filename from namespaces
        $namespaces = $file->getNamespaces();
        $namespaceObj = $namespaces[$namespace] ?? reset($namespaces);

        if (!$namespaceObj) {
            return;
        }

        // Try to get the first available class/interface/trait name
        $className = null;

        // Get all types (classes, interfaces, traits, enums)
        $classes = $namespaceObj->getClasses();
        if (!empty($classes)) {
            $firstKey = array_key_first($classes);
            $className = is_string($firstKey) ? $firstKey : null;
        }

        if ($className === null) {
            $className = 'Generated';
        }

        $filename = $className . '.php';
        $fullPath = $path . '/' . $filename;

        if (!is_dir($path)) {
            mkdir($path, 0o755, true);
        }

        file_put_contents($fullPath, $this->printer->printFile($file));
    }

    /**
     * @return array{file_path: string, content: string, class_name: string, namespace: string}
     */
    public function previewFile(string $namespace, PhpFile $file): array
    {
        $path = $this->pathResolver->resolveFromNamespace($namespace);

        // Get the first class name for filename from namespaces
        $namespaces = $file->getNamespaces();
        $namespaceObj = $namespaces[$namespace] ?? reset($namespaces);

        if (!$namespaceObj) {
            return [
                'file_path' => 'unknown.php',
                'content' => '',
                'class_name' => 'Unknown',
                'namespace' => $namespace,
            ];
        }

        // Try to get the first available class/interface/trait name
        $className = null;

        // Get all types (classes, interfaces, traits, enums)
        $classes = $namespaceObj->getClasses();
        if (!empty($classes)) {
            $firstKey = array_key_first($classes);
            $className = is_string($firstKey) ? $firstKey : null;
        }

        if ($className === null) {
            $className = 'Generated';
        }

        $filename = $className . '.php';
        $fullPath = $path . '/' . $filename;

        return [
            'file_path' => $fullPath,
            'content' => $this->printer->printFile($file),
            'class_name' => $className,
            'namespace' => $namespace,
        ];
    }
}
