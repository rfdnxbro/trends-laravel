# CI/CD

## 現在の実装状況

プロジェクトでは**GitHub Actions**を使用してCI/CDパイプラインを構築中です。

## ✅ 現在実装済みの機能

### Wiki自動同期
- **対象**: `docs/wiki` ディレクトリ
- **実行タイミング**: mainブランチへのプッシュ時
- **同期先**: GitHub Wiki
- **ワークフローファイル**: `.github/workflows/sync-wiki.yml`
- **実装状況**: ✅ 完全実装済み

## 🚧 今後の実装予定

### 自動テストパイプライン

- **テストフレームワーク**: PHPUnit
- **実行タイミング**: PR作成時、mainブランチへのプッシュ時
- **テストカバレッジ**: 95%の目標達成確認
- **スクレイピングテスト**: スクレイピングJobの実行テスト

### コード品質チェック

- **Laravel Pint**: コードスタイルチェック
- **PHPStan**: 静的解析
- **セキュリティチェック**: 脆弱性スキャン

### デプロイメントパイプライン

- **デプロイ先**: Laravel Cloud
- **デプロイ方法**: 手動デプロイまたはGitベースの自動デプロイ

## ワークフロー設定例

### 現在のwiki同期ワークフロー

**ファイルパス:** `.github/workflows/sync-wiki.yml`

- **実行タイミング**: mainブランチへの`docs/wiki/**`変更時
- **機能**: docs/wikiディレクトリをGitHub Wikiに自動同期

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
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_PASSWORD: password
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
      redis:
        image: redis:6
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
    steps:
      - name: Setup PHP
      - name: Install dependencies
      - name: Run tests
      - name: Code style check
      - name: Static analysis
      - name: Test scraping jobs
        run: |
          php artisan queue:work --once --queue=scraping
          php artisan test --filter=ScrapingJobTest
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

# スクレイピング機能チェック
php artisan queue:work --once --queue=scraping
php artisan horizon:status
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
- [ ] スクレイピングJobのテスト

### フェーズ2: デプロイメント自動化

- [ ] 自動デプロイメント
- [ ] デプロイ後の動作確認
- [ ] ロールバック機能
- [ ] スクレイピングスケジューラの起動確認

### フェーズ3: 監視とアラート

- [ ] アプリケーション監視
- [ ] エラーアラート
- [ ] パフォーマンスメトリクス
- [ ] スクレイピング失敗時のアラート
- [ ] Horizonキューの監視

## 関連ドキュメント

- [開発フロー](開発フロー)
- [開発環境](開発環境)
- [技術スタック](技術スタック)