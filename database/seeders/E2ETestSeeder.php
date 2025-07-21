<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Company;
use App\Models\Platform;
use Illuminate\Database\Seeder;

class E2ETestSeeder extends Seeder
{
    /**
     * E2Eテスト用のシードデータを作成
     */
    public function run(): void
    {
        // プラットフォームを作成
        $platform = Platform::firstOrCreate(
            ['name' => 'Qiita'],
            [
                'base_url' => 'https://qiita.com',
                'is_active' => true,
            ]
        );

        // 企業を作成
        $company = Company::firstOrCreate(
            ['domain' => 'example.com'],
            [
                'name' => 'Example Company',
                'website_url' => 'https://example.com',
                'logo_url' => 'https://example.com/logo.png',
                'description' => 'This is a test company for E2E testing',
            ]
        );

        // 記事を作成（URLをキーとして使用）
        Article::updateOrCreate(
            ['url' => 'https://qiita.com/example/items/test1'],
            [
                'title' => 'E2Eテスト用記事タイトル',
                'author_name' => 'テスト著者',
                'author' => 'test_author',
                'author_url' => 'https://qiita.com/test_author',
                'published_at' => now()->subDays(3),
                'engagement_count' => 100,
                'platform_id' => $platform->id,
                'company_id' => $company->id,
                'domain' => 'example.com',
                'platform' => 'Qiita',
                'scraped_at' => now()->subDays(1),
            ]
        );

        // 追加の記事を作成（ページネーションテスト用）
        for ($i = 2; $i <= 25; $i++) {
            Article::updateOrCreate(
                ['url' => "https://qiita.com/example/items/test{$i}"],
                [
                    'title' => "テスト記事 {$i}",
                    'author_name' => "著者 {$i}",
                    'author' => "author_{$i}",
                    'author_url' => "https://qiita.com/author_{$i}",
                    'published_at' => now()->subDays($i),
                    'engagement_count' => rand(50, 200),
                    'platform_id' => $platform->id,
                    'company_id' => $company->id,
                    'domain' => 'example.com',
                    'platform' => 'Qiita',
                    'scraped_at' => now()->subDays(1),
                ]
            );
        }
    }
}
