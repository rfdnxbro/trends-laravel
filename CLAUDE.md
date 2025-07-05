# Claude Code 設定

## 言語設定
- ターミナルでのやり取りは日本語で行う
- コード内の変数名や関数名は英語を使用するが、説明や文書化は日本語で行う

## プロジェクト基本情報
- DevCorpTrends プロジェクト
- 目的: 技術コミュニティでの企業影響力を定点観測
- ターゲット: エンジニアリングオフィス・採用担当者
- 開発言語: PHP (Laravel)
- 本番環境: Laravel Cloud

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
- 新機能追加、バグ修正、リファクタリングなど、すべての開発作業にissueを作成
- プルリクエストのタイトルには関連するissue番号を含める（例: `feat: Company テーブルのMigration作成 #10`）
- issueには明確な完了条件とチェックリストを含める
- **プルリクエスト作成時は必ずissue番号を紐づけること**

### 開発禁止事項
- **mainブランチにmerge済みのdatabase/migrationsファイルを後から変更してはいけない**
- 既存のmigrationファイルを変更する場合は、新しいmigrationファイルを作成する

### 必ず参照すべきケース
- 技術スタックの選択理由や設定: [技術スタック.md](docs/wiki/技術スタック.md)
- 開発環境のセットアップ: [開発環境.md](docs/wiki/開発環境.md)
- ブランチ戦略やレビューフロー: [開発フロー.md](docs/wiki/開発フロー.md)
- CI/CDの現状と将来計画: [CI-CD.md](docs/wiki/CI-CD.md)

### コマンドの確認
開発コマンドは下記のファイルに記載されています：
- セットアップ: [開発環境.md](docs/wiki/開発環境.md)
- テストと品質チェック: [開発フロー.md](docs/wiki/開発フロー.md)
- CI/CDワークフロー: [CI-CD.md](docs/wiki/CI-CD.md)

## 重要な開発コマンド
- ローカル開発: `php artisan serve`
- テスト実行: `php artisan test`
- コードスタイル: `vendor/bin/pint`
- 静的解析: `vendor/bin/phpstan analyse`

## wikiドキュメント更新の指示

### 更新タイミング
以下のような変更を行った際は、対応するwikiドキュメントの更新も検討してください：

#### 技術的変更
- 新しいライブラリ・パッケージ追加 → [技術スタック.md](docs/wiki/技術スタック.md)
- 開発コマンド・環境設定変更 → [開発環境.md](docs/wiki/開発環境.md)
- CI/CDワークフロー変更 → [CI-CD.md](docs/wiki/CI-CD.md)

#### データベース変更
- migration追加・変更 → [データベース設計.md](docs/wiki/データベース設計.md)
- モデル追加・変更 → [データベース設計.md](docs/wiki/データベース設計.md)

#### 機能変更
- 新機能追加 → [機能仕様.md](docs/wiki/機能仕様.md)
- 既存機能の大幅変更 → [機能仕様.md](docs/wiki/機能仕様.md)

### 確認方法
変更後は以下を確認：
1. 実装とwikiドキュメントの内容に矛盾がないか
2. 新しい設定やコマンドがドキュメントに記載されているか
3. 古い情報が残っていないか