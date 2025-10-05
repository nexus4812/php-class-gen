<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Blueprint;

use Nette\PhpGenerator\PhpFile;
use PhpGen\ClassGenerator\Generation\FileComposer;

/**
 * Interface for all code element blueprints
 */
interface BlueprintInterface
{
    /**
     * Build and return the complete PHP file
     *
     * @param FileComposer $assembler The file composer to use for building
     * @return PhpFile The generated PHP file
     */
    public function build(FileComposer $assembler): PhpFile;

    /**
     * Get the fully qualified name of the element being built
     *
     * @return string The fully qualified class/interface/trait name
     */
    public function getFullyQualifiedName(): string;
}
