<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class DependencyRulesTest
{
    /**
     * Core layer can only depend on Config and Builder layers
     */
    public function test_core_layer_dependencies(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpGen\ClassGenerator\Core'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('PhpGen\ClassGenerator\Core'),
                Selector::inNamespace('PhpGen\ClassGenerator\Config'),
                Selector::inNamespace('PhpGen\ClassGenerator\Builder'),
                // PHP standard library
                Selector::inNamespace('')
            )
            ->because('Core layer should only depend on Config and Builder layers');
    }

    /**
     * Builder layer can depend on Nette, Config, and Core layers
     */
    public function test_builder_layer_dependencies(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpGen\ClassGenerator\Builder'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('PhpGen\ClassGenerator\Builder'),
                Selector::inNamespace('PhpGen\ClassGenerator\Config'),
                Selector::inNamespace('PhpGen\ClassGenerator\Core'),
                Selector::inNamespace('Nette'),
                // PHP standard library
                Selector::inNamespace('')
            )
            ->because('Builder layer can depend on Nette, Config, and Core layers');
    }

    /**
     * Config layer can depend on Console and Symfony (for CommandRegistry)
     */
    public function test_config_layer_dependencies(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpGen\ClassGenerator\Config'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('PhpGen\ClassGenerator\Config'),
                Selector::inNamespace('PhpGen\ClassGenerator\Console'),
                Selector::inNamespace('Symfony'),
                // PHP standard library
                Selector::inNamespace('')
            )
            ->because('Config layer can depend on Console and Symfony layers');
    }

    /**
     * Console layer can depend on all layers (UI layer)
     */
    public function test_console_layer_dependencies(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpGen\ClassGenerator\Console'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('PhpGen\ClassGenerator\Console'),
                Selector::inNamespace('PhpGen\ClassGenerator\Core'),
                Selector::inNamespace('PhpGen\ClassGenerator\Config'),
                Selector::inNamespace('PhpGen\ClassGenerator\Builder'),
                Selector::inNamespace('Symfony'),
                Selector::inNamespace('Nette'),
                // PHP standard library
                Selector::inNamespace('')
            )
            ->because('Console layer is the UI layer and can depend on everything');
    }

    /**
     * Builder layer should not depend on Symfony
     */
    public function test_builder_should_not_depend_on_symfony(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpGen\ClassGenerator\Builder'))
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace('Symfony'))
            ->because('Builder layer should not depend on Symfony');
    }

    /**
     * Core layer should not depend on Symfony
     */
    public function test_core_should_not_depend_on_symfony(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpGen\ClassGenerator\Core'))
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace('Symfony'))
            ->because('Core layer should not depend on Symfony');
    }

    /**
     * Config layer should not depend on Nette
     */
    public function test_config_should_not_depend_on_nette(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpGen\ClassGenerator\Config'))
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace('Nette'))
            ->because('Config layer should not depend on Nette');
    }
}
