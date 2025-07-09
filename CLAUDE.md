# Claude Code 設定

## 言語設定
- ターミナルでのやり取りは日本語で行う
- コード内の変数名や関数名は英語を使用するが、説明や文書化は日本語で行う
    - なお、テストコードの関数名は`test_日本語`の命名規則とする

## ドキュメント構成

### このファイル（CLAUDE.md）の役割
- **Claude Codeが毎回実行すべき具体的なコマンドと手順**
- **開発時に必ず守るべき基本ルールと禁止事項**
- **簡潔で実行可能な指示のみを記載**

### docs/wikiディレクトリの役割
- **詳細な技術仕様と環境セットアップ手順**
- **開発者向けの包括的な情報と背景説明**
- **長期的な技術決定の根拠と詳細**

### 詳細ドキュメント
詳細な情報は `docs/wiki` ディレクトリのwikiを参照：
- [プロジェクト概要](docs/wiki/プロジェクト概要.md) - プロジェクトの全体像と目的
- [技術スタック](docs/wiki/技術スタック.md) - 使用技術の詳細と選定理由
- [開発環境](docs/wiki/開発環境.md) - セットアップ手順と環境設定
- [開発フロー](docs/wiki/開発フロー.md) - 詳細な開発手順とレビュー観点
- [CI/CD](docs/wiki/CI-CD.md) - GitHub Actions の実装詳細

## Claude Code への指示

### 開発フロー

#### 1. ブランチ作成
```bash
# feature/機能名、bugfix/バグ修正名、refactor/リファクタ名 のいずれかで命名
git switch -c feature/new-feature
```

#### 2. 開発作業中の必須実行コマンド
開発中は以下を定期的に実行してCIエラーを事前に防ぐ：
```bash
# テスト実行
php artisan test

# コードスタイルチェック（自動修正）
vendor/bin/pint

# 静的解析
vendor/bin/phpstan analyse --memory-limit=1G

# フロントエンドテスト実行
npm test

# フロントエンドビルド
npm run build
```

#### 3. PR作成前の必須チェック
**PR作成前に必ず以下をすべて実行し、エラーがないことを確認する：**
```bash
php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && npm test && npm run build
```

#### 4. PR作成後のCI確認
- **GitHub ActionsのCI/CDパイプラインが正常に完了することを必ず確認する**
- CI失敗時は必ず修正してから次の作業に進む
- カバレッジレポートも確認し、必要に応じてテストを追加する

#### 5. 基本開発原則
- **GitHubのissueを作成してからプルリクエストに紐づけて行う**
- 詳細な開発フロー: [開発フロー.md](docs/wiki/開発フロー.md)

### 開発禁止事項
- **mainブランチにmerge済みのdatabase/migrationsファイルを後から変更してはいけない**
- 既存のmigrationファイルを変更する場合は、新しいmigrationファイルを作成する

### 開発コマンド詳細
詳細な開発コマンドと環境設定は下記ドキュメントを参照：
- セットアップ: [開発環境.md](docs/wiki/開発環境.md)
- テストと品質チェック: [開発フロー.md](docs/wiki/開発フロー.md)
- CI/CDワークフロー: [CI-CD.md](docs/wiki/CI-CD.md)