**このプロジェクトは現在ドラフト段階です**
正式なリリースまでは破壊的な変更が行われる可能性があります。

# PHP Class Generator

[symfony/console](https://github.com/symfony/console) と [nette/php-generator](https://github.com/nette/php-generator) を利用したPHPコード生成ライブラリです。
Repositoryパターン、CQRSパターンなど、プロジェクト独自のアーキテクチャに沿ったテンプレートを作成できます。
特定のフレームワークに依存せず、任意のPHPプロジェクトで使用可能です。

## 特徴

### nette/php-generatorを直接使用する場合との違い

このライブラリは、nette/php-generatorの基本機能に以下の便利な機能を追加しています:

- **Use文の自動生成**: 使用されているクラスのインポート文を自動的に追加
- **Strict Typesの自動追加**: `declare(strict_types=1);` を自動的に挿入
- **PSR-4自動マッピング**: composer.jsonの設定を読み込み、名前空間とファイルパスを自動的にマッピング
- **コマンドベースのアーキテクチャ**: 再利用可能なコード生成コマンドを簡単に作成・管理


## インストール

**準備中**
このプロジェクトはドラフト段階です。  
現在 composer経由ではインストールできません。

## 🚀 クイックスタート

### 基本的な使い方

1. **設定ファイルと独自コマンドを作成**（プロジェクトルートに`phpgen.php`を配置）:

例えばCQRS実装に基づくQueryHandlerクラスとその周辺クラスの雛形のテンプレートを作成する例です。
まず[LaravelCqrsCommandCommand.php](../src/Console/Commands/Example/Laravel/LaravelCqrsCommandCommand.php)のようなコマンドクラスを作成します。

そしてプロジェクトルートに下記のような設定ファイルとCommandを登録させます。
```php
<?php

use PhpGen\ClassGenerator\Config\PhpGenConfig;
use PhpGen\ClassGenerator\Console\Commands\Example\Laravel\LaravelCqrsQueryCommand;

return PhpGenConfig::configure()
    ->withCommands([
        LaravelCqrsQueryCommand::class, // ここに独自に作成した独自コマンドを設置します
    ])
    ->withComposerAutoload()  // composer.jsonからPSR-4マッピングを読み込む
    ->withStrictTypes(true);  // 生成するコードにdeclare(strict_types=1);を挿入する
```

2. **CLIを使用してコードを生成します**:

```bash
# CQRSクエリパターンを生成
vendor/bin/php-gen query:generate GetUser User

# 生成内容をプレビュー（ドライラン）
vendor/bin/php-gen query:generate GetUser User --dry-run

# ファイル内容を含む詳細なプレビューを表示
vendor/bin/php-gen query:generate GetUser User --dry-run -v
```

下記のように生成ができます。

```sh
$ tree app tests
app
├── Contracts
│         └── Query
│             └── User
│                 ├── FindUserByIdQuery.php
│                 ├── FindUserByIdQueryHandler.php
│                 └── FindUserByIdResult.php
└── Infrastructure
    └── Query
        └── User
            └── FindUserByIdQueryHandlerImplementation.php
tests
└── Feature
    └── Infrastructure
        └── Query
            └── User
                └── FindUserByIdQueryHandlerImplementationTest.php
```
