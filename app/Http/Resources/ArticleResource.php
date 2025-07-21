<?php

namespace App\Http\Resources;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Article
 *
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\Platform|null $platform
 */
class ArticleResource extends JsonResource
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
            'author_name' => $this->author_name,
            'author' => $this->author,
            'author_url' => $this->author_url,
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'engagement_count' => (int) $this->engagement_count,
            'platform' => $this->when($this->relationLoaded('platform'), function () {
                $platformRelation = $this->getRelation('platform');

                return [
                    'id' => $platformRelation?->id,
                    'name' => $platformRelation?->name,
                    'base_url' => $platformRelation?->base_url,
                ];
            }),
            'company' => $this->when($this->relationLoaded('company'), function () {
                $companyRelation = $this->getRelation('company');

                if ($companyRelation === null) {
                    return null;
                }

                return [
                    'id' => $companyRelation->id,
                    'name' => $companyRelation->name,
                    'domain' => $companyRelation->domain,
                    'logo_url' => $companyRelation->logo_url,
                    'website_url' => $companyRelation->website_url,
                ];
            }),
            'domain' => $this->domain,
            'platform_name' => $this->platform,
            'scraped_at' => $this->scraped_at->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
