# Dev - 開発実行
`ultrathink`を使って実行してください

$ARGUMENTS について開発を進めます。

## 1. 事前確認
```bash
# ブランチ確認
!git branch --show-current
```

## 2. Issue管理
```bash
# Issue作成（新規の場合）
!gh issue create --title "実装タイトル" --body "実装内容の詳細"

# Issue確認（既存の場合）
!gh issue list --search "$ARGUMENTS"
```

## 3. 実装前テスト
```bash
!php artisan test
```

## 4. 開発中の品質チェック
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

## 5. 実装完了後の統合チェック
```bash
!php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && npm test && npm run build
```

## 6. コミット
```bash
!git commit -m "feat: 実装内容の説明 (#issue番号)"
```

## 注意
- 既存のコードスタイルに従う
- テストを併せて実装
- 関連ドキュメントを更新