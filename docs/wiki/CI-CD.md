# CI/CD

## 現在の実装状況

プロジェクトでは**GitHub Actions**を使用してCI/CDパイプラインを構築中です。

## 現在実装済みの機能

### Wiki自動同期

- **対象**: `docs/wiki` ディレクトリ
- **実行タイミング**: mainブランチへのプッシュ時
- **同期先**: GitHub Wiki
- **ワークフローファイル**: `.github/workflows/sync-wiki.yml`

## 今後の実装予定

### 自動テストパイプライン

- **テストフレームワーク**: PHPUnit
- **実行タイミング**: PR作成時、mainブランチへのプッシュ時
- **テストカバレッジ**: 95%の目標達成確認

### コード品質チェック

- **Laravel Pint**: コードスタイルチェック
- **PHPStan**: 静的解析
- **セキュリティチェック**: 脆弱性スキャン

### デプロイメントパイプライン

- **デプロイ先**: Laravel Cloud
- **デプロイ方法**: 手動デプロイまたはGitベースの自動デプロイ

## ワークフロー設定例

### 現在のwiki同期ワークフロー

```yaml
# .github/workflows/sync-wiki.yml
name: Sync Wiki

on:
  push:
    branches:
      - main
    paths:
      - 'docs/wiki/**'
  workflow_dispatch:

jobs:
  sync-wiki:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout repository
        uses: actions/checkout@v4
      - name: Sync wiki
        uses: newrelic/wiki-sync-action@main
```

### 今後のテストパイプライン設定例

```yaml
# 今後実装予定のテストパイプライン
on:
  pull_request:
    branches: [ main ]
  push:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - name: Setup PHP
      - name: Install dependencies
      - name: Run tests
      - name: Code style check
      - name: Static analysis
```

## 現在の開発フロー

### 手動チェックリスト

デプロイ前に手動で実行するチェック項目：

```bash
# テスト実行
php artisan test

# コードスタイルチェック
vendor/bin/pint

# 静的解析
vendor/bin/phpstan analyse

# フロントエンドビルド
npm run production
```

## 本番環境

### Laravel Cloud

- **ホスティング**: Laravel Cloud
- **データベース**: PostgreSQL
- **デプロイ方法**: Gitベースの自動デプロイまたは手動デプロイ

## 実装ロードマップ

### フェーズ1: 基本テストパイプライン

- [ ] PR作成時の自動テスト
- [ ] コードスタイルチェック
- [ ] 静的解析

### フェーズ2: デプロイメント自動化

- [ ] 自動デプロイメント
- [ ] デプロイ後の動作確認
- [ ] ロールバック機能

### フェーズ3: 監視とアラート

- [ ] アプリケーション監視
- [ ] エラーアラート
- [ ] パフォーマンスメトリクス

## 関連ドキュメント

- [開発フロー](Development-Flow)
- [開発環境](Development-Environment)
- [技術スタック](Tech-Stack)