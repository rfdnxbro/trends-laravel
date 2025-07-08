<?php

namespace Database\Factories;

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
        return [
            'company_id' => \App\Models\Company::factory(),
            'period_type' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
            'period_start' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'period_end' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'total_score' => $this->faker->randomFloat(2, 0, 1000),
            'article_count' => $this->faker->numberBetween(0, 100),
            'total_bookmarks' => $this->faker->numberBetween(0, 1000),
            'calculated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
