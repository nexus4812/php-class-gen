<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Console\Commands;

use PhpGen\ClassGenerator\Builder\BluePrint;
use PhpGen\ClassGenerator\Core\Project;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'class:create',
    description: 'Create a simple DTO class with readonly properties'
)]
class CreateClassCommand extends Command
{
    protected function configureCommand(): void
    {
        $this
            ->addArgument('fully-qualified-name', InputArgument::REQUIRED, 'Class name');
    }

    protected function handle(InputInterface $input, OutputInterface $output): Project
    {
        $project = new Project();

        $className = $input->getArgument('fully-qualified-name');;
        $project->add(BluePrint::createEmptyClass($className));

        return $project;
    }
}
