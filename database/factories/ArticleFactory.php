<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'platform_id' => Platform::factory(),
            'company_id' => Company::factory(),
            'title' => $this->faker->sentence(6),
            'url' => $this->faker->url(),
            'domain' => $this->faker->domainName(),
            'platform' => $this->faker->randomElement(['Qiita', 'Zenn', 'はてなブログ', 'note']),
            'author_name' => $this->faker->name(),
            'author' => $this->faker->userName(),
            'author_url' => $this->faker->url(),
            'published_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'bookmark_count' => $this->faker->numberBetween(0, 1000),
            'likes_count' => $this->faker->numberBetween(0, 500),
            'scraped_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ];
    }
}
