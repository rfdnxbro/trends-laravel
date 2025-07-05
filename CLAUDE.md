# Claude Code 設定

## 言語設定
- ターミナルでのやり取りは日本語で行う
- コード内の変数名や関数名は英語を使用するが、説明や文書化は日本語で行う

## 詳細ドキュメント
詳細な情報は `docs/wiki` ディレクトリのwikiを参照：
- [プロジェクト概要](docs/wiki/プロジェクト概要.md)
- [技術スタック](docs/wiki/技術スタック.md)
- [開発環境](docs/wiki/開発環境.md)
- [開発フロー](docs/wiki/開発フロー.md)
- [CI/CD](docs/wiki/CI-CD.md)

## Claude Code への指示

### 開発フロー
- **原則として、開発はGitHubのissueを作成してからプルリクエストに紐づけて行う**
- 詳細な開発フロー: [開発フロー.md](docs/wiki/開発フロー.md)

### 開発禁止事項
- **mainブランチにmerge済みのdatabase/migrationsファイルを後から変更してはいけない**
- 既存のmigrationファイルを変更する場合は、新しいmigrationファイルを作成する

### 開発コマンド
開発コマンドは下記のファイルに記載されています：
- セットアップ: [開発環境.md](docs/wiki/開発環境.md)
- テストと品質チェック: [開発フロー.md](docs/wiki/開発フロー.md)
- CI/CDワークフロー: [CI-CD.md](docs/wiki/CI-CD.md)