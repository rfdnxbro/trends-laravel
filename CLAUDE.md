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

#### 1. ブランチ作成
```bash
# feature/機能名、bugfix/バグ修正名、refactor/リファクタ名 のいずれかで命名
git switch -c feature/new-feature
```

#### 2. Issue管理とPR連携
```bash
# Issue作成
gh issue create --title "実装タイトル" --body "実装内容の詳細"

# コミット（Issue番号を含める）
git commit -m "feat: 実装内容の説明 (#issue番号)"

# PR作成（Issueと紐付け）
gh pr create --title "PRタイトル" --body "Closes #issue番号"
```

#### 3. 品質チェック
```bash
# PR作成前に実行（必須）
php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && vendor/bin/phpmetrics --report-html=phpmetrics-report app/ && npx eslint 'resources/js/**/*.{ts,tsx}' --max-warnings 0 && npm test && npm run build && npm run test:e2e

# 個別実行コマンド
vendor/bin/phpmetrics --report-html=phpmetrics-report app/  # PHP循環的複雑度チェック
npx eslint 'resources/js/**/*.{ts,tsx}' --max-warnings 0   # TypeScript循環的複雑度チェック

# テストカバレッジ確認（重要：正しいオプション名）
php artisan test --coverage-html=coverage-html             # HTMLカバレッジレポート生成
php artisan test --coverage-text --min=90                  # テキストカバレッジ確認（90%閾値）
```

#### 4. ドキュメント更新
変更内容に応じて関連ドキュメントを更新：
- **新機能/API**: 開発フロー.md、技術スタック.md
- **環境/コマンド**: 開発環境.md、CLAUDE.md
- **CI/CD**: CI-CD.md
- **DB変更**: データベース設計.md

#### 5. 基本原則
- **Issue-PR連携**: 必ずIssueを作成し、PRに `Closes #番号` を含める
- **ドキュメント更新**: コード変更時は関連ドキュメントも同時更新
- **CI確認**: PR後のGitHub Actions成功を確認
- **PHPDoc必須**: 全てのpublic/privateメソッドに適切なPHPDocコメントを記載

### 開発禁止事項
- **mainブランチにmerge済みのdatabase/migrationsファイルを後から変更してはいけない**
- 既存のmigrationファイルを変更する場合は、新しいmigrationファイルを作成する
- **テストカバレッジ閾値の低下は絶対禁止**: 90%未満への変更は品質低下であり、必ずテスト追加で90%以上を維持する

### テスト・CI/CD
- **メモリ制限**: PHPUnit 512M、PHPStan 1G
- **CI並列実行**: 品質チェック + E2E テスト（約2分）
- **カバレッジレポート**: `php artisan test --coverage-html=coverage-html`で生成（.gitignoreで除外済み）
- **カバレッジ閾値**: **90%必須** - テスト削除時は必ず代替テストを追加してカバレッジを維持
- **カバレッジ維持原則**: 90%を最低限とし、極力カバレッジを下げないよう十分なテスト実装を心がける
- **循環的複雑度**: PHPMetrics（PHP）、ESLint（TypeScript）による必須チェック（CI統合済み）
- **重要な注意**: `--coverage-html`が正しいオプション（`--coverage-text`ではない）
- **詳細**: [開発フロー.md](docs/wiki/開発フロー.md)、[CI-CD.md](docs/wiki/CI-CD.md)を参照

#### テスト実装ガイドライン
- **基本方針**: 新機能追加時は対応するテストを必ず作成し、既存のカバレッジレベルを維持または向上させる
- **リファクタリング時**: コード変更後もテストカバレッジが低下しないよう、必要に応じてテストケースを追加
- **テスト削除時**: 冗長なテスト削除の際は、削除対象が本当に不要かを慎重に検討し、必要に応じて代替テストを実装
- **品質優先**: カバレッジ数値だけでなく、エッジケースや例外処理も含む包括的なテストを作成する

### コード品質基準

#### PHPDocコメントの要件
- **必須対象**: 全てのpublic/privateメソッド、クラス
- **記載内容**: 
  ```php
  /**
   * メソッドの目的と動作の説明
   *
   * @param string $param パラメータの説明
   * @param int $limit 制限値の説明
   * @return array 戻り値の構造説明（companies, search_time, total_results等）
   */
  ```
- **動的プロパティ**: `@var` タグで型情報を明示（例：`/** @var \App\Models\Company $company */`）
- **静的解析**: PHPStan エラー解消が必須
- **IDE支援**: 型情報により自動補完とエラー検出を向上

#### 循環的複雑度管理
- **目標**: 全メソッドの循環的複雑度を20未満に維持（必須）
- **手法**: メソッド分離、戦略パターン、共通化によるリファクタリング
- **測定**: PHPMetrics（PHP）、ESLint complexity rule（TypeScript）
- **CI統合**: 複雑度超過時はCI失敗、PRマージ不可

### ⚡ 実行効率化の指示
- **E2Eテスト実行時**: 結果が明確になった時点で即座に中断し、次の行動に移る
- **サーバー起動時**: 起動完了メッセージ確認後、すぐに次のタスクに進む
- **長時間コマンド**: 2分のタイムアウト待機は避け、実行完了が判明次第すぐに次の作業を開始
- **テスト結果確認**: 成功/失敗が判明した段階で評価を行い、無駄な待機時間を排除