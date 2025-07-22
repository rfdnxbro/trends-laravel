<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Platform extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    // public function scrapingLogs(): HasMany
    // {
    //     return $this->hasMany(ScrapingLog::class);
    // }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * API用の選択カラムを取得
     *
     * @return array APIで返すカラム
     */
    public static function getApiColumns(): array
    {
        return ['id', 'name', 'base_url'];
    }

    /**
     * API用プラットフォーム一覧を取得
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getForApi()
    {
        return self::select(self::getApiColumns())
            ->orderBy('name')
            ->get();
    }
}
