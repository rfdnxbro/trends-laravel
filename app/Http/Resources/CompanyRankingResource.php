<?php

namespace App\Http\Resources;

use App\Models\CompanyRanking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CompanyRanking
 */
class CompanyRankingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id ?? null,
            'company' => [
                'id' => $this->resource->company_id ?? null,
                'name' => $this->resource->company_name ?? null,
                'domain' => $this->resource->domain ?? null,
                'logo_url' => $this->resource->logo_url ?? null,
            ],
            'rank_position' => $this->resource->rank_position,
            'total_score' => (float) $this->resource->total_score,
            'article_count' => $this->resource->article_count,
            'total_bookmarks' => $this->resource->total_bookmarks,
            'rank_change' => $this->resource->rank_change ?? null,
            'period' => [
                'start' => $this->resource->period_start,
                'end' => $this->resource->period_end,
            ],
            'calculated_at' => $this->resource->calculated_at,
        ];
    }
}
