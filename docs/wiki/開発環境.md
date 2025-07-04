# 開発環境

## 初期セットアップ

### 必要なソフトウェア

- **PHP**: 8.2以上（最新LTS推奨）
- **Composer**: 最新版
- **Node.js**: 18.x以上（LTS推奨）
- **npm**: Node.jsに含まれる
- **PostgreSQL**: 15.x以上

### プロジェクトセットアップ

```bash
# リポジトリのクローン
git clone <repository-url>
cd trends-laravel

# PHP依存関係のインストール
composer install

# Node.js依存関係のインストール
npm install

# 環境ファイルのコピー
cp .env.example .env

# アプリケーションキーの生成
php artisan key:generate

# データベースマイグレーション
php artisan migrate

# フロントエンドアセットのビルド
npm run dev
```

### 開発サーバー起動

```bash
# Laravel開発サーバー起動
php artisan serve

# フロントエンドアセットのホットリロード（別ターミナル）
npm run hot
```

## テスト環境

### テストフレームワーク

- **PHPUnit**: PHP用テストフレームワーク
- **テストカバレッジ目標**: 95%

### テスト実行

```bash
# 全テスト実行
php artisan test

# 特定のテスト実行
php artisan test --filter TestName
```

## コード品質管理

### コードスタイル

- **Laravel Pint**: コードスタイルチェック・自動修正

```bash
# スタイルチェック
vendor/bin/pint

# ドライラン（確認のみ）
vendor/bin/pint --test
```

### 静的解析

- **PHPStan**: 静的解析ツール

```bash
# 静的解析実行
vendor/bin/phpstan analyse
```

## フロントエンド開発

### 必要なツール

- **React**: UIフレームワーク
- **Tailwind CSS**: CSSフレームワーク
- **Laravel Mix**: ビルドツール

### 開発用ビルド

```bash
# 開発用ビルド
npm run dev

# ホットリロード
npm run hot

# 本番用ビルド
npm run production
```

## 環境設定

### データベース設定

1. **PostgreSQLのインストールと起動**
   ```bash
   # macOS (Homebrew)
   brew install postgresql
   brew services start postgresql
   
   # Ubuntu/Debian
   sudo apt-get install postgresql postgresql-contrib
   sudo systemctl start postgresql
   ```

2. **データベースとユーザーの作成**
   ```bash
   # PostgreSQLに接続
   sudo -u postgres psql
   
   # データベースとユーザー作成
   CREATE DATABASE trends;
   CREATE USER trends_user WITH PASSWORD 'your_password';
   GRANT ALL PRIVILEGES ON DATABASE trends TO trends_user;
   \q
   ```

3. **`.env`ファイルの設定**
   ```bash
   # アプリケーション基本設定
   APP_NAME="Laravel Trends"
   APP_ENV=local
   APP_KEY=base64:your_generated_key
   APP_DEBUG=true
   APP_URL=http://localhost:8000
   
   # データベース設定
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=trends
   DB_USERNAME=trends_user
   DB_PASSWORD=your_password
   
   # フロントエンド設定
   VITE_APP_NAME="${APP_NAME}"
   ```

### トラブルシューティング

#### よくある問題と解決策

1. **データベース接続エラー**
   ```bash
   # PostgreSQLが起動しているか確認
   brew services list | grep postgresql
   
   # 接続テスト
   php artisan tinker
   DB::connection()->getPdo();
   ```

2. **Composer依存関係エラー**
   ```bash
   # Composerキャッシュクリア
   composer clear-cache
   composer install --no-cache
   ```

3. **npmビルドエラー**
   ```bash
   # node_modulesの再インストール
   rm -rf node_modules package-lock.json
   npm install
   ```

## 関連ドキュメント

- [技術スタック](Tech-Stack)
- [開発フロー](Development-Flow)
- [CI/CD](CI-CD)