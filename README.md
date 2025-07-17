# DevCorpTrends

技術コミュニティでの企業影響力を定量化し、エンジニアリングオフィスの採用戦略をデータドリブンでサポートするWebアプリケーションです。

## 概要

はてなブックマーク・Qiita・Zennから企業の技術影響力を分析し、7つの期間でランキング化。企業詳細分析・検索機能・RESTful APIを提供します。

## 🚀 クイックスタート

```bash
git clone <repository-url> && cd trends-laravel
composer install && npm install
cp .env.example .env && php artisan key:generate && php artisan migrate
php artisan serve & npm run dev  # Laravel API + フロントエンド
```

**詳細**: [開発環境](docs/wiki/開発環境.md)を参照

## 技術スタック

Laravel 12 + React 19 + TypeScript + PostgreSQL

**詳細**: [技術スタック](docs/wiki/技術スタック.md)を参照

## 📚 ドキュメント

| 開発者向け | 技術仕様 |
|-----------|---------|
| [開発環境](docs/wiki/開発環境.md) | [技術スタック](docs/wiki/技術スタック.md) |
| [開発フロー](docs/wiki/開発フロー.md) | [API仕様](docs/wiki/API実装状況.md) |
| [CI/CD](docs/wiki/CI-CD.md) | [機能仕様](docs/wiki/機能仕様.md) |

**全ドキュメント**: [docs/wiki](docs/wiki/)

## 開発状況

✅ **運用可能**: データ収集・企業識別・ランキング・API・ダッシュボード・CI/CD  
🚧 **開発中**: 認証機能・検索UI統合

**詳細**: [プロジェクト概要](docs/wiki/プロジェクト概要.md)を参照

## License

MIT License# CI並列化テスト
