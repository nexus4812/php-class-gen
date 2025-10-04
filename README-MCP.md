# PhpGen MCP Server

このMCPサーバーは、PhpGenのコマンド群をClaude Code用のMCPツールとして提供します。

## 機能

- **動的コマンド発見**: `phpgen.php`設定ファイルからコマンドを自動検出
- **自動スキーマ生成**: Symfony Consoleの定義からMCPスキーマを自動生成
- **完全な統合**: 既存のPhpGenワークフローとの完全な互換性
- **エラーハンドリング**: 堅牢な入力検証とエラー処理

## セットアップ

### 1. 依存関係の確認

プロジェクトが正しくセットアップされていることを確認：

```bash
composer install
```

### 2. Claude Desktop設定

`claude_desktop_config.json`ファイルを編集し、MCPサーバーを追加：

```json
{
  "mcpServers": {
    "phpgen": {
      "command": "php",
      "args": ["/absolute/path/to/your/project/mcp-phpgen-server.php"]
    }
  }
}
```

**重要**: パスは絶対パスで指定してください。

### 3. 設定ファイルの場所

MCPサーバーは以下の順序で`phpgen.php`設定ファイルを検索します：

1. `getcwd() . '/phpgen.php'` - 現在の作業ディレクトリ
2. `getcwd() . '/.phpgen.php'` - 隠し設定ファイル
3. プロジェクトルート（サーバーファイルから相対）

## 利用可能なツール

MCPサーバーは`phpgen.php`に登録されているすべてのコマンドを自動的にMCPツールとして提供します。

### 現在のツール

#### `phpgen_dto_create`
DTOクラスを生成します。

**パラメータ:**
- `fullyQualifiedName` (string, required): DTOクラス名
- `properties` (string, optional): プロパティ定義 (例: "id:int,name:string")
- `dryRun` (boolean, optional): プレビューモード（ファイルを実際に生成しない）

**例:**
```json
{
  "fullyQualifiedName": "App\\DTO\\UserDto",
  "properties": "id:int,name:string,email:string",
  "dryRun": true
}
```

#### `phpgen_query_generate`
Laravel CQRSクエリコンポーネントを生成します。

**パラメータ:**
- `context` (string, required): ドメインコンテキスト (例: "User", "Product")
- `queryName` (string, required): クエリ名 (例: "GetUser", "FindProducts")
- `noQuery` (boolean, optional): Queryクラスの生成をスキップ
- `dryRun` (boolean, optional): プレビューモード

**例:**
```json
{
  "context": "User",
  "queryName": "FindUserById",
  "dryRun": true
}
```

## テスト

MCPサーバーが正しく動作することを確認：

```bash
# サーバーをテストモードで起動
echo '{"jsonrpc":"2.0","method":"initialize","params":{},"id":1}' | php mcp-phpgen-server.php

# 利用可能なツールを確認
echo '{"jsonrpc":"2.0","method":"tools/list","params":{},"id":2}' | php mcp-phpgen-server.php
```

## トラブルシューティング

### よくある問題

1. **"PhpGen configuration file not found"**
   - `phpgen.php`ファイルがプロジェクトルートに存在することを確認
   - ファイルパスが正しいことを確認

2. **"Command class does not exist"**
   - `composer install`を実行してオートローダーを更新
   - コマンドクラスが正しく定義されていることを確認

3. **"Permission denied"**
   - `mcp-phpgen-server.php`ファイルが実行可能であることを確認
   - `chmod +x mcp-phpgen-server.php`を実行

### デバッグ

MCPサーバーのログを確認するには：

```bash
# エラー出力を確認
php mcp-phpgen-server.php 2>error.log

# 詳細なエラー情報
php -d display_errors=1 -d error_reporting=E_ALL mcp-phpgen-server.php
```

## 拡張

新しいコマンドを追加するには：

1. PhpGenコマンドクラスを作成
2. `phpgen.php`設定ファイルに追加
3. MCPサーバーを再起動

MCPサーバーは自動的に新しいコマンドを検出し、対応するMCPツールを生成します。

## アーキテクチャ

```
mcp-phpgen-server.php
├── McpServer
├── CommandDiscovery    # コマンドの自動発見
├── McpToolFactory     # MCPツール生成
├── JsonRpcHandler     # JSON-RPC処理
└── PhpGenConfig       # 既存の設定システム
```

各コンポーネントは独立しており、テストと保守が容易です。