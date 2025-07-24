# Dev - 開発実行
`ultrathink`を使って実行してください

$ARGUMENTS について開発実装を進めます。

## 使い方
- `/dev #123` - Issue #123の開発を実装
- `/dev 機能名` - 新機能の開発を実装
- `/dev fix バグ内容` - バグ修正の実装

## 1. 事前確認
```bash
# ブランチ確認
!git branch --show-current
```

## 2. Issue確認（$ARGUMENTSにIssue番号が含まれる場合）
```bash
# Issue詳細確認
!gh issue view $ISSUE_NUMBER

# 関連Issue一覧
!gh issue list --search "$ARGUMENTS"
```

## 3. 開発実装

### 品質チェック
```bash
# 実装前テスト
!php artisan test

# 開発中の品質チェック（詳細は /test コマンド参照）
!vendor/bin/pint
!vendor/bin/phpstan analyse --memory-limit=1G

# 実装完了後の統合チェック（/test コマンド推奨）
!/test
```

### コミット
```bash
!git commit -m "feat: 実装内容の説明 (#issue番号)"
```

## 4. 実装のガイドライン

### コーディング規約
- Laravel標準のコーディング規約に従う
- 既存コードのスタイルを踏襲
- DRY原則を遵守

### テスト駆動開発
- 新機能にはテストを必ず追加
- カバレッジ90%以上を維持
- E2Eテストも考慮

### ドキュメント
開発内容に応じて以下を更新：
- **技術変更**: `docs/wiki/技術スタック.md`
- **DB変更**: `docs/wiki/データベース設計.md`
- **機能追加**: `docs/wiki/機能仕様.md`
- **API変更**: `docs/api/`配下の該当ファイル

## 5. 開発完了確認

### 基本チェック
```bash
# 包括的品質チェック
!/test
```

### 変更内容の確認
```bash
# 差分確認
!git diff

# ステータス確認
!git status
```

## 注意事項
- **コマンド役割分担**: 実装専用（品質チェック=`/test`、PR作成=`/pr`）
- **Issue駆動**: 可能な限りIssueベースで開発
- **テスト併記**: 実装と同時にテストも作成
- **既存コード尊重**: スタイルと構造を維持
- **migration注意**: merge済みファイルは変更禁止