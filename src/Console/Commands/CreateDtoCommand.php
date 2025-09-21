<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Console\Commands;

use Nette\PhpGenerator\ClassType;
use PhpGen\ClassGenerator\Builder\Builder;
use PhpGen\ClassGenerator\Core\PropertyTypeParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use InvalidArgumentException;

/**
 * @example
 * ./bin/php-gen dto:create "App\\DTOs\\User\\UserDto" --properties="id:int,name:string,email:string,address:App\\DTOs\\Address\\AddressDto" --dry-run -vv
 */
#[AsCommand(
    name: 'dto:create',
    description: 'Create a simple DTO class with readonly properties'
)]
class CreateDtoCommand extends Command
{
    protected function configureCommand(): void
    {
        $this
            ->addArgument('fully-qualified-name', InputArgument::REQUIRED, 'DTO class name')
            ->addOption('properties', 'p', InputOption::VALUE_OPTIONAL, 'Properties (format: name:type,email:string)', 'id:int');
    }

    protected function handle(InputInterface $input, OutputInterface $output): void
    {
        $name = $input->getArgument('fully-qualified-name');
        $propertiesString = $input->getOption('properties');

        if (!is_string($name)) {
            throw new InvalidArgumentException('Class name must be a string');
        }

        if (!is_string($propertiesString) && $propertiesString !== null) {
            throw new InvalidArgumentException('Properties must be a string');
        }

        $builder = $this->getBuilder();
        $builder->add($this->createDto($name, $propertiesString ?? 'id:int'));
    }

    private function createDto(string $name, string $propertiesString): Builder
    {
        $properties = PropertyTypeParser::parse($propertiesString);

        return Builder::createClass($name)
            ->defineStructure(function (ClassType $class) use ($properties) {
                $class->setFinal()
                     ->setReadOnly();

                // Add constructor with promoted parameters
                $constructor = $class->addMethod('__construct');

                foreach ($properties as $propertyName => $propertyType) {
                    $constructor->addPromotedParameter($propertyName)
                              ->setType($propertyType)
                              ->setPublic();
                }

                return $class;
            });
    }

}
