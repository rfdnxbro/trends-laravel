<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'id' => $this->id ?? null,
            'company' => [
                'id' => $this->company_id ?? null,
                'name' => $this->company_name ?? null,
                'domain' => $this->domain ?? null,
                'logo_url' => $this->logo_url ?? null,
            ],
            'rank_position' => $this->rank_position,
            'total_score' => (float) $this->total_score,
            'article_count' => $this->article_count,
            'total_bookmarks' => $this->total_bookmarks,
            'rank_change' => $this->rank_change ?? null,
            'period' => [
                'start' => $this->period_start,
                'end' => $this->period_end,
            ],
            'calculated_at' => $this->calculated_at,
        ];
    }
}