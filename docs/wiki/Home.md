# DevCorpTrends

技術コミュニティでの企業影響力を定点観測するエンジニアリングオフィス向けWebサイト

## ドキュメント

| カテゴリ | ページ | 説明 |
|---------|--------|------|
| 🏠 プロジェクト | [プロジェクト概要](プロジェクト概要) | プロジェクトの目的と機能 |
| 📋 仕様 | [機能仕様](機能仕様) | 詳細な機能仕様とAPI実装状況 |
| 🛠 技術 | [技術スタック](技術スタック) | 使用技術と選択理由 |
| 🔌 API | [API実装状況](API実装状況) | RESTful API仕様と実装状況 |
| 💻 開発 | [開発環境](開発環境) | セットアップとトラブルシューティング |
| 🔄 フロー | [開発フロー](開発フロー) | ブランチ戦略とレビュープロセス |
| 🚀 CI/CD | [CI-CD](CI-CD) | 自動化とデプロイメント |

## クイックスタート

```bash
# セットアップ
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan migrate

# 開発サーバー起動
php artisan serve
```

詳細な手順は[開発環境](開発環境)を参照してください。

## API利用例

```bash
# 企業週間ランキング取得
curl http://localhost:8000/api/rankings/weekly

# 企業検索
curl "http://localhost:8000/api/search/companies?q=Google&limit=5"

# 記事検索（過去7日間、ブックマーク50以上）
curl "http://localhost:8000/api/search/articles?q=Laravel&days=7&min_bookmarks=50"
```

詳細なAPI仕様は[API実装状況](API実装状況)を参照してください。
