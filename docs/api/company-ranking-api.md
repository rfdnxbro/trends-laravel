# 企業ランキングAPI エンドポイント

## 概要

企業の技術コミュニティでの影響力ランキングデータを提供するAPIエンドポイントです。

## 基本情報

- **ベースURL**: `/api/rankings`
- **認証**: 不要
- **レート制限**: 60リクエスト/分
- **キャッシュ**: 5分間（CacheTime定数で管理、統計情報は10分間）

## 期間タイプ

| 期間 | 説明 |
|------|------|
| `1w` | 1週間 |
| `1m` | 1ヶ月 |
| `3m` | 3ヶ月 |
| `6m` | 6ヶ月 |
| `1y` | 1年 |
| `3y` | 3年 |
| `all` | 全期間 |

## エンドポイント

### 1. 期間タイプ一覧取得

```
GET /api/rankings/periods
```

**レスポンス**
```json
{
  "data": ["1w", "1m", "3m", "6m", "1y", "3y", "all"]
}
```

### 2. 期間別ランキング取得

```
GET /api/rankings/{period}
```

**パラメータ**
- `period` (required): 期間タイプ
- `page` (optional): ページ番号 (デフォルト: 1)
- `per_page` (optional): 1ページあたりの件数 (デフォルト: 20, 最大: 100)
- `sort_by` (optional): ソート項目 (デフォルト: rank_position)
- `sort_order` (optional): ソート順 (asc/desc, デフォルト: asc)

**レスポンス**
```json
{
  "data": [
    {
      "id": 1,
      "company": {
        "id": 1,
        "name": "Company A",
        "domain": "company-a.com",
        "logo_url": "https://example.com/logo.png"
      },
      "rank_position": 1,
      "total_score": 1250.5,
      "article_count": 15,
      "total_bookmarks": 5000,
      "rank_change": 2,
      "period": {
        "start": "2024-01-01",
        "end": "2024-12-31"
      },
      "calculated_at": "2024-12-31T23:59:59Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  }
}
```

### 3. 上位N件のランキング取得

```
GET /api/rankings/{period}/top/{limit}
```

**パラメータ**
- `period` (required): 期間タイプ
- `limit` (required): 取得件数 (1-100)

**レスポンス**
```json
{
  "data": [
    {
      "id": 1,
      "company": {
        "id": 1,
        "name": "Company A",
        "domain": "company-a.com",
        "logo_url": "https://example.com/logo.png"
      },
      "rank_position": 1,
      "total_score": 1250.5,
      "article_count": 15,
      "total_bookmarks": 5000,
      "rank_change": 2,
      "period": {
        "start": "2024-01-01",
        "end": "2024-12-31"
      },
      "calculated_at": "2024-12-31T23:59:59Z"
    }
  ],
  "meta": {
    "period": "1m",
    "limit": 10,
    "total": 10
  }
}
```

### 4. 特定企業のランキング取得

```
GET /api/rankings/company/{company_id}
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
      "1m": {
        "rank_position": 5,
        "total_score": 850.2,
        "article_count": 12,
        "total_bookmarks": 3500,
        "period_start": "2024-01-01",
        "period_end": "2024-01-31",
        "calculated_at": "2024-01-31T23:59:59Z"
      },
      "1y": {
        "rank_position": 8,
        "total_score": 2100.5,
        "article_count": 45,
        "total_bookmarks": 12000,
        "period_start": "2023-01-01",
        "period_end": "2023-12-31",
        "calculated_at": "2023-12-31T23:59:59Z"
      }
    },
    "history": {
      "1m": [
        {
          "current_rank": 5,
          "previous_rank": 7,
          "rank_change": 2,
          "calculated_at": "2024-01-31T23:59:59Z",
          "company_name": "Company A"
        }
      ]
    }
  }
}
```

### 5. 統計情報取得

```
GET /api/rankings/statistics
```

**レスポンス**
```json
{
  "data": {
    "1m": {
      "total_companies": 100,
      "average_score": 50.25,
      "max_score": 150.75,
      "min_score": 5.10,
      "total_articles": 1000,
      "total_bookmarks": 50000,
      "last_calculated": "2024-12-31T23:59:59Z"
    }
  }
}
```

### 6. 順位上昇企業取得

```
GET /api/rankings/{period}/risers
```

**パラメータ**
- `period` (required): 期間タイプ
- `limit` (optional): 取得件数 (デフォルト: 10, 最大: 50)

**レスポンス**
```json
{
  "data": [
    {
      "company_name": "Rising Company",
      "domain": "rising.com",
      "current_rank": 5,
      "previous_rank": 10,
      "rank_change": 5,
      "calculated_at": "2024-12-31T23:59:59Z"
    }
  ],
  "meta": {
    "period": "1m",
    "limit": 10,
    "total": 15
  }
}
```

### 7. 順位下降企業取得

```
GET /api/rankings/{period}/fallers
```

**パラメータ**
- `period` (required): 期間タイプ
- `limit` (optional): 取得件数 (デフォルト: 10, 最大: 50)

**レスポンス**
```json
{
  "data": [
    {
      "company_name": "Falling Company",
      "domain": "falling.com",
      "current_rank": 15,
      "previous_rank": 8,
      "rank_change": -7,
      "calculated_at": "2024-12-31T23:59:59Z"
    }
  ],
  "meta": {
    "period": "1m",
    "limit": 10,
    "total": 12
  }
}
```

### 8. 順位変動統計取得

```
GET /api/rankings/{period}/statistics
```

**パラメータ**
- `period` (required): 期間タイプ

**レスポンス**
```json
{
  "data": {
    "total_companies": 100,
    "rising_companies": 35,
    "falling_companies": 40,
    "unchanged_companies": 25,
    "average_change": 0.8,
    "max_rise": 12,
    "max_fall": 8,
    "calculated_at": "2024-12-31T23:59:59Z"
  }
}
```

## エラーレスポンス

### 400 Bad Request
```json
{
  "error": "Invalid period. Must be one of: 1w, 1m, 3m, 6m, 1y, 3y, all"
}
```

### 404 Not Found
```json
{
  "error": "Company not found"
}
```

### 429 Too Many Requests
```json
{
  "error": "Rate limit exceeded. Please try again later."
}
```

## 使用例

### 1. 月間ランキング上位10件を取得
```bash
curl -X GET "https://api.example.com/api/rankings/1m/top/10"
```

### 2. 特定企業のランキングを履歴付きで取得
```bash
curl -X GET "https://api.example.com/api/rankings/company/1?include_history=true&history_days=60"
```

### 3. 年間ランキングを2ページ目から取得
```bash
curl -X GET "https://api.example.com/api/rankings/1y?page=2&per_page=25"
```

### 4. スコア順で降順ソートしたランキングを取得
```bash
curl -X GET "https://api.example.com/api/rankings/1m?sort_by=total_score&sort_order=desc"
```

## キャッシュ設定

キャッシュ時間は`App\Constants\CacheTime`クラスで一元管理されています：

- `CacheTime::RANKING` - 5分間（ランキング関連API）
- `CacheTime::STATISTICS` - 10分間（統計情報API）

## 注意事項

- APIレスポンスはキャッシュされるため、最新データの反映には最大5分かかる場合があります
- レート制限に達した場合は、しばらく時間をおいて再度リクエストしてください
- 大量のデータを取得する場合は、ページネーションを使用してください
- 履歴データは過去365日分のみ保持されます