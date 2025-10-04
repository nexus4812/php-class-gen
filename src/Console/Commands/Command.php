<?php

declare(strict_types=1);

namespace PhpGen\ClassGenerator\Console\Commands;

use InvalidArgumentException;
use PhpGen\ClassGenerator\Builder\BluePrint;
use PhpGen\ClassGenerator\Config\PhpGenConfig;
use PhpGen\ClassGenerator\Core\Project;
use PhpGen\ClassGenerator\Core\Generator;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

abstract class Command extends SymfonyCommand
{
    protected PhpGenConfig $config;
    protected Project $generationCollection;

    public function __construct(PhpGenConfig $config)
    {
        $this->config = $config;
        parent::__construct();
    }

    /**
     * Define what files should be generated using the builder pattern
     *
     * This is the main method that subclasses must implement. It should use
     * the getBuilder() method to access the GenerationCollection and define
     * what files should be created and under what conditions.
     *
     * @param InputInterface $input The command input
     * @param OutputInterface $output
     * @return Project
     */
    abstract protected function handle(InputInterface $input, OutputInterface $output): Project;

    /**
     * Add a builder to the generation collection
     *
     * @param BluePrint $builder The builder to add
     * @return void
     */
    protected function add(BluePrint $builder): void
    {
        $this->generationCollection->add($builder);
    }

    final protected function configure(): void
    {
        // Add common options that all generator commands should have
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Preview the files that would be generated without creating them'
        );

        $this->configureCommand();
    }

    protected function configureCommand(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Validate configuration
            $this->config->validate();
            if ($output->isVerbose()) {
                $this->displayConfigurationInfo($io);
            }

            $generator = $this->handle($input, $output)->build($this->config);

            return $input->getOption('dry-run')
                ? $this->executePreview($generator, $io)
                : $this->executeGeneration($generator, $io);

        } catch (InvalidArgumentException $e) {
            $io->error('Configuration error: ' . $e->getMessage());
            return SymfonyCommand::FAILURE;
        } catch (Throwable $e) {
            $io->error('Generation failed: ' . $e->getMessage());

            if ($output->isVerbose()) {
                $io->writeln('<comment>Stack trace:</comment>');
                $io->writeln($e->getTraceAsString());
            }

            return SymfonyCommand::FAILURE;
        }
    }

    /**
     * Execute the actual file generation
     *
     * @param Generator $generator The configured generator
     * @param SymfonyStyle $io The styled I/O interface
     * @return int The exit code
     */
    protected function executeGeneration(Generator $generator, SymfonyStyle $io): int
    {
        $io->info('Generating files...');

        $generator->generate();

        $io->success('Code generation completed successfully!');

        return SymfonyCommand::SUCCESS;
    }

    /**
     * Execute preview mode (dry-run)
     *
     * @param Generator $generator The configured generator
     * @param SymfonyStyle $io The styled I/O interface
     * @return int The exit code
     */
    protected function executePreview(Generator $generator, SymfonyStyle $io): int
    {
        $io->info('Preview mode - no files will be created');

        $previews = $generator->previewGeneration();

        if (empty($previews)) {
            $io->warning('No files would be generated');
            return SymfonyCommand::SUCCESS;
        }

        $io->success('Files that would be generated:');

        foreach ($previews as $preview) {
            $io->section($preview['class_name']);
            $io->writeln("<info>File:</info> {$preview['file_path']}");
            $io->writeln("<comment>Class:</comment> {$preview['class_name']}");
            $io->writeln("<comment>Namespace:</comment> {$preview['namespace']}");

            if ($io->isVerbose()) {
                $io->writeln('<comment>Content preview:</comment>');
                $io->writeln($this->formatCodePreview($preview['content'], $io));
            }

            $io->newLine();
        }

        if (!$io->isVerbose()) {
            $io->note('Use -v flag to see content preview');
        }

        return SymfonyCommand::SUCCESS;
    }

    /**
     * @param string $content
     * @param SymfonyStyle $io
     * @return string
     */
    protected function formatCodePreview(string $content, SymfonyStyle $io): string
    {
        if ($io->isVeryVerbose()) {
            return $content;
        }

        $lines = explode(PHP_EOL, $content);

        $previewLines = array_slice($lines, 0, 20); // Show first 20 lines

        $formatted = implode(PHP_EOL, $previewLines);

        if (count($lines) > 20) {
            $formatted .= "\n<comment>... (truncated, " . (count($lines) - 20) . " more lines)</comment>";
            $io->note('Use -vv flag to see more content preview');
        }

        return $formatted;
    }

    /**
     * Display configuration information in verbose mode
     *
     * @param SymfonyStyle $io The styled I/O interface
     * @return void
     */
    protected function displayConfigurationInfo(SymfonyStyle $io): void
    {
        $io->section('Configuration');

        $rows = [
            ['Use Strict Types', $this->config->getStrictTypes() ? 'Yes' : 'No'],
        ];

        // Add PSR-4 mappings
        foreach ($this->config->getPsr4Mappings() as $namespace => $directory) {
            $rows[] = ["PSR-4 Mapping", "{$namespace} => {$directory}"];
        }

        $io->table(['Setting', 'Value'], $rows);
    }

}
