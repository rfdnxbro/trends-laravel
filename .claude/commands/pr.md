# PR - プルリクエスト作成
`ultrathink`を使って実行してください

$ARGUMENTS についてプルリクエストを作成します。

## 1. ドキュメント更新確認
変更内容に応じて必ず更新：
- **新機能/API**: `docs/wiki/開発フロー.md`、`docs/wiki/技術スタック.md`
- **環境/コマンド**: `docs/wiki/開発環境.md`、`CLAUDE.md`
- **CI/CD**: `docs/wiki/CI-CD.md`
- **DB変更**: `docs/wiki/データベース設計.md`
- **構造変更**: `docs/wiki/プロジェクト概要.md`

## 2. 品質チェック
```bash
!php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && npm test && npm run build && npm run test:e2e
```

## 3. PR作成
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

## 4. PR後の確認
- GitHub Actions CI/CDの成功を確認
- カバレッジレポートを確認