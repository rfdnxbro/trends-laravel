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

### 自動テストパイプライン
- **テストフレームワーク**: PHPUnit、vitest
- **実行タイミング**: PR作成時、mainブランチへのプッシュ時
- **ワークフローファイル**: `.github/workflows/test.yml`
- **ワークフロー名**: `テスト`
- **実装状況**: ✅ **完全実装済み**
- **テスト数**: PHPUnit 223テスト、フロントエンド 64テスト
- **環境**: SQLite in-memory（高速・一貫性保証）

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

### ワークフローファイル

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

## 🎯 現在の品質状況
| 項目 | 状況 |
|------|------|
| PHPUnitテスト | 223テストパス |
| フロントエンドテスト | 64テストパス |
| Laravel Pint | コードスタイル完全パス |
| PHPStan | レベル4でエラー0 |
| フロントエンドビルド | 本番ビルド成功 |

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

### パフォーマンス最適化（v2.0）
- **キャッシュ戦略**: Composer・npmキャッシュでdependencies再取得を削減 ✅
- **条件付き実行**: `[skip ci]`メッセージでの不要ビルドスキップ ✅
- **日本語対応**: ワークフロー名・ステップ名の日本語化 ✅
- **並列実行**: エラーハンドリング問題により一旦無効化 ⚠️
- **現在の実行時間**: 90秒（改善前: 89秒）
- **キャッシュ効果**: 初回実行後は20-30秒短縮見込み

## 📊 パフォーマンス改善の詳細

### 実装した最適化
| 項目 | 状況 | 効果 |
|------|------|------|
| **Composerキャッシュ** | ✅ 実装済み | dependencies再取得を削減 |
| **npmキャッシュ** | ✅ 実装済み | node_modules再構築を削減 |
| **条件付き実行** | ✅ 実装済み | `[skip ci]`で不要ビルド回避 |
| **日本語化** | ✅ 実装済み | 可読性向上 |
| **並列実行** | ⚠️ 無効化 | エラーハンドリング問題により保留 |

### キャッシュ効果の詳細
- **初回実行**: フルダウンロード（90秒）
- **2回目以降**: キャッシュヒット時（推定60-70秒）
- **頻繁なPush**: 継続的な短縮効果

### 将来の改善計画
1. **並列実行の再実装**: エラーハンドリング改善後
2. **マトリックス戦略**: 複数PHP/Nodeバージョン対応時
3. **段階的実行**: 軽量チェック先行で早期フィードバック

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