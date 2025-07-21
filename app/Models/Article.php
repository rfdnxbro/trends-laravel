<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property float|null $match_score
 */
class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'platform_id',
        'company_id',
        'title',
        'url',
        'domain',
        'platform',
        'author_name',
        'author',
        'author_url',
        'published_at',
        'bookmark_count',
        'likes_count',
        'scraped_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'scraped_at' => 'datetime',
        'bookmark_count' => 'integer',
        'likes_count' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('published_at', '>=', now()->subDays($days));
    }

    public function scopePopular($query, $minBookmarks = 10)
    {
        return $query->where('bookmark_count', '>=', $minBookmarks);
    }

    /**
     * 記事の検索とフィルタリングを行うスコープ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFilters($query, array $filters)
    {
        // 検索機能（タイトル、著者名）
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('author_name', 'LIKE', "%{$search}%");
            });
        }

        // 企業でフィルタリング
        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        // プラットフォームでフィルタリング
        if (! empty($filters['platform_id'])) {
            $query->where('platform_id', $filters['platform_id']);
        }

        // 期間でフィルタリング
        if (! empty($filters['start_date'])) {
            $query->where('published_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->where('published_at', '<=', $filters['end_date'].' 23:59:59');
        }

        return $query;
    }

    /**
     * 記事のソートを行うスコープ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $sortBy
     * @param  string  $sortOrder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithSort($query, $sortBy = 'published_at', $sortOrder = 'desc')
    {
        // 許可されたソートカラムのみ受け付ける
        $allowedSortColumns = ['published_at', 'likes_count', 'bookmark_count'];

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('published_at', 'desc');
        }

        return $query;
    }
}
