<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Console\Commands\Example\Laravel;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Property;
use PhpGen\ClassGenerator\Builder\BluePrint;
use PhpGen\ClassGenerator\Console\Commands\Command;
use PhpGen\ClassGenerator\Core\Project;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @example
 * ./bin/php-gen command:generate User CreateUser --dry-run -vv
 */
#[AsCommand(
    name: 'command:generate',
    description: 'Generate Laravel CQRS Command interface, implementation, and test'
)]
class LaravelCqrsCommandCommand extends Command
{
    protected function configureCommand(): void
    {
        $this
            ->addArgument('context', InputArgument::REQUIRED, 'Context/domain name (e.g., User, Product, Order)')
            ->addArgument('commandName', InputArgument::REQUIRED, 'Command name (e.g., CreateUser, UpdateProduct, DeleteOrder)')
            ->addOption('no-return-id', null, InputOption::VALUE_NONE, 'Do not return ID (void return type)')
            ->addOption('no-command', null, InputOption::VALUE_NONE, 'Skip generating the Command class');
    }

    protected function handle(InputInterface $input, OutputInterface $output): Project
    {
        $project = new Project();

        $project->add($this->createCommandInterface($input));
        if (!$input->getOption('no-command')) {
            $project->add($this->createCommand($input));
        }

        $project->add($this->createCommandImplementation($input));
        $project->add($this->createCommandTest($input));

        return $project;
    }

    /**
     * Get the command name from input arguments
     */
    private static function getCommandNameFromInput(InputInterface $input): string
    {
        $commandNameArg = $input->getArgument('commandName');
        return is_string($commandNameArg) ? $commandNameArg : '';
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
        $commandName = self::getCommandNameFromInput($input);
        $context = self::getContextFromInput($input);

        return "App\\Contracts\\Command\\{$context}\\{$commandName}CommandHandler";
    }

    protected static function getCommandName(InputInterface $input): string
    {
        $commandName = self::getCommandNameFromInput($input);
        $context = self::getContextFromInput($input);

        return "App\\Contracts\\Command\\{$context}\\{$commandName}Command";
    }

    protected static function getImplementationName(InputInterface $input): string
    {
        $commandName = self::getCommandNameFromInput($input);
        $context = self::getContextFromInput($input);

        return "App\\Infrastructure\\Command\\{$context}\\{$commandName}CommandHandlerImplementation";
    }

    protected static function getImplementationTestName(InputInterface $input): string
    {
        $commandName = self::getCommandNameFromInput($input);
        $context = self::getContextFromInput($input);

        return "Tests\\Feature\\Infrastructure\\Command\\{$context}\\{$commandName}CommandHandlerImplementationTest";
    }

    /**
     * Get the return type for the command handler
     */
    protected static function getReturnType(InputInterface $input): string
    {
        return $input->getOption('no-return-id') ? 'void' : 'int';
    }

    /**
     * Create the command handler interface
     */
    private function createCommandInterface(InputInterface $input): BluePrint
    {
        return BluePrint::createInterface(self::getInterfaceName($input), function (InterfaceType $interface) use ($input): InterfaceType {
            $commandName = self::getCommandNameFromInput($input);
            $interface->addAttribute('Illuminate\\Container\\Attributes\\Bind', [
                new Literal("{$commandName}CommandHandlerImplementation::class"),
            ]);

            $handle = $interface->addMethod('handle');

            if (!$input->getOption('no-command')) {
                $handle->addParameter('command')
                    ->setType(self::getCommandName($input));
            }

            $returnType = self::getReturnType($input);
            $interface
                ->getMethod('handle')
                ->setReturnType($returnType)
                ->setComment($returnType === 'int'
                    ? 'Execute the command and return the generated ID'
                    : 'Execute the command')
            ;

            return $interface;
        });
    }

    /**
     * Create the command handler implementation
     */
    private function createCommandImplementation(InputInterface $input): BluePrint
    {
        return BluePrint::createEmptyClass(self::getImplementationName($input))
            ->defineStructure(function (ClassType $class) use ($input) {
                $class
                    ->setFinal()
                    ->setReadOnly()
                    ->addImplement(self::getInterfaceName($input))
                ;

                $class
                    ->addMethod('__construct')
                    ->addPromotedParameter("connection")
                    ->setType('Illuminate\\Database\\ConnectionInterface')
                    ->setPrivate()
                ;

                // Add handle method with typed parameters
                $handle = $class->addMethod('handle');
                $returnType = self::getReturnType($input);
                $handle->setReturnType($returnType);
                $handle->setComment('@inheritdoc');

                if ($returnType === 'int') {
                    $handle->setBody(<<<'PHP'
// TODO: Implement command logic (INSERT/UPDATE/DELETE)
// Example for INSERT:
// $this->connection->table('users')->insert([
//     'name' => $command->name,
//     'email' => $command->email,
// ]);
// return (int) $this->connection->getPdo()->lastInsertId();

// For transactions:
// return $this->connection->transaction(function () use ($command) {
//     $this->connection->table('users')->insert([...]);
//     return (int) $this->connection->getPdo()->lastInsertId();
// });

throw new \RuntimeException('Not implemented');
PHP);
                } else {
                    $handle->setBody(<<<'PHP'
// TODO: Implement command logic (INSERT/UPDATE/DELETE)
// Example for UPDATE/DELETE:
// $this->connection->table('users')
//     ->where('id', $command->id)
//     ->update(['name' => $command->name]);

// For transactions:
// $this->connection->transaction(function () use ($command) {
//     $this->connection->table('users')->where(...)->update([...]);
// });

throw new \RuntimeException('Not implemented');
PHP);
                }

                if (!$input->getOption('no-command')) {
                    $handle->addParameter('command')
                        ->setType(self::getCommandName($input));
                }

                return $class;
            });
    }

    /**
     * Create the command class
     */
    private function createCommand(InputInterface $input): BluePrint
    {
        return BluePrint::createClass(self::getCommandName($input), function (ClassType $class): ClassType {
            $class
                ->setFinal()
                ->setReadOnly()
            ;

            $class
                ->addMethod('__construct')
                ->setBody('// TODO: Add command parameters as needed')
                ->addPromotedParameter('id')
                ->setType('int')
            ;

            return $class;
        });
    }

    /**
     * Create the feature test
     */
    private function createCommandTest(InputInterface $input): BluePrint
    {
        return BluePrint::createEmptyClass(self::getImplementationTestName($input))
            ->defineStructure(function (ClassType $class) use ($input) {
                $class->setExtends('Tests\\TestCase');
                $class->addTrait('Illuminate\\Foundation\\Testing\\RefreshDatabase');

                $class->setProperties([
                    (new Property('commandHandler'))->setType(self::getImplementationName($input))->setPrivate(),
                ]);

                // Add setUp method
                $setUp = $class->addMethod('setUp');
                $setUp->setReturnType('void');
                $commandName = self::getCommandNameFromInput($input);
                $implementationClass = "{$commandName}CommandHandlerImplementation";
                $setUp->setBody(
                    'parent::setUp();' . PHP_EOL . PHP_EOL .
                    '$this->commandHandler = $this->app->make(' . $implementationClass . '::class);'
                );

                // Add test for successful execution
                $testHandle = $class->addMethod('testHandleExecutesSuccessfully');
                $testHandle->setReturnType('void');
                $returnType = self::getReturnType($input);
                if ($returnType === 'int') {
                    $testHandle->setBody('// TODO: Implement test for successful command execution with ID return');
                } else {
                    $testHandle->setBody('// TODO: Implement test for successful command execution');
                }

                // Add test for edge cases
                $testEdgeCase = $class->addMethod('testHandleWithInvalidData');
                $testEdgeCase->setReturnType('void');
                $testEdgeCase->setBody('// TODO: Implement test for edge cases');

                return $class;
            });
    }
}
