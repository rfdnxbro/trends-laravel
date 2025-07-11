# PR - プルリクエスト作成
必ず`ultrathink`を使って実行してください

以下の手順でプルリクエストを作成します：

## 対象
以下についてプルリクエストを作成してください：
$ARGUMENTS

## PR作成前の必須チェック
### 1. ドキュメント更新の確認
変更内容に応じて関連ドキュメントを必ず更新：
- 新機能・API追加: @docs/wiki/開発フロー.md、@docs/wiki/技術スタック.md
- 環境設定・コマンド変更: @docs/wiki/開発環境.md、@CLAUDE.md
- CI/CD変更: @docs/wiki/CI-CD.md
- データベース変更: @docs/wiki/データベース設計.md
- プロジェクト構造変更: @docs/wiki/プロジェクト概要.md

### 2. 品質チェック実行
以下のコマンドを実行し、全てが正常に完了することを確認：
!php artisan test && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && npm test && npm run build && npm run test:e2e

### 3. Git操作とPR作成
1. **変更をステージング**
   !git add .

2. **コミット作成**
   !git commit -m "適切なコミットメッセージ"

3. **リモートにプッシュ**
   !git push origin ブランチ名

4. **PRを作成**
   !gh pr create --title "PR タイトル" --body "PR の説明"

## PR作成後の確認
- GitHub Actions の CI/CD パイプラインが正常に完了することを確認
- CI失敗時は必ず修正してから次の作業に進む
- カバレッジレポートも確認し、必要に応じてテストを追加

## 注意事項
- ドキュメント更新なしでのPR作成は禁止
- 全てのCIチェックがパスすることを確認
- 適切なレビュアーを設定