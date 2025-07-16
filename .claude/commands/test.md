# Test - テスト実行

$ARGUMENTS について包括的なテストを実行します。

## 統合テスト実行
```bash
!php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && npm test && npm run build && npm run test:e2e
```

## 個別テスト
```bash
# PHPテスト
!php artisan test

# コードスタイル
!vendor/bin/pint --test

# 静的解析
!vendor/bin/phpstan analyse --memory-limit=1G

# フロントエンド
!npm test
!npm run build

# E2Eテスト
!npm run test:e2e
```

## カバレッジ確認
```bash
!php artisan test --coverage
```