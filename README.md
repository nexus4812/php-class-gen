**This project is currently in draft stage**

Breaking changes may occur until the official release.

# PHP Class Generator

[日本語](document/README_JA.md) | [English](README.md)

A powerful and flexible PHP code generation library.
Create custom code generators easily with a command-based architecture.
Perfect for generating boilerplate code, CQRS patterns, domain objects, and more.

## Features

### nette/php-generator Extensions

- Built on [nette/php-generator](https://github.com/nette/php-generator) with additional convenient features for basic code generation:

- **Automatic Use Statement Generation**: Automatically adds import statements for used classes
- **Auto Strict Types**: Automatically inserts `declare(strict_types=1);`
- **PSR-4 Auto Mapping**: Reads composer.json configuration to automatically map namespaces to file paths
- **Automatic Dependency Resolution**: Analyzes class dependencies and auto-generates required use statements
- **Command-Based Architecture**: Easily create and manage reusable code generation commands

CLI is built on [symfony/console](https://github.com/symfony/console).

### MCP (Model Context Protocol) Support

Integrates with Claude Code/Claude Desktop for AI-assisted code generation:

- Automatically exposes Symfony commands as MCP tools
- Execute code generation commands directly from prompts
- Project-specific code generation available from AI assistants

## Quick Start

### 1. Create Configuration File

Create `phpgen.php` in your project root:

```php
<?php

use PhpGen\ClassGenerator\Config\PhpGenConfig;
use PhpGen\ClassGenerator\Console\Commands\Example\Laravel\LaravelCqrsQueryCommand;

return PhpGenConfig::configure()
    ->withCommands([
        LaravelCqrsQueryCommand::class,
    ])
    ->withComposerAutoload()  // Load PSR-4 mappings from composer.json
    ->withStrictTypes(true);  // Add strict types to generated code
```

### 2. Generate Code Using CLI

```bash
# Generate CQRS Query pattern
vendor/bin/php-gen query:generate User GetUserById

# Preview generation (dry run)
vendor/bin/php-gen query:generate User GetUserById --dry-run

# Detailed preview with file contents
vendor/bin/php-gen query:generate User GetUserById --dry-run -v
```

### 3. Verify Installation

```bash
vendor/bin/php-gen --help
```

## =� Installation & Setup

### Installation

**Coming Soon**: This project is in draft stage. It is not currently available via Composer.

### Setup Steps

1. **Create Configuration File** (`phpgen.php`)
2. **Configure PSR-4 Mapping** (can be auto-loaded from composer.json)
3. **Register Commands**

## <� Built-in Commands

The following commands are available:

- **query:generate** - Laravel CQRS Query pattern
- **command:generate** - Laravel CQRS Command pattern
- **dto:create** - DTO classes
- **class:create** - Simple classes

See `src/Console/Commands/` for details.

### Usage Examples

```bash
# Generate CQRS Query pattern
vendor/bin/php-gen query:generate User GetUserById

# Generate DTO
vendor/bin/php-gen dto:create "App\\DTOs\\UserDto" --properties="id:int,name:string"

# Verify with dry run
vendor/bin/php-gen query:generate User GetUserById --dry-run -v
```

## Creating Custom Commands

### Basic Pattern

```php
<?php

namespace App\Commands;

use PhpGen\ClassGenerator\Blueprint\FileBlueprint;
use PhpGen\ClassGenerator\Console\Commands\Command;
use PhpGen\ClassGenerator\Core\Project;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'generate:service', description: 'Generate service class')]
final class ServiceGeneratorCommand extends Command
{
    protected function handle(InputInterface $input, OutputInterface $output): Project
    {
        $project = new Project();

        // Add files to generate
        $project->add(
            FileBlueprint::createEmptyClass('App\\Services\\UserService')
                ->defineStructure(function ($class) {
                    $class->setFinal();
                    $class->addMethod('__construct')->setPublic();
                    return $class;
                })
        );

        return $project;
    }
}
```

### Registering Commands

```php
// phpgen.php
return PhpGenConfig::configure()
    ->withCommands([
        \App\Commands\ServiceGeneratorCommand::class,
    ])
    ->withComposerAutoload()
    ->withStrictTypes(true);
```

See `src/Console/Commands/Example/Laravel/` for detailed implementation examples.

## Configuration

### PSR-4 Mapping Management

Handle complex namespace scenarios with priority mappings:

```php
return PhpGenConfig::configure()
    ->withComposerAutoload()  // Load mappings from composer.json
    ->withPsr4Mapping('App\\', 'app')  // Regular mapping
    ->withPriorityPsr4Mapping('Tests\\', 'tests')  // Override composer.json mapping
    ->withPriorityPsr4Mapping('Legacy\\', 'legacy')  // Handle legacy code
    ->withStrictTypes(true);
```

**Mapping Rules:**
- Priority mappings override regular mappings
- Duplicates within the same priority level cause errors
- Duplicates between different priority levels are allowed (priority wins)

### Configuration Methods

```php
PhpGenConfig::configure()
    ->withCommands([...])                    // Register command classes
    ->withPsr4Mapping($namespace, $dir)      // Add PSR-4 mapping
    ->withPriorityPsr4Mapping($ns, $dir)     // Add priority PSR-4 mapping
    ->withComposerAutoload($path)            // Load from composer.json
    ->withStrictTypes(bool)                  // Enable/disable strict types
```

## MCP Server Integration

PhpGen works as an MCP (Model Context Protocol) server and can be used directly from Claude Code or Claude Desktop.

### Architecture

Built on the [php-mcp/server](https://github.com/php-mcp/server) library, automatically exposing existing Symfony commands as MCP tools.

### Starting the Server

```bash
# Start MCP server
./bin/php-gen mcp:server

# Specify custom configuration file
./bin/php-gen mcp:server --config-path=/path/to/phpgen.php
```

### Claude Desktop Configuration

Add to Claude Desktop configuration file (macOS: `~/Library/Application Support/Claude/claude_desktop_config.json`):

```json
{
  "mcpServers": {
    "phpgen": {
      "command": "/absolute/path/to/your/project/bin/php-gen",
      "args": ["mcp:server"]
    }
  }
}
```

### Available MCP Tools

Commands configured in your configuration file are available to AI via MCP.

**Note**: All tools are automatically generated from Symfony commands registered in `phpgen.php`.

### Usage Example with Claude Code

```
You: Generate a CQRS query for User context

Claude: I'll generate code using the query_generate tool...
```

## =� Architecture

### Core Generation Flow

```
Command � Project � FileBlueprint � Generator � CodeWriter
```

- **Command**: Processes command-line input and defines files to generate
- **Project**: Collects multiple FileBlueprints
- **FileBlueprint**: Defines class/interface/enum structure
- **Generator**: Generates code using Nette PHP Generator
- **CodeWriter**: Writes files to disk

See the `src/` directory for details.

## Troubleshooting

### Debug Mode

```bash
# Verify generation with detailed dry run
vendor/bin/php-gen your:command --dry-run -v
```

### Common Errors

**"No PSR-4 mappings configured"**
```php
// Add at least one mapping
return PhpGenConfig::configure()
    ->withPsr4Mapping('App\\', 'app')
    ->withCommands([...]);
```

**"Duplicate directory"**
```php
// Resolve with priority mapping
return PhpGenConfig::configure()
    ->withComposerAutoload()
    ->withPriorityPsr4Mapping('Tests\\', 'tests')
    ->withCommands([...]);
```
