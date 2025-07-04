# Claude Code 設定

## 言語設定
- ターミナルでのやり取りは日本語で行う
- 出力されるドキュメント、コメント、メッセージはすべて日本語をベースとする
- コード内の変数名や関数名は英語を使用するが、説明や文書化は日本語で行う

## プロジェクト情報
- Laravel trends プロジェクト
- 開発言語: PHP (Laravel)
- 本番環境: Laravel Cloud
- データベース: PostgreSQL

## 技術スタック
- フロントエンド: React
- CSS: Tailwind CSS
- ビルドツール: Laravel Mix
- PHPバージョン: 最新LTS
- Laravelバージョン: 最新LTS

## 開発環境
- ローカル開発: `php artisan serve`
- テストフレームワーク: PHPUnit
- テストカバレッジ目標: 95%
- コードスタイル: Laravel Pint
- 静的解析: PHPStan

## 開発フロー
- 開発はブランチを切って作業する
- 必ずプルリクエストのレビューを通す
- その後GitHub上でmergeを行う

## CI/CD
- GitHub Actions を使用
- デプロイ前に自動テストを実行
- テストが通らない場合はデプロイを停止

## ドキュメント管理
- プロジェクトドキュメントは `docs/wiki` ディレクトリにMarkdown形式で作成
- コミットした仕様からclaude codeが重要な点のみピックアップして記述
- GitHub ActionsでGitHub wikiに自動反映