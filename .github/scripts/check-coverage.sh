#!/bin/bash

# テストカバレッジチェックスクリプト
# 使用方法: bash .github/scripts/check-coverage.sh [閾値]

set -e

# デフォルト閾値は95%
THRESHOLD=${1:-95.0}

echo "🔍 テストカバレッジチェックを開始します..."
echo "📊 設定された閾値: ${THRESHOLD}%"

# 実際のカバレッジ値を取得
COVERAGE=$(php artisan test --coverage-text 2>/dev/null | grep "Lines:" | grep -oE '[0-9]+\.[0-9]+' | head -n1)

# カバレッジ取得に失敗した場合のフォールバック
if [[ -z "$COVERAGE" ]]; then
    echo "⚠️  カバレッジ情報の取得に失敗しました。テストを実行してください。"
    exit 1
fi

echo "📈 現在のテストカバレッジ: ${COVERAGE}%"

# 閾値チェック
if (( $(echo "$COVERAGE < $THRESHOLD" | bc -l) )); then
    echo "❌ テストカバレッジが閾値を下回っています"
    echo "   現在: ${COVERAGE}%"
    echo "   必要: ${THRESHOLD}%以上"
    echo ""
    echo "📚 カバレッジ改善領域:"
    echo "   - Console Commands: handleメソッドテスト実装"
    echo "   - API Controllers: メソッドテスト完全実装"
    echo "   - Scraping Services: 未テストメソッド実装"
    echo "   - Resources & Models: 未カバー箇所完全実装"
    echo ""
    echo "💡 カバレッジ向上のため、該当領域のテスト実装をお願いします。"
    exit 1
else
    echo "✅ テストカバレッジチェック合格: ${COVERAGE}%"
    if (( $(echo "$COVERAGE >= 98.0" | bc -l) )); then
        echo "🎉 優秀！98%以上の高いカバレッジを達成しています"
    elif (( $(echo "$COVERAGE >= 97.0" | bc -l) )); then
        echo "🌟 素晴らしい！97%以上の高品質なカバレッジです"
    fi
fi

echo ""
echo "✨ カバレッジチェック完了"