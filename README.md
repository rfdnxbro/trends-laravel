# Laravel Trends

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

## 📊 プロジェクト概要

はてなブックマーク、Qiita、Zennのトレンドから技術コミュニティにおける企業影響力を分析・可視化するWebアプリケーションです。

**対象ユーザー**: エンジニアリングオフィス・採用担当者・CTO・VP of Engineering

## 🛠️ 技術スタック

- **Backend**: PHP 8.2+ / Laravel (最新LTS)
- **Frontend**: React + Tailwind CSS
- **Database**: PostgreSQL 15.x+
- **Deploy**: Laravel Cloud

## 📚 ドキュメント

詳細なドキュメントは `docs/wiki` ディレクトリを参照してください：

- [プロジェクト概要](docs/wiki/プロジェクト概要.md) - 目的と主要機能
- [技術スタック](docs/wiki/技術スタック.md) - 技術選択の理由
- [開発環境](docs/wiki/開発環境.md) - セットアップと運用
- [開発フロー](docs/wiki/開発フロー.md) - ブランチ戦略とレビュー
- [CI/CD](docs/wiki/CI-CD.md) - 自動化とデプロイ

## 🧪 開発コマンド

```bash
# テスト実行
php artisan test

# コードスタイルチェック
vendor/bin/pint

# 静的解析
vendor/bin/phpstan analyse

# フロントエンドビルド
npm run dev
```

## 🏗️ 開発状況

現在、基本的なLaravel環境とwikiドキュメントが整備されています。
CI/CDはGitHub Actionsを使用してwiki自動同期を実装済みです。

## 📄 License

This project is licensed under the MIT License.
