# Scripts

このディレクトリには、プロジェクトの自動化スクリプトが含まれています。

## check-wiki-with-claude.sh

commit後にClaude自身がwiki反映状況をチェック・更新するスクリプトです。

### 機能

- 最新commitの変更内容を分析
- docs/wiki内のドキュメント更新が必要かを判定
- 必要に応じてwikiドキュメントを自動更新

### 自動実行設定

Claude Code hookを使用してgit commit後に自動実行されるよう設定できます。

`.claude/settings.local.json` に以下を追加：

```json
{
  "hooks": {
    "PostToolUse": [
      {
        "matcher": "Bash\\(git commit:.*\\)",
        "hooks": [
          {
            "type": "command",
            "command": "./scripts/check-wiki-with-claude.sh"
          }
        ]
      }
    ]
  }
}
```

### 手動実行

```bash
./scripts/check-wiki-with-claude.sh
```

### 前提条件

- Claude Codeがインストールされていること
- プロジェクトルートディレクトリで実行すること
- docs/wikiディレクトリが存在すること

### デバッグ

デバッグログを有効にするには：

```bash
DEBUG=true ./scripts/check-wiki-with-claude.sh
```