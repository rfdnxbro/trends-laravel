# CI/CD

## 現在の実装状況

プロジェクトでは**GitHub Actions**を使用してCI/CDパイプラインを完全に構築・運用しています。

## ✅ 現在実装済みの機能

### Wiki自動同期
- **対象**: `docs/wiki` ディレクトリ
- **実行タイミング**: mainブランチへのプッシュ時
- **同期先**: GitHub Wiki
- **ワークフローファイル**: `.github/workflows/sync-wiki.yml`
- **ワークフロー名**: `Wiki同期`
- **実装状況**: ✅ 完全実装済み

### 自動テストパイプライン（統合CI）
- **テストフレームワーク**: PHPUnit、vitest
- **実行タイミング**: PR作成時、mainブランチへのプッシュ時
- **ワークフローファイル**: `.github/workflows/test.yml`
- **ワークフロー名**: `テスト`
- **実装状況**: ✅ **完全実装済み**
- **テスト数**: PHPUnit 429テスト、フロントエンド 158テスト
- **環境**: SQLite in-memory（高速・一貫性保証）
- **CI統一化**: ubuntu-latest + shivammathur/setup-php@v2

### E2E専用テストパイプライン
- **テストフレームワーク**: Playwright
- **実行タイミング**: PR作成時、mainブランチへのプッシュ時
- **ワークフローファイル**: `.github/workflows/e2e.yml`
- **ワークフロー名**: `E2Eテスト`
- **実装状況**: ✅ **完全実装済み**
- **テスト数**: 7個のブラウザテストケース
- **環境**: PostgreSQL + Laravel開発サーバー
- **並列実行**: 4ワーカーで高速実行（約2分）
- **MCP統合**: Claude Code連携でテスト自動化（issue #102実装完了）

### テストカバレッジレポート
- **カバレッジ計測**: PHPUnit with Xdebug
- **実行タイミング**: PR作成・更新時
- **ワークフローファイル**: `.github/workflows/coverage-comment.yml`
- **ワークフロー名**: `カバレッジコメント`
- **実装状況**: ✅ **完全実装済み**
- **機能**: 
  - HTML・XML形式のカバレッジレポート生成
  - PR上でのカバレッジ結果自動コメント
  - カバレッジ結果に応じた視覚的フィードバック（🟢🟡🔴）
  - Artifactsによるレポートファイル保存（30日間）

### コード品質チェック
- **Laravel Pint**: コードスタイルチェック ✅ **実装済み**
- **PHPStan**: 静的解析（レベル4、エラー0） ✅ **実装済み**
- **フロントエンドビルド**: Vite本番ビルドテスト ✅ **実装済み**

## 🔧 実装されたテストパイプライン

### 並列実行アーキテクチャ

**メインCIワークフロー** と **E2E専用ワークフロー** が並列実行され、トータル約2分でCI/CDが完了します。

#### テスト責務分離
- **メインCI**: Unit/Feature Tests + 品質チェック（SQLite）
- **E2E CI**: ブラウザテスト + ユーザージャーニー検証（PostgreSQL）

### メインCIワークフロー

**ファイルパス:** `.github/workflows/test.yml`

```yaml
name: テスト

on:
  pull_request:
    branches: [ main ]
  push:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    # コミットメッセージに[skip ci]が含まれている場合はスキップ
    if: ${{ !contains(github.event.head_commit.message, '[skip ci]') }}
    
    # テスト用にSQLite in-memoryデータベースを使用
    # 外部サービスは不要
    
    steps:
      - name: コードをチェックアウト
        uses: actions/checkout@v4
      
      - name: PHPセットアップ
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, dom, fileinfo, sqlite3, pdo_sqlite
          coverage: xdebug
      
      - name: Node.jsセットアップ
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
      
      - name: Composer依存関係をキャッシュ
        uses: actions/cache@v4
        with:
          path: |
            ~/.composer/cache
            vendor
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: |
            ${{ runner.os }}-composer-
      
      - name: PHP依存関係をインストール
        run: |
          composer install --no-interaction --prefer-dist --optimize-autoloader
      
      - name: Node.js依存関係をインストール
        run: npm ci
      
      - name: 環境ファイルを作成
        run: |
          cp .env.ci .env
          php artisan key:generate
      
      - name: フロントエンドアセットをビルド
        run: npm run build
      
      - name: データベースを設定
        run: |
          php artisan config:cache
          php artisan migrate --force
      
      - name: PHPテストを実行
        run: php artisan test
      
      - name: コードスタイルチェックを実行
        run: vendor/bin/pint --test
      
      - name: 静的解析を実行
        run: vendor/bin/phpstan analyse --memory-limit=1G
      
      - name: フロントエンドテストを実行
        run: npm test
      
      - name: スクレイピングサービスをテスト
        run: |
          php artisan test --filter=Scraper
          php artisan test --filter=ScrapeCommand
```

### E2E専用ワークフロー

**ファイルパス:** `.github/workflows/e2e.yml`

```yaml
name: E2Eテスト

on:
  pull_request:
    branches: [ main ]
  push:
    branches: [ main ]

jobs:
  e2e:
    runs-on: ubuntu-latest
    
    # コミットメッセージに[skip e2e]が含まれている場合はスキップ
    if: ${{ !contains(github.event.head_commit.message, '[skip e2e]') }}
    
    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_DB: trends_laravel_e2e
          POSTGRES_USER: postgres
          POSTGRES_PASSWORD: password
        ports:
          - 5432:5432
        options: --health-cmd="pg_isready -U postgres" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
      - name: コードをチェックアウト
        uses: actions/checkout@v4
      
      - name: PHPセットアップ
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, dom, fileinfo, pgsql, pdo_pgsql
          coverage: none
      
      - name: Node.js 20をセットアップ
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
      
      - name: Composer依存関係をキャッシュ
        uses: actions/cache@v4
        with:
          path: |
            ~/.composer/cache
            vendor
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: |
            ${{ runner.os }}-composer-
      
      - name: PHP依存関係をインストール
        run: composer install --no-interaction --prefer-dist --optimize-autoloader
      
      - name: Node.js依存関係をインストール
        run: npm ci
      
      - name: Playwrightブラウザインストール
        run: npx playwright install --with-deps chromium
      
      - name: 環境ファイルを作成
        run: |
          cp .env.ci .env
          php artisan key:generate
      
      - name: フロントエンドアセットをビルド
        run: npm run build
      
      - name: データベースを設定
        run: |
          php artisan config:cache
          php artisan migrate:fresh --force
          php artisan db:seed --force
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_PORT: 5432
          DB_DATABASE: trends_laravel_e2e
          DB_USERNAME: postgres
          DB_PASSWORD: password
      
      - name: Laravel開発サーバーを起動
        run: |
          php artisan serve --host=0.0.0.0 --port=8000 &
          sleep 10
          curl -f http://localhost:8000 || (echo "Laravel server failed to start" && exit 1)
        env:
          APP_URL: http://localhost:8000
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_PORT: 5432
          DB_DATABASE: trends_laravel_e2e
          DB_USERNAME: postgres
          DB_PASSWORD: password
      
      - name: E2Eテストを実行
        run: npx playwright test --workers=4
        env:
          APP_URL: http://localhost:8000
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_PORT: 5432
          DB_DATABASE: trends_laravel_e2e
          DB_USERNAME: postgres
          DB_PASSWORD: password
      
      - name: Playwrightレポートをアップロード
        uses: actions/upload-artifact@v4
        if: failure()
        with:
          name: playwright-report
          path: playwright-report/
          retention-days: 30
```

## 🎯 現在の品質状況
| 項目 | 状況 |
|------|------|
| PHPUnitテスト | 429テストパス |
| フロントエンドテスト | 158テストパス |
| E2Eテスト | 7テストパス（Playwright） |
| Laravel Pint | コードスタイル完全パス |
| PHPStan | レベル4でエラー0 |
| フロントエンドビルド | 本番ビルド成功 |
| CI並列実行 | 約2分で完了 |

## 🚀 実装された品質保証

### 自動実行される検証項目
1. **PHPUnitテスト**: 全機能の動作確認
2. **Laravel Pint**: PSR-12準拠のコードスタイルチェック
3. **PHPStan**: 静的解析による型安全性確認
4. **フロントエンドテスト**: Reactコンポーネントの動作確認
5. **フロントエンドビルド**: 本番環境用アセットビルド
6. **スクレイピングテスト**: 既存のスクレイピング機能テスト

### 技術的特徴
- **高速テスト環境**: SQLite in-memoryで外部依存なし
- **完全な型安全性**: PHPStan レベル4で0エラー
- **最適化されたビルド順序**: Viteアセット生成 → テスト実行
- **クリーンなテストスイート**: 223の価値あるテスト
- **警告ゼロ**: PHPUnitアトリビュート記法対応
- **確実な品質保証**: エラー隠蔽なしの設計

### パフォーマンス最適化（v3.0）
- **キャッシュ戦略**: Composer・npmキャッシュでdependencies再取得を削減 ✅
- **条件付き実行**: `[skip ci]`/`[skip e2e]`メッセージでの不要ビルドスキップ ✅
- **日本語対応**: ワークフロー名・ステップ名の日本語化 ✅
- **並列実行**: メインCI + E2E CI の並列実行で高速化 ✅
- **CI統一化**: ubuntu-latest + shivammathur/setup-php@v2 採用 ✅
- **現在の実行時間**: 約2分（メインCI: 約2分、E2E CI: 約2分）
- **キャッシュ効果**: 初回実行後は20-30秒短縮見込み

## 📊 パフォーマンス改善の詳細

### 実装した最適化
| 項目 | 状況 | 効果 |
|------|------|------|
| **Composerキャッシュ** | ✅ 実装済み | dependencies再取得を削減 |
| **npmキャッシュ** | ✅ 実装済み | node_modules再構築を削減 |
| **条件付き実行** | ✅ 実装済み | `[skip ci]`/`[skip e2e]`で不要ビルド回避 |
| **日本語化** | ✅ 実装済み | 可読性向上 |
| **並列実行** | ✅ 実装済み | メインCI + E2E CI の並列実行 |
| **CI統一化** | ✅ 実装済み | ubuntu-latest + shivammathur/setup-php@v2 |
| **責務分離** | ✅ 実装済み | SQLite(Main) + PostgreSQL(E2E) |

### キャッシュ効果の詳細
- **初回実行**: フルダウンロード（90秒）
- **2回目以降**: キャッシュヒット時（推定60-70秒）
- **頻繁なPush**: 継続的な短縮効果

### 将来の改善計画
1. **マトリックス戦略**: 複数PHP/Nodeバージョン対応時
2. **段階的実行**: 軽量チェック先行で早期フィードバック
3. **自動デプロイメント**: 品質チェック完了後の自動デプロイ
4. **E2Eテスト拡張**: より多くのユーザージャーニーカバレッジ

## 現在の開発フロー

### 自動化されたチェック

PR作成時に自動実行される項目：

```bash
# 以下がCI/CDで自動実行されます（順次実行）
php artisan test             # PHPUnitテスト
vendor/bin/pint --test       # コードスタイルチェック
vendor/bin/phpstan analyse   # 静的解析
npm test                     # フロントエンドテスト
npm run build               # フロントエンドビルド

# キャッシュ戦略
# Composer・npmキャッシュによりdependencies再取得を削減

# 条件付き実行
# コミットメッセージに[skip ci]が含まれている場合はスキップ
```

### 手動チェック（必要に応じて）

```bash
# 開発環境での追加チェック
php artisan queue:work --once --queue=scraping
php artisan horizon:status
```

## 本番環境

### Laravel Cloud
- **ホスティング**: Laravel Cloud
- **データベース**: PostgreSQL
- **デプロイ方法**: Gitベースの自動デプロイまたは手動デプロイ

## ✅ 実装完了ロードマップ

### フェーズ1: 基本テストパイプライン
- ✅ PR作成時の自動テスト
- ✅ コードスタイルチェック
- ✅ 静的解析
- ✅ スクレイピング関連テスト
- ✅ フロントエンドテスト

### フェーズ2: デプロイメント自動化
- ✅ 品質保証パイプライン
- ✅ CI/CD品質向上
- [ ] 自動デプロイメント（今後検討）
- [ ] デプロイ後の動作確認
- [ ] ロールバック機能

### フェーズ3: 監視とアラート（今後の拡張）
- [ ] アプリケーション監視
- [ ] エラーアラート
- [ ] パフォーマンスメトリクス
- [ ] スクレイピング失敗時のアラート
- [ ] Horizonキューの監視

## 関連ドキュメント

- [開発フロー](開発フロー)
- [開発環境](開発環境)
- [技術スタック](技術スタック)
- [プロジェクト概要](プロジェクト概要)