<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Console\Commands;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Property;
use PhpGen\ClassGenerator\Builder\BluePrint;
use PhpGen\ClassGenerator\Core\Project;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @example
 * ./bin/php-gen query:generate User FindUserById --dry-run -vv
 */
#[AsCommand(
    name: 'query:generate',
    description: 'Generate Laravel CQRS Query interface, implementation, and test'
)]
class LaravelCqrsQueryCommand extends Command
{
    protected function configureCommand(): void
    {
        $this
            ->addArgument('context', InputArgument::REQUIRED, 'Context/domain name (e.g., User, Product, Order)')
            ->addArgument('queryName', InputArgument::REQUIRED, 'Query name (e.g., GetUser, FindProducts, SearchOrders)')
            ->addOption('no-query', null, InputOption::VALUE_NONE, 'Skip generating the Query class');
    }

    protected function handle(InputInterface $input, OutputInterface $output): Project
    {
        $project = new Project();

        $project->add($this->createQueryInterface($input));
        if (!$input->getOption('no-query')) {
            $project->add($this->createQuery($input));
        }

        $project->add($this->createResult($input));
        $project->add($this->createQueryImplementation($input));
        $project->add($this->createQueryTest($input));

        return $project;
    }

    /**
     * Get the query name from input arguments
     */
    private static function getQueryNameFromInput(InputInterface $input): string
    {
        $queryNameArg = $input->getArgument('queryName');
        return is_string($queryNameArg) ? $queryNameArg : '';
    }

    /**
     * Get the context from input arguments
     */
    private static function getContextFromInput(InputInterface $input): string
    {
        $contextArg = $input->getArgument('context');
        return is_string($contextArg) ? $contextArg : '';
    }

    protected static function getInterfaceName(InputInterface $input): string
    {
        $queryName = self::getQueryNameFromInput($input);
        $context = self::getContextFromInput($input);

        return "App\\Contracts\\Query\\{$context}\\{$queryName}QueryHandler";
    }

    protected static function getQueryName(InputInterface $input): string
    {
        $queryName = self::getQueryNameFromInput($input);
        $context = self::getContextFromInput($input);

        return "App\\Contracts\\Query\\{$context}\\{$queryName}Query";
    }

    protected static function getResultName(InputInterface $input): string
    {
        $queryName = self::getQueryNameFromInput($input);
        $context = self::getContextFromInput($input);

        return "App\\Contracts\\Query\\{$context}\\{$queryName}Result";
    }

    protected static function getImplementationName(InputInterface $input): string
    {
        $queryName = self::getQueryNameFromInput($input);
        $context = self::getContextFromInput($input);

        return "App\\Infrastructure\\Query\\{$context}\\{$queryName}QueryHandlerImplementation";
    }

    protected static function getImplementationTestName(InputInterface $input): string
    {
        $queryName = self::getQueryNameFromInput($input);
        $context = self::getContextFromInput($input);

        return "Tests\\Feature\\Infrastructure\\Query\\{$context}\\{$queryName}QueryHandlerImplementationTest";
    }

    /**
     * Create the query handler interface
     */
    private function createQueryInterface(InputInterface $input): BluePrint
    {
        return BluePrint::createInterface(self::getInterfaceName($input), function (InterfaceType $interface) use ($input): InterfaceType {
            $queryName = self::getQueryNameFromInput($input);
            $interface->addAttribute('Illuminate\\Container\\Attributes\\Bind', [
                new Literal("{$queryName}QueryHandlerImplementation::class"),
            ]);

            $handle = $interface->addMethod('handle');

            if (!$input->getOption('no-query')) {
                $handle->addParameter('query')
                    ->setType(self::getQueryName($input));
            }

            $interface->getMethod('handle')
                ->setReturnType(self::getResultName($input))
                ->setComment('Execute the query and return the result');

            return $interface;
        });
    }

    /**
     * Create the query handler implementation
     */
    private function createQueryImplementation(InputInterface $input): BluePrint
    {
        return BluePrint::createEmptyClass(self::getImplementationName($input))
            ->defineStructure(function (ClassType $class) use ($input) {
                $class
                    ->setFinal()
                    ->setReadOnly()
                    ->addImplement(self::getInterfaceName($input));

                // Add constructor for dependency injection
                $constructor = $class->addMethod('__construct');
                $constructor->setComment('Constructor for dependency injection');
                $constructor->setBody('// TODO: Add dependencies as needed');

                // Add handle method with typed parameters
                $handle = $class->addMethod('handle');

                if (!$input->getOption('no-query')) {
                    $handle->addParameter('query')
                        ->setType(self::getQueryName($input));
                }

                $handle->setReturnType(self::getResultName($input));
                $handle->setComment('@inheritdoc');
                $handle->setBody('// TODO: Implement query logic' . PHP_EOL);

                return $class;
            });
    }

    /**
     * Create the query class
     */
    private function createQuery(InputInterface $input): BluePrint
    {
        return BluePrint::createClass(self::getQueryName($input), function (ClassType $class): ClassType {
            $class
                ->setFinal()
                ->setReadOnly();

            // Add constructor with basic parameters
            $constructor = $class->addMethod('__construct');
            $constructor->setComment('Query constructor with parameters');
            $constructor->setBody('// TODO: Add query parameters as needed');
            $constructor->addPromotedParameter('id')
                ->setType('int')
                ->setComment('Entity ID');

            return $class;
        });
    }

    /**
     * Create the result class
     */
    private function createResult(InputInterface $input): BluePrint
    {
        return BluePrint::createClass(self::getResultName($input), static function (ClassType $class): ClassType {
            $class
                ->setFinal()
                ->setReadOnly();

            // Add constructor with result data
            $constructor = $class->addMethod('__construct');
            $constructor->setComment('Result constructor with data');
            $constructor->setBody('// TODO: Add result properties as needed');
            $constructor->addPromotedParameter('data')
                ->setType('mixed')
                ->setComment('Query result data');

            return $class;
        });
    }

    /**
     * Create the feature test
     */
    private function createQueryTest(InputInterface $input): BluePrint
    {
        return BluePrint::createEmptyClass(self::getImplementationTestName($input))
            ->defineStructure(function (ClassType $class) use ($input) {
                $class->setExtends('Tests\\TestCase');
                $class->addTrait('Illuminate\\Foundation\\Testing\\RefreshDatabase');

                $class->setProperties([
                    (new Property('queryHandler'))->setType(self::getImplementationName($input)),
                ]);

                // Add setUp method
                $setUp = $class->addMethod('setUp');
                $setUp->setReturnType('void');
                $queryName = self::getQueryNameFromInput($input);
                $implementationClass = "{$queryName}QueryHandlerImplementation";
                $setUp->setBody(
                    'parent::setUp();' . PHP_EOL . PHP_EOL .
                    '$this->queryHandler = $this->app->make(' . $implementationClass . '::class);'
                );

                // Add test for successful execution
                $testHandle = $class->addMethod('testHandleReturnsExpectedResult');
                $testHandle->setReturnType('void');
                $testHandle->setBody('// TODO: Implement test for successful query execution');

                // Add test for edge cases
                $testEdgeCase = $class->addMethod('testHandleWithInvalidData');
                $testEdgeCase->setReturnType('void');
                $testEdgeCase->setBody('// TODO: Implement test for edge cases');

                return $class;
            });
    }
}
