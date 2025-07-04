# 開発環境

## 必要な環境

### 必須
- **PHP 8.3+**
- **Composer**
- **Node.js 18+**
- **npm**
- **MySQL 8.0+**

### 推奨
- **Laravel Sail** (Docker環境)
- **Redis**

## セットアップ手順

### 1. リポジトリのクローン
```bash
git clone https://github.com/rfdnxbro/trends-laravel.git
cd trends-laravel
```

### 2. 依存関係のインストール
```bash
composer install
npm install
```

### 3. 環境設定
```bash
cp .env.example .env
php artisan key:generate
```

### 4. データベース設定
```bash
# .envファイルでデータベース設定を更新
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trends_laravel
DB_USERNAME=root
DB_PASSWORD=

# マイグレーション実行
php artisan migrate
```

### 5. 開発サーバー起動
```bash
php artisan serve
npm run dev
```

## Laravel Sail環境 (推奨)

### 初回セットアップ
```bash
composer require laravel/sail --dev
php artisan sail:install
```

### Sail環境での起動
```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev
```

## トラブルシューティング

### よくある問題

1. **Composerの依存関係エラー**
   ```bash
   composer install --ignore-platform-reqs
   ```

2. **Node.jsバージョンエラー**
   ```bash
   nvm use 18
   npm install
   ```

3. **データベース接続エラー**
   - `.env`ファイルのデータベース設定を確認
   - MySQL/MariaDBサービスが起動しているか確認

### パフォーマンス最適化
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```