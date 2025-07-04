# Claude Code 設定

## 言語設定
- ターミナルでのやり取りは日本語で行う
- コード内の変数名や関数名は英語を使用するが、説明や文書化は日本語で行う

## プロジェクト基本情報
- Laravel trends プロジェクト
- 目的: 技術コミュニティでの企業影響力を定点観測
- ターゲット: エンジニアリングオフィス・採用担当者
- 開発言語: PHP (Laravel)
- 本番環境: Laravel Cloud

## 詳細ドキュメント
詳細な情報は `docs/wiki` ディレクトリのwikiを参照：
- [プロジェクト概要](docs/wiki/Project-Overview.md)
- [技術スタック](docs/wiki/Tech-Stack.md)
- [開発環境](docs/wiki/Development-Environment.md)
- [開発フロー](docs/wiki/Development-Flow.md)
- [CI/CD](docs/wiki/CI-CD.md)

## Claude Code への指示

### 必ず参照すべきケース
- 技術スタックの選択理由や設定: [Tech-Stack.md](docs/wiki/Tech-Stack.md)
- 開発環境のセットアップ: [Development-Environment.md](docs/wiki/Development-Environment.md)
- ブランチ戦略やレビューフロー: [Development-Flow.md](docs/wiki/Development-Flow.md)
- CI/CDの現状と将来計画: [CI-CD.md](docs/wiki/CI-CD.md)

### コマンドの確認
開発コマンドは下記のファイルに記載されています：
- セットアップ: [Development-Environment.md](docs/wiki/Development-Environment.md)
- テストと品質チェック: [Development-Flow.md](docs/wiki/Development-Flow.md)
- CI/CDワークフロー: [CI-CD.md](docs/wiki/CI-CD.md)

## 重要な開発コマンド
- ローカル開発: `php artisan serve`
- テスト実行: `php artisan test`
- コードスタイル: `vendor/bin/pint`
- 静的解析: `vendor/bin/phpstan analyse`