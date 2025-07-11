# Dev - 開発実行

以下の手順で開発を実行します：

## 前提条件
- CLAUDE.mdの開発フローに従って実行
- 適切なブランチで作業していることを確認

## 実装内容
以下について実装してください：
$ARGUMENTS

## 開発フロー
1. **現在のブランチを確認**
   !git branch --show-current

2. **Issue管理**
   - 既存のIssueがある場合：Issue番号を確認
   - Issueがない場合：新規作成
     !gh issue create --title "実装タイトル" --body "実装内容の詳細"
   - Issue番号を記録（コミットメッセージで使用）

3. **実装前にテストを実行して現在の状態を確認**
   !php artisan test

4. **実装を進める**
   - 必要に応じてIssueを更新：
     !gh issue edit [issue番号] --add-label "in-progress"
     !gh issue comment [issue番号] --body "実装状況の更新"

5. **実装中に定期的に以下を実行**
   - !vendor/bin/pint（コードスタイル自動修正）
   - !vendor/bin/phpstan analyse --memory-limit=1G（静的解析）
   - !php artisan test（テスト実行）
   - !npm test（フロントエンドテスト）
   - !npm run build（フロントエンドビルド）

6. **実装完了後、品質チェックを実行**
   !php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && npm test && npm run build

7. **コミット作成**
   - Issue番号を含むコミットメッセージ：
     !git commit -m "feat: 実装内容の説明 (#issue番号)"
   - 複数コミットの場合も各コミットにIssue番号を含む

## 注意事項
- 既存のコードスタイル・命名規則に従う
- 適切なテストを併せて実装
- セキュリティ・パフォーマンスを考慮
- 関連ドキュメントの更新も忘れずに