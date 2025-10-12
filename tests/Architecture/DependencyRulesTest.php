<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class DependencyRulesTest
{
    /**
     * Core layer can only depend on Config, Blueprint, and Generation layers
     */
    public function test_core_layer_dependencies(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpGen\ClassGenerator\Core'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('PhpGen\ClassGenerator\Core'),
                Selector::inNamespace('PhpGen\ClassGenerator\Config'),
                Selector::inNamespace('PhpGen\ClassGenerator\Blueprint'),
                Selector::inNamespace('PhpGen\ClassGenerator\Generation')
            )
            ->because('Core layer should only depend on Config, Blueprint, and Generation layers');
    }

    /**
     * Blueprint layer can depend on Nette, Config, Core, and Generation layers
     */
    public function test_blueprint_layer_dependencies(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpGen\ClassGenerator\Blueprint'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('PhpGen\ClassGenerator\Blueprint'),
                Selector::inNamespace('PhpGen\ClassGenerator\Config'),
                Selector::inNamespace('PhpGen\ClassGenerator\Core'),
                Selector::inNamespace('PhpGen\ClassGenerator\Generation'),
                Selector::inNamespace('Nette')
            )
            ->because('Blueprint layer can depend on Nette, Config, Core, and Generation layers');
    }

    /**
     * Generation layer can depend on Nette, Config, Core, and Blueprint layers
     */
    public function test_generation_layer_dependencies(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpGen\ClassGenerator\Generation'))
            ->canOnlyDependOn()
            ->classes(
                Selector::inNamespace('PhpGen\ClassGenerator\Generation'),
                Selector::inNamespace('PhpGen\ClassGenerator\Config'),
                Selector::inNamespace('PhpGen\ClassGenerator\Core'),
                Selector::inNamespace('PhpGen\ClassGenerator\Blueprint'),
                Selector::inNamespace('Nette')
            )
            ->because('Generation layer can depend on Nette, Config, Core, and Blueprint layers');
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
                Selector::inNamespace('Symfony')
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
                Selector::inNamespace('PhpGen\ClassGenerator\Blueprint'),
                Selector::inNamespace('PhpGen\ClassGenerator\Generation'),
                Selector::inNamespace('PhpGen\ClassGenerator\Mcp'),
                Selector::inNamespace('Symfony'),
                Selector::inNamespace('Nette')
            )
            ->because('Console layer is the UI layer and can depend on everything');
    }

    /**
     * Blueprint layer should not depend on Symfony
     */
    public function test_blueprint_should_not_depend_on_symfony(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpGen\ClassGenerator\Blueprint'))
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace('Symfony'))
            ->because('Blueprint layer should not depend on Symfony');
    }

    /**
     * Generation layer should not depend on Symfony
     */
    public function test_generation_should_not_depend_on_symfony(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('PhpGen\ClassGenerator\Generation'))
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace('Symfony'))
            ->because('Generation layer should not depend on Symfony');
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
