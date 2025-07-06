# 企業詳細API エンドポイント

## 概要

企業の詳細情報、記事一覧、影響力スコア履歴、ランキング情報を提供するAPIエンドポイントです。

## 基本情報

- **ベースURL**: `/api/companies`
- **認証**: 不要
- **レート制限**: 60リクエスト/分
- **キャッシュ**: 5分間（CacheTime::DEFAULT定数で管理）

## エンドポイント

### 1. 企業詳細情報取得

```
GET /api/companies/{company_id}
```

**パラメータ**
- `company_id` (required): 企業ID

**レスポンス**
```json
{
  "data": {
    "id": 1,
    "name": "Company A",
    "domain": "company-a.com",
    "description": "企業の説明文",
    "logo_url": "https://example.com/logo.png",
    "website_url": "https://company-a.com",
    "is_active": true,
    "current_rankings": [
      {
        "period": "1m",
        "rank_position": 5,
        "total_score": 850.2,
        "article_count": 12,
        "total_bookmarks": 3500,
        "calculated_at": "2024-01-31T23:59:59Z"
      },
      {
        "period": "1y",
        "rank_position": 8,
        "total_score": 2100.5,
        "article_count": 45,
        "total_bookmarks": 12000,
        "calculated_at": "2023-12-31T23:59:59Z"
      }
    ],
    "recent_articles": [
      {
        "id": 1,
        "title": "記事タイトル",
        "url": "https://example.com/article",
        "platform": "Qiita",
        "published_at": "2024-01-30T10:00:00Z",
        "bookmark_count": 150,
        "likes_count": 75
      }
    ],
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-31T12:00:00Z"
  }
}
```

### 2. 企業の記事一覧取得

```
GET /api/companies/{company_id}/articles
```

**パラメータ**
- `company_id` (required): 企業ID
- `page` (optional): ページ番号 (デフォルト: 1)
- `per_page` (optional): 1ページあたりの件数 (デフォルト: 20, 最大: 100)
- `days` (optional): 過去何日分の記事を取得するか (デフォルト: 30)
- `min_bookmarks` (optional): 最小ブックマーク数 (デフォルト: 0)

**レスポンス**
```json
{
  "data": [
    {
      "id": 1,
      "title": "記事タイトル",
      "url": "https://example.com/article",
      "domain": "example.com",
      "platform": "Qiita",
      "author_name": "著者名",
      "author_url": "https://example.com/author",
      "published_at": "2024-01-30T10:00:00Z",
      "bookmark_count": 150,
      "likes_count": 75,
      "company": {
        "id": 1,
        "name": "Company A",
        "domain": "company-a.com"
      },
      "platform_details": {
        "id": 1,
        "name": "Qiita",
        "url": "https://qiita.com"
      },
      "scraped_at": "2024-01-30T12:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 50,
    "last_page": 3,
    "company_id": 1,
    "filters": {
      "days": 30,
      "min_bookmarks": 0
    }
  }
}
```

### 3. 企業の影響力スコア履歴取得

```
GET /api/companies/{company_id}/scores
```

**パラメータ**
- `company_id` (required): 企業ID
- `period` (optional): 期間タイプ (デフォルト: 1d)
- `days` (optional): 過去何日分のスコアを取得するか (デフォルト: 30)

**レスポンス**
```json
{
  "data": {
    "company_id": 1,
    "scores": [
      {
        "date": "2024-01-30",
        "score": 85.5,
        "rank_position": 5,
        "calculated_at": "2024-01-30T23:59:59Z"
      },
      {
        "date": "2024-01-29",
        "score": 82.3,
        "rank_position": 6,
        "calculated_at": "2024-01-29T23:59:59Z"
      }
    ]
  },
  "meta": {
    "period": "1d",
    "days": 30,
    "total": 30
  }
}
```

### 4. 企業のランキング情報取得

```
GET /api/companies/{company_id}/rankings
```

**パラメータ**
- `company_id` (required): 企業ID
- `include_history` (optional): 履歴を含める (true/false, デフォルト: false)
- `history_days` (optional): 履歴取得日数 (デフォルト: 30)

**レスポンス**
```json
{
  "data": {
    "company_id": 1,
    "rankings": {
      "1w": {
        "rank_position": 3,
        "total_score": 125.5,
        "article_count": 5,
        "total_bookmarks": 1200,
        "period_start": "2024-01-24",
        "period_end": "2024-01-31",
        "calculated_at": "2024-01-31T23:59:59Z"
      },
      "1m": {
        "rank_position": 5,
        "total_score": 850.2,
        "article_count": 12,
        "total_bookmarks": 3500,
        "period_start": "2024-01-01",
        "period_end": "2024-01-31",
        "calculated_at": "2024-01-31T23:59:59Z"
      }
    },
    "history": [
      {
        "period": "1m",
        "current_rank": 5,
        "previous_rank": 7,
        "rank_change": 2,
        "calculated_at": "2024-01-31T23:59:59Z"
      }
    ]
  }
}
```

## エラーレスポンス

### 400 Bad Request
```json
{
  "error": "企業IDが無効です",
  "details": {
    "company_id": ["The company id field is required."]
  }
}
```

### 404 Not Found
```json
{
  "error": "企業が見つかりません"
}
```

### 429 Too Many Requests
```json
{
  "error": "Rate limit exceeded. Please try again later."
}
```

## 使用例

### 1. 企業詳細情報を取得
```bash
curl -X GET "https://api.example.com/api/companies/1"
```

### 2. 企業の記事一覧を取得（過去7日間、ブックマーク数50以上）
```bash
curl -X GET "https://api.example.com/api/companies/1/articles?days=7&min_bookmarks=50"
```

### 3. 企業の影響力スコア履歴を取得（過去60日間）
```bash
curl -X GET "https://api.example.com/api/companies/1/scores?days=60"
```

### 4. 企業のランキング情報を履歴付きで取得
```bash
curl -X GET "https://api.example.com/api/companies/1/rankings?include_history=true&history_days=60"
```

### 5. 記事一覧をページネーションで取得
```bash
curl -X GET "https://api.example.com/api/companies/1/articles?page=2&per_page=10"
```

## レスポンスデータ詳細

### 企業詳細情報
- `id`: 企業ID
- `name`: 企業名
- `domain`: 企業のドメイン
- `description`: 企業の説明文
- `logo_url`: 企業ロゴのURL
- `website_url`: 企業のウェブサイトURL
- `is_active`: 企業がアクティブかどうか
- `current_rankings`: 現在のランキング情報（複数期間）
- `recent_articles`: 最近の記事（最大5件）

### 記事情報
- `id`: 記事ID
- `title`: 記事タイトル
- `url`: 記事のURL
- `domain`: 記事のドメイン
- `platform`: プラットフォーム名
- `author_name`: 著者名
- `author_url`: 著者のプロフィールURL
- `published_at`: 公開日時
- `bookmark_count`: ブックマーク数
- `likes_count`: いいね数
- `company`: 関連企業情報
- `platform_details`: プラットフォーム詳細情報
- `scraped_at`: スクレイピング日時

### スコア情報
- `date`: 日付
- `score`: 影響力スコア
- `rank_position`: ランキング順位
- `calculated_at`: 計算日時

### ランキング情報
- `rank_position`: ランキング順位
- `total_score`: 合計スコア
- `article_count`: 記事数
- `total_bookmarks`: 合計ブックマーク数
- `period_start`: 期間開始日
- `period_end`: 期間終了日
- `calculated_at`: 計算日時

## キャッシュ設定

キャッシュ時間は`App\Constants\CacheTime`クラスで一元管理されています：

- `CacheTime::DEFAULT` - 5分間（一般的なAPI）
- `CacheTime::COMPANY_DETAIL` - 5分間（企業詳細）
- `CacheTime::STATISTICS` - 10分間（統計情報）

## 注意事項

- APIレスポンスはキャッシュされるため、最新データの反映には最大5分かかる場合があります
- レート制限に達した場合は、しばらく時間をおいて再度リクエストしてください
- 大量のデータを取得する場合は、ページネーションを使用してください
- 記事の履歴データは過去365日分のみ保持されます
- 企業詳細の`recent_articles`は最大5件まで表示されます。より多くの記事を取得する場合は記事一覧APIを使用してください