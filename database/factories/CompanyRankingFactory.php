<?php

namespace Database\Factories;

use App\Constants\RankingPeriod;
use App\Models\Company;
use App\Models\CompanyRanking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanyRanking>
 */
class CompanyRankingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'ranking_period' => $this->faker->randomElement(RankingPeriod::getValidPeriods()),
            'rank_position' => $this->faker->numberBetween(1, 100),
            'total_score' => $this->faker->randomFloat(2, 0, 1000),
            'article_count' => $this->faker->numberBetween(1, 50),
            'total_bookmarks' => $this->faker->numberBetween(100, 10000),
            'period_start' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'period_end' => $this->faker->dateTimeBetween('now', '+1 day'),
            'calculated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Create a ranking for a specific period.
     */
    public function forPeriod(string $period): static
    {
        return $this->state(fn (array $attributes) => [
            'ranking_period' => $period,
        ]);
    }

    /**
     * Create a top ranking (1-10).
     */
    public function topRanking(): static
    {
        return $this->state(fn (array $attributes) => [
            'rank_position' => $this->faker->numberBetween(1, 10),
            'total_score' => $this->faker->randomFloat(2, 500, 1000),
            'article_count' => $this->faker->numberBetween(20, 50),
            'total_bookmarks' => $this->faker->numberBetween(5000, 10000),
        ]);
    }

    /**
     * Create a low ranking (51-100).
     */
    public function lowRanking(): static
    {
        return $this->state(fn (array $attributes) => [
            'rank_position' => $this->faker->numberBetween(51, 100),
            'total_score' => $this->faker->randomFloat(2, 0, 100),
            'article_count' => $this->faker->numberBetween(1, 5),
            'total_bookmarks' => $this->faker->numberBetween(100, 1000),
        ]);
    }

    /**
     * Create a recent ranking.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'calculated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}