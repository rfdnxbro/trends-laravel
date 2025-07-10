# DevCorpTrends

技術コミュニティでの企業影響力を定点観測するエンジニアリングオフィス向けWebサイト

## 概要

はてなブックマーク、Qiita、Zennのトレンドを分析し、技術コミュニティで影響力のある企業をランキング化。
エンジニアリングオフィスの採用戦略立案をデータドリブンでサポートします。

## 主要機能

- **企業影響力ランキング**: 7つの期間（1週間〜全期間）でのランキング表示
- **企業詳細分析**: 技術情報発信履歴・スコア推移・ランキング履歴
- **検索機能**: 企業・記事の横断検索
- **動的企業識別**: CompanyMatcherによる自動企業紐づけシステム
- **React ダッシュボード**: モダンなフロントエンド（React 19 + TypeScript）
- **RESTful API**: 60req/minのレート制限・5分キャッシュ

## 🚀 クイックスタート

```bash
# プロジェクトのクローン
git clone <repository-url>
cd trends-laravel

# 依存関係のインストール
composer install && npm install

# 環境設定
cp .env.example .env
php artisan key:generate
php artisan migrate

# 開発サーバー起動
php artisan serve     # Laravel API（localhost:8000）
npm run dev          # フロントエンド（localhost:5173）
```

## 技術スタック

- **バックエンド**: Laravel 12 + PHP 8.2+ + PostgreSQL
- **フロントエンド**: React 19 + TypeScript + Tailwind CSS v4
- **インフラ**: Laravel Cloud
- **品質管理**: PHPUnit（223テスト）+ Vitest（64テスト）

## 📚 ドキュメント

詳細なドキュメントは [docs/wiki](docs/wiki/) を参照：

| カテゴリ | ドキュメント | 説明 |
|---------|-------------|------|
| 🏠 概要 | [プロジェクト概要](docs/wiki/プロジェクト概要.md) | 目的・機能・アーキテクチャ |
| 💻 開発 | [開発環境](docs/wiki/開発環境.md) | セットアップ・トラブルシューティング |
| 🔄 プロセス | [開発フロー](docs/wiki/開発フロー.md) | ブランチ戦略・レビュー・品質管理 |
| 🛠 技術 | [技術スタック](docs/wiki/技術スタック.md) | 技術選択の理由・実装状況 |
| 🤖 システム | [企業マッチングシステム](docs/wiki/企業マッチングシステム.md) | CompanyMatcher・動的企業識別 |
| 🚀 CI/CD | [CI/CD](docs/wiki/CI-CD.md) | 自動化・テスト・デプロイ |

### API・機能仕様

- [API実装状況](docs/wiki/API実装状況.md) - RESTful API仕様
- [機能仕様](docs/wiki/機能仕様.md) - 詳細な機能要件
- [フロントエンド実装](docs/wiki/フロントエンド実装.md) - React実装詳細

## 開発状況

✅ **実装完了**
- データ収集システム（はてな・Qiita・Zenn）
- **動的企業識別システム（CompanyMatcher）** - データベースベースの柔軟な企業紐づけ
- 企業影響力ランキング（7期間対応）
- RESTful API（企業・検索・ランキング）
- React ダッシュボード（ダッシュボード・企業詳細）
- CI/CDパイプライン（テスト自動化・Wiki同期）

🚧 **開発中**
- 招待制ユーザー認証
- 検索機能のフロントエンド統合

## License

MIT License