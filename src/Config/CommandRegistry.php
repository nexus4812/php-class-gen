<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Config;

use InvalidArgumentException;
use PhpGen\ClassGenerator\Console\Commands\Command;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Application;
use Throwable;

/**
 * Command registry for managing and instantiating generator commands
 *
 * This class is responsible for registering commands from the configuration,
 * validating them, and providing them to the console application.
 * It ensures that all commands extend AbstractGeneratorCommand and can be properly instantiated.
 */
final class CommandRegistry
{
    /**
     * @var array<string, class-string<Command>>
     */
    private array $registeredCommands = [];

    /**
     * Create a new CommandRegistry instance
     *
     * @param PhpGenConfig $config The configuration containing command definitions
     */
    public function __construct(
        private readonly PhpGenConfig $config
    ) {
        $this->registerCommandsFromConfig();
    }

    /**
     * Register all commands from the configuration
     *
     * This method processes the commands defined in the PhpGenConfig and
     * validates that they are proper command classes.
     *
     * @return void
     * @throws InvalidArgumentException If any command is invalid
     */
    private function registerCommandsFromConfig(): void
    {
        foreach ($this->config->getCommands() as $commandClass) {
            $this->validateAndRegisterCommand($commandClass);
        }
    }

    /**
     * Validate and register a single command class
     *
     * @param class-string $commandClass The command class to validate and register
     * @return void
     * @throws InvalidArgumentException If the command class is invalid
     */
    private function validateAndRegisterCommand(string $commandClass): void
    {
        // Check if class exists
        if (!class_exists($commandClass)) {
            throw new InvalidArgumentException("Command class '{$commandClass}' does not exist");
        }

        // Check if class extends AbstractGeneratorCommand
        if (!is_subclass_of($commandClass, Command::class)) {
            throw new InvalidArgumentException(
                "Command class '{$commandClass}' must extend " . Command::class
            );
        }

        // Check if class can be instantiated
        $reflection = new ReflectionClass($commandClass);
        if ($reflection->isAbstract()) {
            throw new InvalidArgumentException("Command class '{$commandClass}' cannot be abstract");
        }

        // Get the command name to use as registry key
        $commandName = $this->getCommandName($commandClass);

        // Check for duplicate command names
        if (isset($this->registeredCommands[$commandName])) {
            throw new InvalidArgumentException(
                "Duplicate command name '{$commandName}' found. " .
                "Commands '{$this->registeredCommands[$commandName]}' and '{$commandClass}' " .
                "have the same name."
            );
        }

        $this->registeredCommands[$commandName] = $commandClass;
    }

    /**
     * Get the command name from a command class
     *
     * This method instantiates the command temporarily to get its name.
     *
     * @param class-string<Command> $commandClass
     * @return string The command name
     */
    private function getCommandName(string $commandClass): string
    {
        // Create a temporary instance to get the name
        $tempInstance = new $commandClass($this->config);
        $name = $tempInstance->getName();

        return $name ?? '';
    }

    /**
     * Create and return all registered command instances
     *
     * This method instantiates all registered commands and returns them
     * as an array ready to be added to the console application.
     *
     * @return array<Command> Array of command objects
     */
    public function createCommands(): array
    {
        $commands = [];

        foreach ($this->registeredCommands as $commandName => $commandClass) {
            try {
                $command = new $commandClass($this->config);
                $commands[] = $command;
            } catch (Throwable $e) {
                throw new RuntimeException(
                    "Failed to instantiate command '{$commandClass}': " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        return $commands;
    }

    /**
     * Register commands with a Symfony Console Application
     *
     * This is a convenience method that creates all commands and adds them
     * to the provided console application.
     *
     * @param Application $application The console application to register commands with
     * @return void
     */
    public function registerWithApplication(Application $application): void
    {
        $commands = $this->createCommands();

        foreach ($commands as $command) {
            $application->add($command);
        }
    }
}
