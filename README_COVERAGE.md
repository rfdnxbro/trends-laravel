# 📊 テストカバレッジ管理

## 概要
このプロジェクトでは95%以上のテストカバレッジを維持することを目標としています。

## 現在のカバレッジ状況

[![テストカバレッジ](https://img.shields.io/badge/coverage-95.31%25-brightgreen)](https://rfdnxbro.github.io/trends-laravel/)

- **ライン**: 95.31% (2194/2302) ✅ **目標達成**
- **メソッド**: 79.35% (146/184)
- **クラス**: 52.94% (18/34)

## CI/CDでのカバレッジチェック

### 自動チェック機能
- **PR作成時**: 95%未満でCI失敗
- **Push時**: カバレッジレポート自動生成
- **毎日午前2時**: カバレッジレポート更新

### チェック方法
```bash
# 手動でカバレッジチェック実行
bash .github/scripts/check-coverage.sh

# 閾値を指定してチェック
bash .github/scripts/check-coverage.sh 97.0
```

### カバレッジレポート生成
```bash
# HTMLレポート生成
php artisan test --coverage-html=coverage-html

# テキストレポート生成
php artisan test --coverage-text

# XML（CI/CD用）レポート生成
php artisan test --coverage-clover=coverage.xml
```

## カバレッジ向上のためのIssue

100%に近づけるための改善点は以下のIssueで管理されています：

### 優先度: High
- [#141: APIコントローラーのメソッドテスト完全実装](https://github.com/rfdnxbro/trends-laravel/issues/141)
  - メソッドカバレッジ大幅向上（79.35% → 85%以上）

### 優先度: Medium  
- [#140: Console Commandsのhandleメソッドテスト実装](https://github.com/rfdnxbro/trends-laravel/issues/140)
  - ScrapeAll, ScrapePlatform, ScrapeScheduleのhandleメソッド
  
- [#142: スクレイピングサービスの未テストメソッド実装](https://github.com/rfdnxbro/trends-laravel/issues/142)
  - 外部API依存部分の品質保証
  
- [#144: CI/CDテストカバレッジ95%以上の継続確保](https://github.com/rfdnxbro/trends-laravel/issues/144)
  - 品質維持のためのCI強化

### 優先度: Low
- [#143: ResourceとModelの未カバー箇所完全実装](https://github.com/rfdnxbro/trends-laravel/issues/143)
  - 最終調整で97%以上を目指す

## カバレッジレポートの確認

### オンラインレポート
- **ライブレポート**: [https://rfdnxbro.github.io/trends-laravel/](https://rfdnxbro.github.io/trends-laravel/)
- **詳細HTMLレポート**: [https://rfdnxbro.github.io/trends-laravel/coverage/](https://rfdnxbro.github.io/trends-laravel/coverage/)

### ローカルレポート
```bash
# HTMLレポート生成・表示
php artisan test --coverage-html=coverage-html
open coverage-html/index.html
```

## 開発者向けガイド

### 新機能開発時
1. **テスト駆動開発**: 機能実装前にテスト作成
2. **カバレッジ確認**: 実装後に `php artisan test --coverage-text` で確認
3. **95%維持**: PRコード前にカバレッジが95%以上であることを確認

### テスト作成のベストプラクティス
- **Unit Tests**: ビジネスロジック・計算処理・バリデーション
- **Feature Tests**: API統合・HTTPエンドポイント・データベース連携
- **E2E Tests**: ユーザージャーニー・画面操作・ワークフロー

### カバレッジ低下時の対応
1. **原因特定**: `coverage-html/index.html` で未カバー箇所を確認
2. **テスト追加**: 不足しているテストケースを実装
3. **Issue作成**: 大きな改善が必要な場合はIssue化

## 関連設定ファイル

- **PHPUnit設定**: `phpunit.xml` - カバレッジ閾値80-95%
- **CI/CDワークフロー**: `.github/workflows/test.yml` - 95%チェック
- **カバレッジ公開**: `.github/workflows/coverage-report.yml` - GitHub Pages
- **チェックスクリプト**: `.github/scripts/check-coverage.sh` - 閾値チェック

## 目標とマイルストーン

- [x] **95%達成**: 2025-07-14時点で95.31%達成済み ✅
- [ ] **97%達成**: Issue#141の実装完了
- [ ] **98%達成**: Issue#140,142の実装完了  
- [ ] **99%達成**: Issue#143の実装完了
- [ ] **100%達成**: 全Issue完了・完全カバレッジ

---

🤖 Generated with [Claude Code](https://claude.ai/code)

このドキュメントは自動的に更新されます。最新情報は[GitHubリポジトリ](https://github.com/rfdnxbro/trends-laravel)をご確認ください。