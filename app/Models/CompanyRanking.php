<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyRanking extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'ranking_period',
        'rank_position',
        'total_score',
        'article_count',
        'total_bookmarks',
        'period_start',
        'period_end',
        'calculated_at',
    ];

    protected $casts = [
        'rank_position' => 'integer',
        'total_score' => 'decimal:2',
        'article_count' => 'integer',
        'total_bookmarks' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'calculated_at' => 'datetime',
    ];

    /**
     * 企業とのリレーション
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * 期間タイプでフィルター
     */
    public function scopePeriodType($query, string $periodType)
    {
        return $query->where('ranking_period', $periodType);
    }

    /**
     * 順位範囲でフィルター
     */
    public function scopeRankRange($query, int $startRank, int $endRank)
    {
        return $query->whereBetween('rank_position', [$startRank, $endRank]);
    }

    /**
     * トップランクでフィルター
     */
    public function scopeTopRank($query, int $topCount = 10)
    {
        return $query->where('rank_position', '<=', $topCount);
    }

    /**
     * 順位順でソート
     */
    public function scopeOrderByRank($query, string $direction = 'asc')
    {
        return $query->orderBy('rank_position', $direction);
    }

    /**
     * スコア順でソート
     */
    public function scopeOrderByScore($query, string $direction = 'desc')
    {
        return $query->orderBy('total_score', $direction);
    }

    /**
     * 計算日時順でソート
     */
    public function scopeOrderByCalculatedAt($query, string $direction = 'desc')
    {
        return $query->orderBy('calculated_at', $direction);
    }

    /**
     * 最新の計算結果を取得
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('calculated_at', 'desc');
    }

    /**
     * 特定企業のランキングを取得
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * 期間範囲でフィルター
     */
    public function scopePeriodRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate]);
    }

    /**
     * アクティブな企業のランキングのみ取得
     */
    public function scopeActiveCompanies($query)
    {
        return $query->whereHas('company', function ($query) {
            $query->where('is_active', true);
        });
    }
}
