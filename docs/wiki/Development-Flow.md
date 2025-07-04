# 開発フロー

## ブランチ戦略

### メインブランチ
- **main** - 本番環境に対応する安定版
- **develop** - 開発版 (必要に応じて作成)

### フィーチャーブランチ
```bash
# 機能開発
feature/add-user-dashboard
feature/implement-api-endpoints

# バグ修正
fix/login-validation-error
fix/database-connection-issue

# ホットフィックス
hotfix/critical-security-patch
```

## 開発手順

### 1. 新機能開発
```bash
# 最新のmainブランチから分岐
git checkout main
git pull origin main
git checkout -b feature/your-feature-name

# 開発・テスト
# ... 開発作業 ...

# プッシュ
git add .
git commit -m "feat: add your feature description"
git push origin feature/your-feature-name
```

### 2. プルリクエスト作成
- GitHub上でプルリクエストを作成
- 適切なタイトルと説明を記載
- レビュワーを指定

### 3. コードレビュー
- 最低1人のレビュワーによる承認が必要
- 自動テストが全て通過すること
- コードスタイルチェックが通過すること

## コミットメッセージ規約

### 形式
```
<type>(<scope>): <description>

<body>

<footer>
```

### タイプ
- **feat**: 新機能
- **fix**: バグ修正
- **docs**: ドキュメント変更
- **style**: コードスタイル変更
- **refactor**: リファクタリング
- **test**: テスト追加・修正
- **chore**: その他の変更

### 例
```bash
feat(auth): add user login functionality

- Implement login form validation
- Add session management
- Create user authentication middleware

Closes #123
```

## 品質チェック

### ローカル実行
```bash
# コードスタイルチェック
vendor/bin/pint

# 静的解析
vendor/bin/phpstan analyse

# テスト実行
php artisan test
```

### CI/CDでの自動チェック
- **PHPUnit** - 自動テスト
- **PHPStan** - 静的解析
- **Pint** - コードフォーマット
- **Larastan** - Laravel固有の静的解析

## デプロイメント

### ステージング環境
- `main`ブランチへのマージ時に自動デプロイ
- 本番環境への最終確認

### 本番環境
- Laravel Cloudでの自動デプロイ
- ダウンタイムゼロデプロイ対応

## 緊急時対応

### ホットフィックス手順
```bash
# mainブランチから緊急修正ブランチを作成
git checkout main
git checkout -b hotfix/critical-issue

# 修正作業
# ... 修正 ...

# 緊急リリース
git add .
git commit -m "hotfix: fix critical security issue"
git push origin hotfix/critical-issue
```

### ロールバック手順
```bash
# 前のバージョンに戻す
git checkout main
git revert <commit-hash>
git push origin main
```