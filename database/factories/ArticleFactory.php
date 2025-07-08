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
        $platforms = ['Qiita', 'Zenn', 'はてなブログ', 'note'];
        $platform = $this->faker->randomElement($platforms);
        $domain = $this->getDomainForPlatform($platform);

        return [
            'platform_id' => Platform::factory(),
            'company_id' => Company::factory(),
            'title' => $this->generateTechTitle(),
            'url' => $this->faker->unique()->url(),
            'domain' => $domain,
            'platform' => $platform,
            'author_name' => $this->faker->name(),
            'author' => $this->faker->userName(),
            'author_url' => $this->faker->url(),
            'published_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'bookmark_count' => $this->faker->numberBetween(0, 1000),
            'likes_count' => $this->faker->numberBetween(0, 500),
            'scraped_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ];
    }

    /**
     * Generate a tech-related title.
     */
    private function generateTechTitle(): string
    {
        $techTerms = [
            'React', 'Vue.js', 'Angular', 'Laravel', 'Django', 'Node.js', 'Python',
            'JavaScript', 'TypeScript', 'PHP', 'Docker', 'Kubernetes', 'AWS', 'GCP',
            'MySQL', 'PostgreSQL', 'Redis', 'MongoDB', 'Elasticsearch', 'GraphQL',
            'CI/CD', 'DevOps', 'Microservices', 'API', 'Serverless', 'Machine Learning',
            'AI', 'データ分析', 'セキュリティ', 'パフォーマンス最適化',
        ];

        $actions = [
            'を使った開発手法',
            'の基本的な使い方',
            'で作るWebアプリケーション',
            'のベストプラクティス',
            'による効率化',
            'の導入方法',
            'のトラブルシューティング',
            'を活用した事例',
            'の設計パターン',
            '入門ガイド',
        ];

        $tech = $this->faker->randomElement($techTerms);
        $action = $this->faker->randomElement($actions);

        return $tech.$action;
    }

    /**
     * Get domain for platform.
     */
    private function getDomainForPlatform(string $platform): string
    {
        return match ($platform) {
            'Qiita' => 'qiita.com',
            'Zenn' => 'zenn.dev',
            'はてなブログ' => 'hatena.ne.jp',
            'note' => 'note.com',
            default => $this->faker->domainName(),
        };
    }

    /**
     * Create a popular article.
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'bookmark_count' => $this->faker->numberBetween(500, 2000),
            'likes_count' => $this->faker->numberBetween(200, 1000),
        ]);
    }

    /**
     * Create a recent article.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'scraped_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Create an article for a specific platform.
     */
    public function forPlatform(string $platform): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => $platform,
            'domain' => $this->getDomainForPlatform($platform),
        ]);
    }

    /**
     * Create an article with low engagement.
     */
    public function lowEngagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'bookmark_count' => $this->faker->numberBetween(0, 10),
            'likes_count' => $this->faker->numberBetween(0, 5),
        ]);
    }

    /**
     * Create an article with high engagement.
     */
    public function highEngagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'bookmark_count' => $this->faker->numberBetween(1000, 5000),
            'likes_count' => $this->faker->numberBetween(500, 2000),
        ]);
    }
}
