# Laravel Trends

技術コミュニティでの企業影響力を定点観測するエンジニアリングオフィス向けWebサイト

## ドキュメント

| カテゴリ | ページ | 説明 |
|---------|--------|------|
| 🏠 プロジェクト | [プロジェクト概要](Project-Overview) | プロジェクトの目的と機能 |
| 🛠 技術 | [技術スタック](Tech-Stack) | 使用技術と選択理由 |
| 💻 開発 | [開発環境](Development-Environment) | セットアップとトラブルシューティング |
| 🔄 フロー | [開発フロー](Development-Flow) | ブランチ戦略とレビュープロセス |
| 🚀 CI/CD | [CI/CD](CI-CD) | 自動化とデプロイメント |

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

詳細な手順は[開発環境](Development-Environment)を参照してください。