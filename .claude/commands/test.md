# Test - 包括的品質チェック
`ultrathink`を使って実行してください

$ARGUMENTS について包括的な品質チェックを実行します。

## 使い方
- `/test` - 全品質チェックを実行
- `/test unit` - PHPUnitテストのみ実行  
- `/test frontend` - フロントエンドテストのみ実行
- `/test e2e` - E2Eテストのみ実行
- `/test coverage` - カバレッジレポート生成

## 1. 事前確認
```bash
# 現在のブランチ確認
!git branch --show-current

# 変更状況確認
!git status
```

## 2. 包括的品質チェック（デフォルト）
```bash
# 一括実行（PR作成前の必須チェック）
!php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && npm test && npm run build && npm run test:e2e
```

## 3. 個別テスト実行

### PHPテスト（$ARGUMENTSに"unit"が含まれる場合）
```bash
# PHPUnit実行
!php artisan test

# カバレッジ付き実行（$ARGUMENTSに"coverage"も含まれる場合）
!php artisan test --coverage-html=coverage-html
```

### コードスタイル・静的解析
```bash
# コードスタイルチェック
!vendor/bin/pint --test

# 静的解析
!vendor/bin/phpstan analyse --memory-limit=1G
```

### フロントエンドテスト（$ARGUMENTSに"frontend"が含まれる場合）
```bash
# Vitestテスト
!npm test

# フロントエンドビルド
!npm run build
```

### E2Eテスト（$ARGUMENTSに"e2e"が含まれる場合）
```bash
# Playwright E2Eテスト
!npm run test:e2e

# デバッグモード（問題がある場合）
!npm run test:e2e:debug

# UIモード（インタラクティブ確認）
!npm run test:e2e:ui

# ヘッドレスモード（ブラウザ表示）
!npm run test:e2e:headed
```

## 4. カバレッジレポート（$ARGUMENTSに"coverage"が含まれる場合）

### PHPカバレッジ
```bash
# HTMLレポート生成
!php artisan test --coverage-html=coverage-html

# 簡易レポート表示
!php artisan test --coverage
```

### フロントエンドカバレッジ
```bash
# Vitestカバレッジ
!npm test -- --coverage
```

## 5. テスト結果の確認

### 成功基準
- PHPUnitテスト: 全テスト合格（90%以上カバレッジ維持）
- Laravel Pint: エラー0件
- PHPStan: エラー0件（レベル4）
- フロントエンドテスト: 全テスト合格
- ビルド: 成功
- E2Eテスト: 全シナリオ合格

### 失敗時の対応
1. エラーメッセージを確認
2. 関連ファイルを修正
3. 個別テストで再確認
4. 包括的チェックを再実行

## 6. パフォーマンス確認
```bash
# テスト実行時間の確認
!php artisan test --profile

# 遅いテストの特定と改善検討
```

## 注意事項
- **メモリ制限**: PHPStanは`--memory-limit=1G`必須
- **カバレッジ維持**: 90%以上を必ず維持
- **新機能**: E2Eテスト必須
- **CI/CD**: ローカルとCIの環境差に注意