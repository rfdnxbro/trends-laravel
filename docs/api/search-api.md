# 検索API仕様書

## 概要

企業・記事・技術キーワードを検索するAPIエンドポイント群です。PostgreSQLの全文検索機能を活用し、関連度スコアによる検索結果の最適化を実装しています。

## 共通仕様

### 認証
- 認証不要（パブリックAPI）

### レート制限
- 60リクエスト/分

### キャッシュ
- 検索結果は5分間キャッシュされます

### エラーレスポンス
```json
{
  "error": "エラーメッセージ",
  "details": {
    "field": ["詳細なエラー情報"]
  }
}
```

## エンドポイント

### 1. 企業検索

企業名・ドメイン・説明文から企業を検索します。

#### リクエスト
```
GET /api/search/companies
```

#### パラメータ
| パラメータ | 型 | 必須 | デフォルト | 説明 |
|-----------|---|------|-----------|------|
| q | string | ✓ | - | 検索クエリ（1-255文字） |
| limit | integer | - | 20 | 取得件数（最大100） |

#### レスポンス例
```json
{
  "data": {
    "companies": [
      {
        "id": 1,
        "name": "株式会社サンプル",
        "domain": "sample.com",
        "description": "サンプル企業の説明",
        "logo_url": "https://example.com/logo.png",
        "website_url": "https://sample.com",
        "is_active": true,
        "match_score": 0.95,
        "current_rankings": [
          {
            "period": "weekly",
            "rank_position": 5,
            "total_score": 1250.5,
            "article_count": 12,
            "total_bookmarks": 450,
            "calculated_at": "2023-12-01T10:00:00Z"
          }
        ],
        "created_at": "2023-01-01T00:00:00Z",
        "updated_at": "2023-12-01T00:00:00Z"
      }
    ]
  },
  "meta": {
    "total_results": 25,
    "search_time": 0.123,
    "query": "サンプル"
  }
}
```

### 2. 記事検索

記事タイトル・著者名から記事を検索します。

#### リクエスト
```
GET /api/search/articles
```

#### パラメータ
| パラメータ | 型 | 必須 | デフォルト | 説明 |
|-----------|---|------|-----------|------|
| q | string | ✓ | - | 検索クエリ（1-255文字） |
| limit | integer | - | 20 | 取得件数（最大100） |
| days | integer | - | 30 | 検索対象期間（日数） |
| min_bookmarks | integer | - | 0 | 最小ブックマーク数 |

#### レスポンス例
```json
{
  "data": {
    "articles": [
      {
        "id": 1,
        "title": "Laravel入門ガイド",
        "url": "https://qiita.com/sample/items/12345",
        "domain": "qiita.com",
        "platform": "Qiita",
        "author_name": "sample_author",
        "author_url": "https://qiita.com/sample_author",
        "published_at": "2023-11-15T10:00:00Z",
        "bookmark_count": 125,
        "likes_count": 45,
        "match_score": 0.92,
        "company": {
          "id": 1,
          "name": "株式会社サンプル",
          "domain": "sample.com"
        },
        "platform_details": {
          "id": 1,
          "name": "Qiita",
          "base_url": "https://qiita.com"
        },
        "scraped_at": "2023-11-15T12:00:00Z"
      }
    ]
  },
  "meta": {
    "total_results": 15,
    "search_time": 0.089,
    "query": "Laravel",
    "filters": {
      "days": 30,
      "min_bookmarks": 0
    }
  }
}
```

### 3. 統合検索

企業・記事を横断的に検索します。

#### リクエスト
```
GET /api/search
```

#### パラメータ
| パラメータ | 型 | 必須 | デフォルト | 説明 |
|-----------|---|------|-----------|------|
| q | string | ✓ | - | 検索クエリ（1-255文字） |
| type | string | - | all | 検索タイプ（companies/articles/all） |
| limit | integer | - | 20 | 取得件数（最大100） |
| days | integer | - | 30 | 記事検索の対象期間（日数） |
| min_bookmarks | integer | - | 0 | 記事検索の最小ブックマーク数 |

#### レスポンス例
```json
{
  "data": {
    "companies": [
      {
        "id": 1,
        "name": "株式会社サンプル",
        "domain": "sample.com",
        "description": "サンプル企業の説明",
        "match_score": 0.95,
        "current_rankings": [],
        "created_at": "2023-01-01T00:00:00Z",
        "updated_at": "2023-12-01T00:00:00Z"
      }
    ],
    "articles": [
      {
        "id": 1,
        "title": "Laravel入門ガイド",
        "url": "https://qiita.com/sample/items/12345",
        "author_name": "sample_author",
        "bookmark_count": 125,
        "match_score": 0.92,
        "company": {
          "id": 1,
          "name": "株式会社サンプル",
          "domain": "sample.com"
        },
        "published_at": "2023-11-15T10:00:00Z"
      }
    ]
  },
  "meta": {
    "total_results": 40,
    "search_time": 0.156,
    "query": "Laravel",
    "type": "all",
    "filters": {
      "days": 30,
      "min_bookmarks": 0
    }
  }
}
```

## 関連度スコア計算

### 企業検索の関連度スコア
- 企業名完全一致: 1.0
- 企業名部分一致: 0.8
- ドメイン一致: 0.6
- 説明文一致: 0.4
- 最新ランキング存在: 0.2

### 記事検索の関連度スコア
- タイトル一致: 1.0
- 著者名一致: 0.5
- ブックマーク数による加算:
  - 100以上: 0.3
  - 50以上: 0.2
  - 10以上: 0.1
- 記事の新しさによる加算:
  - 7日以内: 0.2
  - 30日以内: 0.1

## パフォーマンス最適化

### インデックス
- 企業テーブル: name, domain, description
- 記事テーブル: title, author_name, published_at, bookmark_count

### キャッシュ戦略
- 検索結果は5分間キャッシュ
- キャッシュキーは検索クエリとパラメータのハッシュ値

### 制限事項
- 検索クエリは255文字まで
- 一度に取得可能な件数は100件まで
- レート制限: 60リクエスト/分

## エラーコード

| HTTPステータス | エラーコード | 説明 |
|--------------|-------------|------|
| 400 | Bad Request | パラメータが不正 |
| 429 | Too Many Requests | レート制限に達した |
| 500 | Internal Server Error | サーバー内部エラー |

## 使用例

### 企業検索
```bash
curl -X GET "https://api.example.com/api/search/companies?q=Google&limit=10"
```

### 記事検索（フィルタ付き）
```bash
curl -X GET "https://api.example.com/api/search/articles?q=React&days=7&min_bookmarks=50"
```

### 統合検索
```bash
curl -X GET "https://api.example.com/api/search?q=Laravel&type=all&limit=20"
```

## 実装ファイル

### コントローラー
- `app/Http/Controllers/Api/SearchController.php`

### リソース
- `app/Http/Resources/CompanyResource.php`
- `app/Http/Resources/CompanyArticleResource.php`

### テスト
- `tests/Feature/SearchApiTest.php`
- `tests/Unit/SearchControllerTest.php`

### ルート
- `routes/api.php`

## 技術仕様

### 使用技術
- Laravel 10.x
- PostgreSQL（全文検索）
- Redis（キャッシュ）

### 検索アルゴリズム
- ILIKE演算子による部分一致検索
- 関連度スコアによる結果ソート
- 複数フィールドでの横断検索

### セキュリティ
- SQLインジェクション対策（Eloquent ORM使用）
- レート制限による DoS攻撃対策
- パラメータバリデーション

## 今後の拡張予定

1. **全文検索エンジンの導入**
   - Elasticsearch/OpenSearchの検討

2. **検索精度の向上**
   - 形態素解析の導入
   - 同義語辞書の活用

3. **検索分析機能**
   - 検索ログの収集
   - 人気検索キーワードの分析

4. **パフォーマンス改善**
   - 検索結果の事前計算
   - より効率的なキャッシュ戦略