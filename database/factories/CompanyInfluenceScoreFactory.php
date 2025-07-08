<?php

namespace Database\Factories;

use App\Constants\ScorePeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanyInfluenceScore>
 */
class CompanyInfluenceScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = $this->faker->dateTimeBetween('-30 days', '-1 day');
        $periodEnd = $this->faker->dateTimeBetween($periodStart, 'now');

        return [
            'company_id' => \App\Models\Company::factory(),
            'period_type' => $this->faker->randomElement(ScorePeriod::getValidPeriods()),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_score' => $this->faker->randomFloat(2, 0, 1000),
            'article_count' => $this->faker->numberBetween(0, 100),
            'total_bookmarks' => $this->faker->numberBetween(0, 10000),
            'calculated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Create a score for a specific period type.
     */
    public function forPeriod(string $periodType): static
    {
        return $this->state(fn (array $attributes) => [
            'period_type' => $periodType,
        ]);
    }

    /**
     * Create a high influence score.
     */
    public function highInfluence(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_score' => $this->faker->randomFloat(2, 500, 1000),
            'article_count' => $this->faker->numberBetween(20, 100),
            'total_bookmarks' => $this->faker->numberBetween(5000, 10000),
        ]);
    }

    /**
     * Create a low influence score.
     */
    public function lowInfluence(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_score' => $this->faker->randomFloat(2, 0, 100),
            'article_count' => $this->faker->numberBetween(0, 5),
            'total_bookmarks' => $this->faker->numberBetween(0, 500),
        ]);
    }

    /**
     * Create a recent score.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'calculated_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
        ]);
    }

    /**
     * Create a monthly score with proper period dates.
     */
    public function monthly(): static
    {
        $startOfMonth = $this->faker->dateTimeBetween('-6 months', 'now')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        return $this->state(fn (array $attributes) => [
            'period_type' => ScorePeriod::MONTHLY,
            'period_start' => $startOfMonth,
            'period_end' => $endOfMonth,
        ]);
    }

    /**
     * Create a weekly score with proper period dates.
     */
    public function weekly(): static
    {
        $startOfWeek = $this->faker->dateTimeBetween('-12 weeks', 'now')->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        return $this->state(fn (array $attributes) => [
            'period_type' => ScorePeriod::WEEKLY,
            'period_start' => $startOfWeek,
            'period_end' => $endOfWeek,
        ]);
    }

    /**
     * Create a daily score with proper period dates.
     */
    public function daily(): static
    {
        $date = $this->faker->dateTimeBetween('-30 days', 'now');

        return $this->state(fn (array $attributes) => [
            'period_type' => ScorePeriod::DAILY,
            'period_start' => $date->copy()->startOfDay(),
            'period_end' => $date->copy()->endOfDay(),
        ]);
    }
}
