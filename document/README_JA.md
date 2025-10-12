**このプロジェクトは現在ドラフト段階です**

正式なリリースまでは破壊的な変更が行われる可能性があります。

# PHP Class Generator

強力で柔軟なPHPコード生成ライブラリ。
コマンドベースのアーキテクチャにより、カスタムコードジェネレータを簡単に作成できます。
ボイラープレートコード、CQRSパターン、ドメインオブジェクトなどの生成に最適です。

## ✨ 特徴

### nette/php-generatorの拡張機能

- [nette/php-generator](https://github.com/nette/php-generator) を基盤とし、 基本的なコード生成に以下の便利な機能を追加:

- **Use文の自動生成**: 使用されているクラスのインポート文を自動的に追加
- **Strict Typesの自動追加**: `declare(strict_types=1);` を自動的に挿入
- **PSR-4の自動マッピング**: composer.jsonの設定を読み込み、名前空間とファイルパスを自動的にマッピング
- **依存関係の自動解析**: クラスの依存関係を分析し、必要なuse文を自動生成
- **コマンドベースのアーキテクチャ**: 再利用可能なコード生成コマンドを簡単に作成・管理

CLIは[symfony/console](https://github.com/symfony/console)を元に作成しています。

### MCP (Model Context Protocol) 対応

Claude Code/Claude Desktopと統合し、AI支援によるコード生成が可能:

- Symfonyコマンドを自動的にMCPツールとして公開
- プロンプトからコード生成コマンドを直接実行
- プロジェクト固有のコード生成をAIアシスタントから利用可能

## 🚀 クイックスタート

### 1. 設定ファイルの作成

プロジェクトルートに`phpgen.php`を作成:

```php
<?php

use PhpGen\ClassGenerator\Config\PhpGenConfig;
use PhpGen\ClassGenerator\Console\Commands\Example\Laravel\LaravelCqrsQueryCommand;

return PhpGenConfig::configure()
    ->withCommands([
        LaravelCqrsQueryCommand::class,
    ])
    ->withComposerAutoload()  // composer.jsonからPSR-4マッピングを読み込む
    ->withStrictTypes(true);  // 生成するコードにstrict typesを追加
```

### 2. CLIを使用してコードを生成

```bash
# CQRSクエリパターンを生成
vendor/bin/php-gen query:generate User GetUserById

# 生成内容をプレビュー（ドライラン）
vendor/bin/php-gen query:generate User GetUserById --dry-run

# ファイル内容を含む詳細なプレビュー
vendor/bin/php-gen query:generate User GetUserById --dry-run -v
```

### 3. インストール確認

```bash
vendor/bin/php-gen --help
```

## 🛠 インストール & セットアップ

### インストール

**準備中**: このプロジェクトはドラフト段階です。現在Composer経由ではインストールできません。

### セットアップ手順

1. **設定ファイルの作成** (`phpgen.php`)
2. **PSR-4マッピングの設定** (composer.jsonから自動読み込み可能)
3. **コマンドの登録**

## 🎯 組み込みコマンド

以下のコマンドが利用可能です:

- **query:generate** - Laravel CQRS Query パターン
- **command:generate** - Laravel CQRS Command パターン
- **dto:create** - DTOクラス
- **class:create** - シンプルなクラス

詳細は`src/Console/Commands/`を参照してください。

### 使用例

```bash
# CQRSクエリパターンを生成
vendor/bin/php-gen query:generate User GetUserById

# DTO生成
vendor/bin/php-gen dto:create "App\\DTOs\\UserDto" --properties="id:int,name:string"

# ドライランで確認
vendor/bin/php-gen query:generate User GetUserById --dry-run -v
```

## 🔧 カスタムコマンドの作成

### 基本パターン

```php
<?php

namespace App\Commands;

use PhpGen\ClassGenerator\Blueprint\FileBlueprint;
use PhpGen\ClassGenerator\Console\Commands\Command;
use PhpGen\ClassGenerator\Core\Project;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'generate:service', description: 'サービスクラスを生成')]
final class ServiceGeneratorCommand extends Command
{
    protected function handle(InputInterface $input, OutputInterface $output): Project
    {
        $project = new Project();

        // 生成したいファイルを追加
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

### コマンドの登録

```php
// phpgen.php
return PhpGenConfig::configure()
    ->withCommands([
        \App\Commands\ServiceGeneratorCommand::class,
    ])
    ->withComposerAutoload()
    ->withStrictTypes(true);
```

詳細な実装例は`src/Console/Commands/Example/Laravel/`を参照してください。

## ⚙️ 設定

### PSR-4マッピング管理

複雑な名前空間のシナリオを優先度付きマッピングで処理:

```php
return PhpGenConfig::configure()
    ->withComposerAutoload()  // composer.jsonのマッピングを読み込む
    ->withPsr4Mapping('App\\', 'app')  // 通常のマッピング
    ->withPriorityPsr4Mapping('Tests\\', 'tests')  // composer.jsonのマッピングを上書き
    ->withPriorityPsr4Mapping('Legacy\\', 'legacy')  // レガシーコードを処理
    ->withStrictTypes(true);
```

**マッピングルール:**
- 優先度マッピングは通常のマッピングを上書き
- 同じ優先度レベル内での重複はエラー
- 異なる優先度レベル間の重複は許可（優先度が勝つ）

### 設定メソッド

```php
PhpGenConfig::configure()
    ->withCommands([...])                    // コマンドクラスを登録
    ->withPsr4Mapping($namespace, $dir)      // PSR-4マッピングを追加
    ->withPriorityPsr4Mapping($ns, $dir)     // 優先度付きPSR-4マッピングを追加
    ->withComposerAutoload($path)            // composer.jsonから読み込む
    ->withStrictTypes(bool)                  // strict typesを有効/無効化
```

## 🔌 MCPサーバー統合

PhpGenはMCP（Model Context Protocol）サーバーとして動作し、Claude CodeやClaude Desktopから直接利用できます。

### アーキテクチャ

[php-mcp/server](https://github.com/php-mcp/server)ライブラリを基盤とし、既存のSymfonyコマンドを自動的にMCPツールとして公開します。

### サーバーの起動

```bash
# MCPサーバーを起動
./bin/php-gen mcp:server

# カスタム設定ファイルを指定
./bin/php-gen mcp:server --config-path=/path/to/phpgen.php
```

### Claude Desktop設定

Claude Desktopの設定ファイル（macOS: `~/Library/Application Support/Claude/claude_desktop_config.json`）に追加:

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

### 利用可能なMCPツール

設定ファイルで設定したCommandをMCP経由でAIが利用可能です。

**Note**: すべてのツールは`phpgen.php`に登録されたSymfonyコマンドから自動的に生成されます。

### Claude Codeでの使用例

```
あなた: Userコンテキスト用のCQRSクエリを生成して

Claude: query_generateツールを使用してコードを生成します...
```

## 📐 アーキテクチャ

### コア生成フロー

```
Command → Project → FileBlueprint → Generator → CodeWriter
```

- **Command**: コマンドライン入力を処理し、生成するファイルを定義
- **Project**: 複数のFileBlueprintを収集
- **FileBlueprint**: クラス/インターフェース/列挙型の構造を定義
- **Generator**: Nette PHP Generatorを使用してコード生成
- **CodeWriter**: ファイルをディスクに書き込み

詳細は`src/`ディレクトリを参照してください。

## 🐛 トラブルシューティング

### デバッグモード

```bash
# 詳細なドライランで生成内容を確認
vendor/bin/php-gen your:command --dry-run -v
```

### よくあるエラー

**"No PSR-4 mappings configured"**
```php
// 少なくとも1つのマッピングを追加
return PhpGenConfig::configure()
    ->withPsr4Mapping('App\\', 'app')
    ->withCommands([...]);
```

**"Duplicate directory"**
```php
// 優先度マッピングで解決
return PhpGenConfig::configure()
    ->withComposerAutoload()
    ->withPriorityPsr4Mapping('Tests\\', 'tests')
    ->withCommands([...]);
```
