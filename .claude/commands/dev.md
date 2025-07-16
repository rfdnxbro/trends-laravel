# Dev - 統合開発コマンド
`ultrathink`を使って実行してください

$ARGUMENTS について開発を進めます。

## 使い方
- `/dev #123` - Issue #123の開発を進める
- `/dev test` - テストのみ実行
- `/dev pr` - PR作成のみ実行
- `/dev #123 pr` - Issue #123の開発を進めてPRも作成

## 1. 事前確認
```bash
# ブランチ確認
!git branch --show-current
```

## 2. Issue管理（$ARGUMENTSにIssue番号が含まれる場合）
```bash
# Issue作成（新規の場合）
!gh issue create --title "実装タイトル" --body "実装内容の詳細"

# Issue確認（既存の場合）
!gh issue list --search "$ARGUMENTS"
```

## 3. 開発実装（$ARGUMENTSに"test"や"pr"のみが含まれない場合）

### 実装前テスト
```bash
!php artisan test
```

### 開発中の品質チェック
```bash
# コードスタイル自動修正
!vendor/bin/pint

# 静的解析
!vendor/bin/phpstan analyse --memory-limit=1G

# テスト実行
!php artisan test
!npm test
!npm run build
```

### 実装完了後の統合チェック
```bash
!php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && npm test && npm run build
```

### コミット
```bash
!git commit -m "feat: 実装内容の説明 (#issue番号)"
```

## 4. テスト実行（$ARGUMENTSに"test"が含まれる場合）

### 統合テスト実行
```bash
!php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && npm test && npm run build && npm run test:e2e
```

### 個別テスト
```bash
# PHPテスト
!php artisan test

# コードスタイル
!vendor/bin/pint --test

# 静的解析
!vendor/bin/phpstan analyse --memory-limit=1G

# フロントエンド
!npm test
!npm run build

# E2Eテスト
!npm run test:e2e
```

### カバレッジ確認
```bash
!php artisan test --coverage
```

## 5. PR作成（$ARGUMENTSに"pr"が含まれる場合）

### ドキュメント更新確認
変更内容に応じて必ず更新：
- **新機能/API**: `docs/wiki/開発フロー.md`、`docs/wiki/技術スタック.md`
- **環境/コマンド**: `docs/wiki/開発環境.md`、`CLAUDE.md`
- **CI/CD**: `docs/wiki/CI-CD.md`
- **DB変更**: `docs/wiki/データベース設計.md`
- **構造変更**: `docs/wiki/プロジェクト概要.md`

### 品質チェック
```bash
!php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && npm test && npm run build && npm run test:e2e
```

### PR作成
```bash
# ステージング
!git add .

# コミット
!git commit -m "適切なコミットメッセージ"

# プッシュ
!git push -u origin $(git branch --show-current)

# PR作成（Issueと紐付け）
!gh pr create --title "PRタイトル" --body "$(cat <<'EOF'
## 概要
実装内容の説明

## 変更内容
- 変更点1
- 変更点2

## テスト
- [x] テスト実行済み
- [x] 品質チェック完了

Closes #[issue番号]

🤖 Generated with [Claude Code](https://claude.ai/code)
EOF
)"
```

### PR後の確認
- GitHub Actions CI/CDの成功を確認
- カバレッジレポートを確認

## 注意事項
- 既存のコードスタイルに従う
- テストを併せて実装
- 関連ドキュメントを更新
- mainブランチにmerge済みのmigrationファイルは変更しない