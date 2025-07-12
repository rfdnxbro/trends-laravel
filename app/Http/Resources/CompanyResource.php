<?php

namespace App\Http\Resources;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
class CompanyResource extends JsonResource
{
    protected $currentRankings;

    public function __construct($resource, $currentRankings = null)
    {
        parent::__construct($resource);
        $this->currentRankings = $currentRankings;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'domain' => $this->domain,
            'description' => $this->description,
            'logo_url' => $this->logo_url,
            'website_url' => $this->website_url,
            'is_active' => $this->is_active,
            'current_rankings' => $this->formatCurrentRankings(),
            'recent_articles' => CompanyArticleResource::collection($this->whenLoaded('articles')),
            'total_articles' => $this->whenLoaded('articles', function () {
                return $this->articles ? $this->articles->count() : 0;
            }, 0),
            'ranking_history' => $this->formatRankingHistory(),
            'match_score' => $this->when(isset($this->match_score), $this->match_score),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function formatCurrentRankings(): array
    {
        if (! $this->currentRankings || ! is_array($this->currentRankings)) {
            return [];
        }

        $rankings = [];
        foreach ($this->currentRankings as $period => $ranking) {
            if ($ranking && is_object($ranking)) {
                $rankings[] = [
                    'period' => $period,
                    'rank_position' => $ranking->rank_position,
                    'total_score' => (float) $ranking->total_score,
                    'article_count' => $ranking->article_count,
                    'total_bookmarks' => $ranking->total_bookmarks,
                    'calculated_at' => $ranking->calculated_at,
                ];
            }
        }

        return $rankings;
    }

    private function formatRankingHistory(): array
    {
        if (! $this->currentRankings || ! is_array($this->currentRankings)) {
            return [];
        }

        $history = [];
        foreach ($this->currentRankings as $period => $ranking) {
            if ($ranking && is_object($ranking)) {
                $history[] = [
                    'date' => $ranking->calculated_at ? $ranking->calculated_at->format('Y-m-d') : now()->format('Y-m-d'),
                    'rank' => $ranking->rank_position,
                    'influence_score' => (float) $ranking->total_score,
                ];
            }
        }

        return $history;
    }
}
