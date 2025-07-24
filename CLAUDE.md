# Claude Code 設定

## 言語設定
- ターミナルでのやり取りは日本語で行う
- コード内の変数名や関数名は英語を使用するが、説明や文書化は日本語で行う
    - なお、テストコードの関数名は`test_日本語`の命名規則とする

## ドキュメント構成

### 設計原則
- **DRY原則**: 重複を避け、単一責任・再利用性を重視
- **役割分担**: CLAUDE.md = 簡潔な指示、docs/wiki = 詳細仕様

### 詳細ドキュメント
`docs/wiki` を参照：[プロジェクト概要](docs/wiki/プロジェクト概要.md) | [技術スタック](docs/wiki/技術スタック.md) | [開発環境](docs/wiki/開発環境.md) | [開発フロー](docs/wiki/開発フロー.md) | [CI/CD](docs/wiki/CI-CD.md)

## Claude Code への指示

### 開発フロー

**詳細**: [開発フロー](docs/wiki/開発フロー.md)を参照

#### 基本コマンド
```bash
# ブランチ作成
git switch -c feature/機能名

# Issue作成・PR連携
gh issue create --title "タイトル" --body "詳細"
gh pr create --title "タイトル" --body "Closes #issue番号"

# PR作成前の品質チェック（必須）
php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && npm test && npm run build && npm run test:e2e
```

### 開発禁止事項
- **mainブランチにmerge済みのdatabase/migrationsファイルを後から変更してはいけない**
- 既存のmigrationファイルを変更する場合は、新しいmigrationファイルを作成する
- **テストカバレッジ閾値の低下は絶対禁止**: 90%未満への変更は品質低下であり、必ずテスト追加で90%以上を維持する

### ⚡ 実行効率化の指示
- **E2Eテスト実行時**: 結果が明確になった時点で即座に中断し、次の行動に移る
- **サーバー起動時**: 起動完了メッセージ確認後、すぐに次のタスクに進む
- **長時間コマンド**: 2分のタイムアウト待機は避け、実行完了が判明次第すぐに次の作業を開始
- **テスト結果確認**: 成功/失敗が判明した段階で評価を行い、無駄な待機時間を排除