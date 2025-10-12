<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Core;

use Nette\PhpGenerator\PhpFile;
use PhpGen\ClassGenerator\Config\PhpGenConfig;
use PhpGen\ClassGenerator\Blueprint\FileBlueprint;
use PhpGen\ClassGenerator\Generation\CodeWriter;
use PhpGen\ClassGenerator\Generation\FileComposer;

/**
 * Main generator class for creating PHP code elements
 *
 * This class provides a fluent interface for creating various PHP code elements
 * (classes, interfaces, traits, abstract classes) with advanced features like
 * use statement management and configuration callbacks.
 *
 * The Generator uses the Builder pattern to provide type-safe, chainable methods
 * for configuring and generating PHP code.
 *
 * @example Basic usage:
 * $generator = Generator::create()
 *     ->classBuilder('App\\Domain\\User')
 *     ->addUse('Illuminate\\Database\\Eloquent\\Model')
 *     ->configure(function (ClassType $class): ClassType {
 *         $class->setExtends('Model');
 *         return $class;
 *     });
 */
final class Generator
{
    /**
     * Collection of PhpFile objects for direct file-based generation
     * @var array<string, PhpFile>
     */
    private array $files = [];

    /**
     * Unified collection of all builders for streamlined processing
     * @var array<string, FileBlueprint>
     */
    private array $builders = [];

    /**
     * File composer for building PHP files from specs
     */
    private FileComposer $assembler;

    /**
     * Configuration instance
     */
    private PhpGenConfig $config;

    public function __construct(?PhpGenConfig $config = null)
    {
        $this->config = $config ?? new PhpGenConfig();
        $this->assembler = new FileComposer($this->config);
    }

    /**
     * Create a new Generator instance
     *
     * @return self A new Generator instance ready for configuration
     */
    public static function create(?PhpGenConfig $config = null): self
    {
        return new self($config);
    }

    /**
     * Add a pre-configured builder to the generator
     *
     * This method allows adding builders that have been created and configured
     * outside of the generator, providing more flexibility in builder creation.
     *
     * @param FileBlueprint $builder The configured builder to add
     * @return self Returns the generator instance for method chaining
     */
    public function addBuilder(FileBlueprint $builder): self
    {
        $this->builders[$builder->getFullyQualifiedName()] = $builder;
        return $this;
    }


    /**
     * Generate all configured files to disk
     *
     * This method processes all registered builders and files, generating the actual
     * PHP files to the filesystem according to the configured path resolver.
     *
     * @param bool $dryRun If true, files will not be written to disk (useful for testing)
     * @return void
     *
     * @example
     * // Generate files to disk
     * $generator->generate();
     *
     * // Dry run (preview without writing)
     * $generator->generate(true);
     */
    public function generate(bool $dryRun = false): void
    {
        $generator = new CodeWriter(new PathResolver($this->config));

        // Generate files created via file() method
        foreach ($this->files as $namespace => $file) {
            $generator->generateFile($namespace, $file, $dryRun);
        }

        // Generate files from all builders (unified processing)
        foreach ($this->builders as $fullyQualifiedName => $builder) {
            $file = $builder->build($this->assembler);
            $generator->generateFile($fullyQualifiedName, $file, $dryRun);
        }
    }

    /**
     * Preview all configured files without writing to disk
     *
     * This method generates preview data for all registered builders and files,
     * allowing you to inspect the generated code before writing to disk.
     *
     * @return array<array{file_path: string, content: string, class_name: string, namespace: string}>
     *   An array of preview data, each containing file path, content, class name, and namespace
     *
     * @example
     * $previews = $generator->previewGeneration();
     * foreach ($previews as $preview) {
     *     echo "File: {$preview['file_path']}\n";
     *     echo "Content:\n{$preview['content']}\n";
     * }
     */
    public function previewGeneration(): array
    {
        $generator = new CodeWriter(new PathResolver($this->config));
        $preview = [];

        // Preview files created via file() method
        foreach ($this->files as $namespace => $file) {
            $preview[] = $generator->previewFile($namespace, $file);
        }

        // Preview files from all builders (unified processing)
        foreach ($this->builders as $fullyQualifiedName => $builder) {
            $file = $builder->build($this->assembler);
            $preview[] = $generator->previewFile($fullyQualifiedName, $file);
        }

        return $preview;
    }
}
