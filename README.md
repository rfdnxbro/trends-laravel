# DevCorpTrends

技術コミュニティでの企業影響力を定点観測するエンジニアリングオフィス向けWebサイト

## 🚀 クイックスタート

```bash
# プロジェクトをクローン
git clone <repository-url>
cd trends-laravel

# セットアップ
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan migrate

# 開発サーバー起動
php artisan serve
```

## 📚 ドキュメント

詳細なドキュメントは `docs/wiki` ディレクトリを参照してください：

- [プロジェクト概要](docs/wiki/プロジェクト概要.md) - 目的と主要機能
- [技術スタック](docs/wiki/技術スタック.md) - 技術選択の理由
- [開発環境](docs/wiki/開発環境.md) - セットアップと運用
- [開発フロー](docs/wiki/開発フロー.md) - ブランチ戦略とレビュー
- [CI/CD](docs/wiki/CI-CD.md) - 自動化とデプロイ

## 🏗️ 開発状況

現在、基本的なLaravel環境とwikiドキュメントが整備されています。
CI/CDはGitHub Actionsを使用してwiki自動同期を実装済みです。

## 📄 License

This project is licensed under the MIT License.