<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Builder;

use Nette\PhpGenerator\PhpFile;

/**
 * Interface for all code element builders
 */
interface BuilderInterface
{
    /**
     * Build and return the complete PHP file
     *
     * @param FileAssembler $assembler The file assembler to use for building
     * @return PhpFile The generated PHP file
     */
    public function build(FileAssembler $assembler): PhpFile;

    /**
     * Get the fully qualified name of the element being built
     *
     * @return string The fully qualified class/interface/trait name
     */
    public function getFullyQualifiedName(): string;
}
