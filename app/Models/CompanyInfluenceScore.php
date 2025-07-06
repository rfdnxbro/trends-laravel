<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyInfluenceScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'period_type',
        'period_start',
        'period_end',
        'total_score',
        'article_count',
        'total_bookmarks',
        'calculated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_score' => 'decimal:2',
        'article_count' => 'integer',
        'total_bookmarks' => 'integer',
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
        return $query->where('period_type', $periodType);
    }

    /**
     * 期間範囲でフィルター
     */
    public function scopePeriodRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate]);
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
     * 特定企業のスコアを取得
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}