<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}
