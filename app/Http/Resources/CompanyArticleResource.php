<?php

namespace App\Http\Resources;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Article
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\Platform|null $platform
 */
class CompanyArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'domain' => $this->domain,
            'platform' => $this->platform,
            'author_name' => $this->author_name,
            'author_url' => $this->author_url,
            'published_at' => $this->published_at,
            'bookmark_count' => $this->bookmark_count,
            'likes_count' => $this->likes_count,
            'company' => [
                'id' => $this->company_id,
                'name' => $this->whenLoaded('company', function () {
                    return $this->company?->name;
                }),
                'domain' => $this->whenLoaded('company', function () {
                    return $this->company?->domain;
                }),
            ],
            'platform_details' => $this->when($this->relationLoaded('platform') && is_object($this->platform), function () {
                return [
                    'id' => $this->platform?->id,
                    'name' => $this->platform?->name,
                    'base_url' => $this->platform?->base_url,
                ];
            }),
            'match_score' => $this->when(isset($this->match_score), $this->match_score),
            'scraped_at' => $this->scraped_at,
        ];
    }
}
