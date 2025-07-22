<?php

namespace Database\Factories;

use App\Constants\ArticleEngagement;
use App\Constants\Platform as PlatformConstants;
use App\Models\Company;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $platform = $this->faker->randomElement(PlatformConstants::getValidPlatforms());
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
            'engagement_count' => $this->faker->numberBetween(...ArticleEngagement::getNormalBookmarkRange()),
            'scraped_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ];
    }

    /**
     * 技術系のタイトルを生成
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
     * プラットフォームのドメインを取得
     */
    private function getDomainForPlatform(string $platform): string
    {
        return match ($platform) {
            PlatformConstants::QIITA => 'qiita.com',
            PlatformConstants::ZENN => 'zenn.dev',
            PlatformConstants::HATENA_BOOKMARK => 'b.hatena.ne.jp',
            default => $this->faker->domainName(),
        };
    }

    /**
     * 人気記事を作成
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'engagement_count' => $this->faker->numberBetween(...ArticleEngagement::getPopularLikesRange()),
        ]);
    }

    /**
     * 最近の記事を作成
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'scraped_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * 特定プラットフォーム用の記事を作成
     */
    public function forPlatform(string $platform): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => $platform,
            'domain' => $this->getDomainForPlatform($platform),
        ]);
    }

    /**
     * 低エンゲージメントの記事を作成
     */
    public function lowEngagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'engagement_count' => $this->faker->numberBetween(...ArticleEngagement::getLowBookmarkRange()),
        ]);
    }

    /**
     * 高エンゲージメントの記事を作成
     */
    public function highEngagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'engagement_count' => $this->faker->numberBetween(...ArticleEngagement::getHighLikesRange()),
        ]);
    }
}
