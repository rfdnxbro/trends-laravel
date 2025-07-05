#!/bin/bash

# ================================================================================
# Wiki反映チェックスクリプト (check-wiki-with-claude.sh)
# ================================================================================
# 
# 【目的】
# git commit後に自動実行され、commit内容がwikiドキュメントに適切に反映されているかを
# Claude自身がチェック・更新する
#
# 【実行タイミング】
# Claude Code hookによりgit commitコマンド実行後に自動実行
# 
# 【処理概要】
# 1. 最新commitの変更内容を分析
# 2. docs/wiki内の関連ドキュメント更新が必要か判定
# 3. 必要に応じてwikiドキュメントを更新
# 4. CI/CDにより、docs/wikiの変更は自動的にGitHub Wikiに反映される
#
# 【前提条件】
# - Claude Codeが利用可能な環境
# - プロジェクトルートディレクトリで実行
# - docs/wikiディレクトリが存在
# ================================================================================

set -e  # エラー時にスクリプトを終了

# ディレクトリ設定
readonly REPO_DIR=$(pwd)
readonly LOCAL_WIKI_DIR="${REPO_DIR}/docs/wiki"
readonly SCRIPT_NAME=$(basename "$0")

# ログ出力用関数（色付き）
print_info() {
    echo -e "\033[34m[INFO]\033[0m $1" >&2
}

print_success() {
    echo -e "\033[32m[SUCCESS]\033[0m $1" >&2
}

print_error() {
    echo -e "\033[31m[ERROR]\033[0m $1" >&2
}

print_debug() {
    if [ "${DEBUG:-}" = "true" ]; then
        echo -e "\033[90m[DEBUG]\033[0m $1" >&2
    fi
}

# ================================================================================
# 事前チェック
# ================================================================================

print_info "=== $SCRIPT_NAME: Wiki反映チェック開始 ==="
print_debug "実行ディレクトリ: $REPO_DIR"
print_debug "Wikiディレクトリ: $LOCAL_WIKI_DIR"

# Claude Codeコマンドの存在確認
if ! command -v claude >/dev/null 2>&1; then
    print_error "Claude Codeコマンドが見つかりません"
    print_error "Claude Codeをインストールして再実行してください"
    exit 1
fi

# ローカルwikiディレクトリの存在確認
if [ ! -d "$LOCAL_WIKI_DIR" ]; then
    print_error "Wikiディレクトリが見つかりません: $LOCAL_WIKI_DIR"
    print_error "docs/wikiディレクトリを作成してから再実行してください"
    exit 1
fi

# Gitリポジトリかどうかの確認
if [ ! -d "$REPO_DIR/.git" ]; then
    print_error "Gitリポジトリではありません: $REPO_DIR"
    exit 1
fi

print_success "事前チェック完了"

# ================================================================================
# メイン処理: Claudeによるwiki反映チェック・更新
# ================================================================================

print_info "Claude実行: commit内容のwiki反映状況をチェック中..."

# Claude実行（wiki反映チェック・更新）
# インタラクティブモードでClaude実行（hookから呼ばれるため適切に動作）
claude "
【タスク】commit内容のwiki反映チェック・更新

プロジェクトで最新のcommitが行われました。commit内容がdocs/wikiドキュメントに適切に反映されているかをチェックし、必要に応じて更新してください。

【ステップ1: commit内容の分析】
- 'git log -1 --stat' で最新commitの変更ファイルを確認
- 'git show HEAD --name-only' で変更されたファイル一覧を取得
- 主要な変更内容（機能追加・修正・設定変更等）を把握

【ステップ2: wiki更新要否の判定】
以下の変更タイプごとに、対応するwikiドキュメントの更新が必要かを判定:

🔧 **技術的変更**
- 新しいライブラリ・パッケージ追加 → 技術スタック.md
- 開発コマンド・環境設定変更 → 開発環境.md
- CI/CDワークフロー変更 → CI-CD.md

📊 **データベース変更**
- migration追加・変更 → データベース設計.md
- モデル追加・変更 → データベース設計.md

⚙️ **機能変更**
- 新機能追加 → 機能仕様.md
- 既存機能の大幅変更 → 機能仕様.md
- API仕様変更 → 関連ドキュメント

【ステップ3: 実行アクション】
- 更新不要な場合: 「commit内容にwiki更新は不要です」と表示して終了
- 更新必要な場合: 該当するwikiファイルを適切に更新

【報告要求】
処理完了後、以下を簡潔に報告:
1. チェックしたcommit内容の概要
2. wiki更新の要否判定結果
3. 実行したアクション（更新した場合は具体的な内容）

処理を開始してください。
"

# Claude実行結果の取得
CLAUDE_EXIT_CODE=$?

# ================================================================================
# 結果処理
# ================================================================================

if [ $CLAUDE_EXIT_CODE -eq 0 ]; then
    print_success "Wiki反映チェックが正常に完了しました"
    print_info "docs/wikiの変更は、次回mainブランチpush時にCI/CDによりGitHub Wikiに自動反映されます"
else
    print_error "Wiki反映チェック中にエラーが発生しました (終了コード: $CLAUDE_EXIT_CODE)"
    print_error "詳細はClaude Codeの出力を確認してください"
    exit 1
fi

print_info "=== $SCRIPT_NAME: Wiki反映チェック完了 ==="