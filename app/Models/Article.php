<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory;

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
}
