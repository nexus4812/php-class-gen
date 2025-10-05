
**This project is currently in draft stage**

Breaking changes may be made until official deployment

# PHP Class Generator

A powerful and flexible PHP code generation library that allows you to create custom code generators using a command-based architecture. Perfect for generating boilerplate code, CQRS patterns, domain objects, and more.

## 🚀 Quick Start

### Basic Usage

1. **Create a configuration file** (`phpgen.php` in your project root):

```php
<?php

use PhpGen\ClassGenerator\Config\PhpGenConfig;use PhpGen\ClassGenerator\Console\Commands\Example\Laravel\LaravelCqrsQueryCommand;

return PhpGenConfig::configure()
    ->withCommands([
        LaravelCqrsQueryCommand::class,
    ])
    ->withComposerAutoload()  // Load PSR-4 mappings from composer.json
    ->withStrictTypes(true);
```

2. **Generate code using the CLI**:

```bash
# Generate a CQRS Query pattern
vendor/bin/php-gen query:generate GetUser User

# Preview what would be generated (dry-run)
vendor/bin/php-gen query:generate GetUser User --dry-run

# See detailed preview with file contents
vendor/bin/php-gen query:generate GetUser User --dry-run -v
```

## 📋 Table of Contents

- [Installation & Setup](#-installation--setup)
- [Built-in Commands](#-built-in-commands)
- [Creating Custom Commands](#-creating-custom-commands)
- [Configuration](#-configuration)
- [Examples](#-examples)
- [Advanced Usage](#-advanced-usage)
- [Troubleshooting](#-troubleshooting)

## 🛠 Installation & Setup

### 1. Install via Composer

```bash
composer require php-gen/class-generator
```

### 2. Create Configuration File

Create `phpgen.php` in your project root:

```php
<?php

use PhpGen\ClassGenerator\Config\PhpGenConfig;use PhpGen\ClassGenerator\Console\Commands\Example\Laravel\LaravelCqrsQueryCommand;

return PhpGenConfig::configure()
    ->withCommands([
        LaravelCqrsQueryCommand::class,
        // Add your custom commands here
    ])
    ->withComposerAutoload()  // Automatically load from composer.json
    ->withPsr4Mapping('App\\', 'app')  // Additional mappings
    ->withStrictTypes(true);
```

### 3. Verify Installation

```bash
vendor/bin/php-gen --help
```

## 🎯 Built-in Commands

### Laravel CQRS Query Generator

Generates a complete CQRS Query pattern for Laravel applications.

```bash
vendor/bin/php-gen query:generate GetUser User
```

**Generated files:**
- `app/Contracts/User/Query/GetUserQuery.php` - Query data class
- `app/Contracts/User/Result/GetUserResult.php` - Result data class
- `app/Contracts/Queries/User/GetUserQueryHandler.php` - Interface with Laravel Bind attribute
- `app/Infrastructure/Queries/User/GetUserQueryImplementation.php` - Implementation
- `tests/Feature/Contracts/User/Query/GetUserQueryTest.php` - PHPUnit test

**Options:**
- `--no-query` - Skip generating the Query class
- `--dry-run` - Preview without creating files
- `-v` - Show detailed preview

## 🔧 Creating Custom Commands

### Step 1: Create Your Command Class

```php
<?php

namespace App\Commands;

use Nette\PhpGenerator\ClassType;
use PhpGen\ClassGenerator\Builder\BluePrint;
use PhpGen\ClassGenerator\Console\Commands\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'generate:model',
    description: 'Generate an Eloquent model with relationships'
)]
final class ModelGeneratorCommand extends Command
{
    protected function configureCommand(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Model name');
    }

    protected function handle(InputInterface $input, OutputInterface $output): void
    {
        $modelName = $input->getArgument('name');
        $builder = $this->getBuilder();

        // Add your model generation logic
        $builder->add($this->createModel($modelName));
        $builder->add($this->createFactory($modelName));
        $builder->add($this->createMigration($modelName));
    }

    private function createModel(string $modelName): BluePrint
    {
        $className = $this->config->resolveNamespace("Models\\{$modelName}");

        return BluePrint::createEmptyClass($className)
            ->defineStructure(function (ClassType $class) use ($modelName) {
                $class->setExtends('Illuminate\\Database\\Eloquent\\Model')
                      ->setFinal();

                // Add properties and methods
                $class->addProperty('fillable', [])
                      ->setProtected()
                      ->setType('array')
                      ->addComment('@var array<string>');

                return $class;
            });
    }

    private function createFactory(string $modelName): BluePrint
    {
        $className = $this->config->resolveNamespace("Database\\Factories\\{$modelName}Factory");

        return BluePrint::createEmptyClass($className)
            ->defineStructure(function (ClassType $class) use ($modelName) {
                $class->setExtends('Illuminate\\Database\\Eloquent\\Factories\\Factory');

                // Add definition method
                $class->addMethod('definition')
                      ->setReturnType('array')
                      ->setBody('return [
            // TODO: Add factory definitions
        ];');

                return $class;
            });
    }

    private function createMigration(string $modelName): BluePrint
    {
        $tableName = strtolower($modelName) . 's';
        $className = $this->config->resolveNamespace("Database\\Migrations\\Create{$modelName}sTable");

        return BluePrint::createEmptyClass($className)
            ->defineStructure(function (ClassType $class) use ($tableName) {
                $class->setExtends('Illuminate\\Database\\Migrations\\Migration');

                // Add up method
                $class->addMethod('up')
                      ->setReturnType('void')
                      ->setBody("Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            // TODO: Add table columns
            \$table->timestamps();
        });");

                // Add down method
                $class->addMethod('down')
                      ->setReturnType('void')
                      ->setBody("Schema::dropIfExists('{$tableName}');");

                return $class;
            });
    }
}
```

### Step 2: Register Your Command

Add to your `phpgen.php`:

```php
return PhpGenConfig::configure()
    ->withCommands([
        \PhpGen\ClassGenerator\Console\Commands\Example\Laravel\LaravelCqrsQueryCommand::class,
        \App\Commands\ModelGeneratorCommand::class,  // Your custom command
    ])
    ->withComposerAutoload()
    ->withStrictTypes(true);
```

### Step 3: Use Your Command

```bash
vendor/bin/php-gen generate:model User --dry-run
```

## ⚙️ Configuration

### PSR-4 Mapping Management

Handle complex namespace scenarios with priority mappings:

```php
return PhpGenConfig::configure()
    ->withComposerAutoload()  // Load composer.json mappings
    ->withPsr4Mapping('App\\', 'app')  // Normal mapping
    ->withPriorityPsr4Mapping('Tests\\', 'tests')  // Override composer mapping
    ->withPriorityPsr4Mapping('Legacy\\', 'legacy')  // Handle legacy code
    ->withStrictTypes(true);
```

**Mapping Rules:**
- Priority mappings override normal mappings
- Duplicates within same priority level cause errors
- Duplicates between priority levels are allowed (priority wins)

### Configuration Methods

```php
PhpGenConfig::configure()
    ->withCommands([...])                    // Register command classes
    ->withPsr4Mapping($namespace, $dir)      // Add PSR-4 mapping
    ->withPriorityPsr4Mapping($ns, $dir)     // Add priority PSR-4 mapping
    ->withComposerAutoload($path)            // Load from composer.json
    ->withStrictTypes(bool)                  // Enable/disable strict types
```

## 📚 Examples

### Example 1: Simple DTO Generator

```php
#[AsCommand(name: 'generate:dto', description: 'Generate a DTO class')]
final class DtoGeneratorCommand implements CommandInterface
{
    public function handle(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        $this->generationBuilder->add(
            $this->createClassBuilder("App\\DTOs\\{$name}DTO")
                ->configure(function ($class) {
                    $class->setFinal()
                          ->setReadOnly()
                          ->addMethod('__construct')
                          ->setPublic();
                    return $class;
                })
        );

        return $this->generationBuilder->build()->generate();
    }
}
```

### Example 2: API Resource Generator

```php
#[AsCommand(name: 'generate:resource', description: 'Generate API resource')]
final class ResourceGeneratorCommand implements CommandInterface
{
    private function defineGeneration(GenerationBuilder $builder, InputInterface $input, SymfonyStyle $io): void
    {
        $name = $input->getArgument('name');

        // Generate Resource
        $builder->add($this->createResource($name));

        // Generate Collection
        $builder->add($this->createCollection($name));

        // Generate Test
        $builder->add($this->createResourceTest($name));
    }

    private function createResource(string $name): Builder
    {
        return $this->createClassBuilder("App\\Http\\Resources\\{$name}Resource")
            ->configure(function ($class) {
                $class->setExtends('Illuminate\\Http\\Resources\\Json\\JsonResource');

                $class->addMethod('toArray')
                      ->setReturnType('array')
                      ->addParameter('request')
                      ->setBody('return parent::toArray($request);');

                return $class;
            });
    }
}
```

### Example 3: Full CRUD Generator

```php
#[AsCommand(name: 'generate:crud', description: 'Generate complete CRUD')]
final class CrudGeneratorCommand implements CommandInterface
{
    private function defineGeneration(GenerationBuilder $builder, InputInterface $input, SymfonyStyle $io): void
    {
        $name = $input->getArgument('name');

        // Model
        $builder->add($this->createModel($name));

        // Controller
        $builder->add($this->createController($name));

        // Requests
        $builder->add($this->createStoreRequest($name));
        $builder->add($this->createUpdateRequest($name));

        // Resource
        $builder->add($this->createResource($name));

        // Tests
        $builder->add($this->createControllerTest($name));

        // Migration
        $builder->add($this->createMigration($name));
    }
}
```

## 🔍 Advanced Usage

### Custom Builder Patterns

```php
// Create your own builder helpers
trait BuilderHelpers
{
    protected function createLaravelModel(string $name): Builder
    {
        return $this->createClassBuilder("App\\Models\\{$name}")
            ->addUse('Illuminate\\Database\\Eloquent\\Model')
            ->configure(function ($class) {
                $class->setExtends('Model')->setFinal();
                return $class;
            });
    }

    protected function createLaravelController(string $name): Builder
    {
        return $this->createClassBuilder("App\\Http\\Controllers\\{$name}Controller")
            ->addUse('Illuminate\\Http\\Request')
            ->addUse('App\\Http\\Controllers\\Controller')
            ->configure(function ($class) {
                $class->setExtends('Controller');
                return $class;
            });
    }
}
```

### Dynamic Configuration

```php
// Environment-specific configuration
$env = $_ENV['APP_ENV'] ?? 'development';

$config = PhpGenConfig::configure()
    ->withCommands([LaravelCqrsQueryCommand::class]);

if ($env === 'development') {
    $config->withPsr4Mapping('Dev\\', 'dev-tools');
}

return $config->withComposerAutoload();
```

### Conditional Generation

```php
private function defineGeneration(GenerationBuilder $builder, InputInterface $input, SymfonyStyle $io): void
{
    $name = $input->getArgument('name');

    // Always generate model
    $builder->add($this->createModel($name));

    // Conditionally generate API resources
    if ($input->getOption('with-api')) {
        $builder->add($this->createResource($name));
        $builder->add($this->createController($name));
    }

    // Conditionally generate tests
    if (!$input->getOption('no-tests')) {
        $builder->add($this->createTest($name));
    }
}
```

## 🐛 Troubleshooting

### Common Issues

**1. "No PSR-4 mappings configured" Error**
```php
// Ensure you have at least one mapping
return PhpGenConfig::configure()
    ->withPsr4Mapping('App\\', 'app')  // Add this
    ->withCommands([...]);
```

**2. "Duplicate directory" Error**
```php
// Use priority mappings to resolve conflicts
return PhpGenConfig::configure()
    ->withComposerAutoload()
    ->withPriorityPsr4Mapping('Tests\\', 'tests')  // Override composer mapping
    ->withCommands([...]);
```

**3. Command Not Found**
```php
// Make sure your command is registered
return PhpGenConfig::configure()
    ->withCommands([
        YourCommand::class,  // Add your command class
    ]);
```

**4. Files Not Generated in Expected Location**
```bash
# Check your PSR-4 mappings
vendor/bin/php-gen your:command --dry-run -v
```

### Debug Mode

Use verbose dry-run to debug generation:

```bash
vendor/bin/php-gen your:command --dry-run -v
```

This shows:
- Configuration settings
- PSR-4 mappings
- Generated file paths
- Complete file contents

### Validation

The library automatically validates:
- Command class existence
- PSR-4 mapping conflicts
- Required configuration

## 📄 License

MIT License - see [LICENSE](LICENSE) for details.
