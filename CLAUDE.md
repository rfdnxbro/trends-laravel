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