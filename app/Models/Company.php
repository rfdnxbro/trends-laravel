<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property float|null $match_score
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'description',
        'logo_url',
        'website_url',
        'is_active',
        'url_patterns',
        'domain_patterns',
        'keywords',
        'zenn_organizations',
        'qiita_username',
        'zenn_username',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'url_patterns' => 'array',
        'domain_patterns' => 'array',
        'keywords' => 'array',
        'zenn_organizations' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 企業のランキング情報
     */
    public function rankings(): HasMany
    {
        return $this->hasMany(CompanyRanking::class);
    }

    /**
     * 企業の影響力スコア
     */
    public function influenceScores(): HasMany
    {
        return $this->hasMany(CompanyInfluenceScore::class);
    }

    /**
     * 企業の記事
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * API一覧用の検索・フィルタ・ソート機能付きクエリ
     *
     * @param  array  $filters  フィルタ条件（search, domain, is_active）
     * @param  string  $sortBy  ソートカラム
     * @param  string  $sortOrder  ソート順序
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getForApiList(array $filters = [], string $sortBy = 'name', string $sortOrder = 'asc')
    {
        $query = self::query();

        // 検索条件
        if (! empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        if (! empty($filters['domain'])) {
            $query->where('domain', 'like', "%{$filters['domain']}%");
        }

        // アクティブ状態フィルタ
        if (isset($filters['is_active']) && $filters['is_active'] !== null) {
            $query->where('is_active', $filters['is_active']);
        }

        // ソート
        $query->orderBy($sortBy, $sortOrder);

        return $query;
    }

    /**
     * API詳細用のクエリ（関連データ込み）
     *
     * @param  int  $companyId  企業ID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getForApiDetail(int $companyId)
    {
        return self::with(['rankings', 'articles' => function ($query) {
            $query->recent(config('constants.api.default_article_days'))
                ->orderBy('published_at', 'desc')
                ->limit(config('constants.api.default_article_limit'));
        }])->where('id', $companyId);
    }

    /**
     * 企業記事一覧用のクエリ
     *
     * @param  int  $companyId  企業ID
     * @param  int  $days  取得日数
     * @param  int  $minEngagement  最小エンゲージメント数
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|null
     */
    public static function getArticlesForApi(int $companyId, int $days, int $minEngagement)
    {
        $company = self::find($companyId);
        if (! $company) {
            return null;
        }

        return $company->articles()
            ->with('platform')
            ->where('published_at', '>=', now()->subDays($days))
            ->where('engagement_count', '>=', $minEngagement)
            ->orderBy('published_at', 'desc');
    }

    /**
     * 検索用クエリ（関連度スコア計算付き）
     *
     * @param  string  $query  検索クエリ
     * @param  int  $limit  取得件数
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function searchForApi(string $query, int $limit)
    {
        return self::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('domain', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->with(['rankings' => function ($q) {
                $q->latest('calculated_at')->limit(1);
            }])
            ->limit($limit);
    }
}
