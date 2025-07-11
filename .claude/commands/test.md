# Test - テスト実行

以下の手順で包括的なテストを実行します：

## テスト対象
以下について詳細にテストしてください：
$ARGUMENTS

## 実行するテスト
1. **PHPUnit テスト**
   - !php artisan test
   - 単体テスト・機能テストを実行
   - テストカバレッジを確認

2. **コードスタイルチェック**
   - !vendor/bin/pint --test
   - コードスタイルが規約に準拠しているかチェック

3. **静的解析**
   - !vendor/bin/phpstan analyse --memory-limit=1G
   - 型エラーや潜在的なバグを検出

4. **フロントエンドテスト**
   - !npm test
   - コンポーネントの単体テストを実行

5. **フロントエンドビルド**
   - !npm run build
   - 本番環境向けのビルドが正常に完了するかテスト

6. **E2Eテスト**
   - !npm run test:e2e
   - ブラウザでの実際の動作をテスト

## テスト結果の確認
- 全てのテストが正常に完了することを確認
- 失敗したテストがある場合は原因を特定し修正
- カバレッジレポートで未テスト部分を確認

## 品質チェック統合実行
全てのテストをまとめて実行：
!php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && npm test && npm run build && npm run test:e2e