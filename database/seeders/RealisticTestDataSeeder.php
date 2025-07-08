<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Company;
use App\Models\CompanyInfluenceScore;
use App\Models\CompanyRanking;
use App\Models\Platform;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class RealisticTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('リアルなテストデータを生成中...');

        $this->createPlatforms();
        $this->createTechCompanies();
        $this->createTechArticles();
        $this->createInfluenceScores();
        $this->createRankings();

        $this->command->info('リアルなテストデータの生成が完了しました！');
    }

    /**
     * Create realistic platforms.
     */
    private function createPlatforms(): void
    {
        $platforms = [
            ['name' => 'Qiita', 'domain' => 'qiita.com'],
            ['name' => 'Zenn', 'domain' => 'zenn.dev'],
            ['name' => 'はてなブログ', 'domain' => 'hatena.ne.jp'],
            ['name' => 'note', 'domain' => 'note.com'],
        ];

        foreach ($platforms as $platform) {
            Platform::firstOrCreate($platform);
        }
    }

    /**
     * Create realistic tech companies.
     */
    private function createTechCompanies(): void
    {
        $companies = [
            ['name' => 'サイボウズ', 'domain' => 'cybozu.com'],
            ['name' => 'メルカリ', 'domain' => 'mercari.com'],
            ['name' => 'リクルート', 'domain' => 'recruit.co.jp'],
            ['name' => 'ヤフー', 'domain' => 'yahoo.co.jp'],
            ['name' => 'DeNA', 'domain' => 'dena.com'],
            ['name' => 'CyberAgent', 'domain' => 'cyberagent.co.jp'],
            ['name' => 'LINE', 'domain' => 'line.me'],
            ['name' => 'Retty', 'domain' => 'retty.me'],
            ['name' => 'SmartNews', 'domain' => 'smartnews.com'],
            ['name' => 'Cookpad', 'domain' => 'cookpad.com'],
            ['name' => 'Wantedly', 'domain' => 'wantedly.com'],
            ['name' => 'freee', 'domain' => 'freee.co.jp'],
            ['name' => 'MoneyForward', 'domain' => 'moneyforward.com'],
            ['name' => 'Chatwork', 'domain' => 'chatwork.com'],
            ['name' => 'Sansan', 'domain' => 'sansan.com'],
        ];

        foreach ($companies as $companyData) {
            Company::firstOrCreate(
                ['domain' => $companyData['domain']],
                [
                    'name' => $companyData['name'],
                    'description' => $companyData['name'].'の技術チームによる技術情報の発信',
                    'website_url' => 'https://'.$companyData['domain'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Create realistic tech articles.
     */
    private function createTechArticles(): void
    {
        $companies = Company::all();
        $platforms = Platform::all();

        foreach ($companies as $company) {
            $articleCount = rand(10, 50);

            for ($i = 0; $i < $articleCount; $i++) {
                Article::create([
                    'company_id' => $company->id,
                    'platform_id' => $platforms->random()->id,
                    'title' => $this->generateRealisticTitle(),
                    'url' => 'https://example.com/article-'.uniqid(),
                    'domain' => $company->domain,
                    'platform' => $platforms->random()->name,
                    'author_name' => $this->generateAuthorName(),
                    'author' => strtolower(str_replace(' ', '', $this->generateAuthorName())),
                    'author_url' => 'https://example.com/author-'.uniqid(),
                    'published_at' => Carbon::now()->subDays(rand(1, 365)),
                    'bookmark_count' => $this->generateRealisticBookmarkCount(),
                    'likes_count' => $this->generateRealisticLikesCount(),
                    'scraped_at' => Carbon::now()->subDays(rand(0, 7)),
                ]);
            }
        }
    }

    /**
     * Create realistic influence scores.
     */
    private function createInfluenceScores(): void
    {
        $companies = Company::all();
        $periodTypes = ['monthly', 'weekly'];

        foreach ($companies as $company) {
            foreach ($periodTypes as $periodType) {
                $periods = $this->generatePeriods($periodType, 6);

                foreach ($periods as $period) {
                    $articleCount = Article::where('company_id', $company->id)
                        ->whereBetween('published_at', [$period['start'], $period['end']])
                        ->count();

                    $totalBookmarks = Article::where('company_id', $company->id)
                        ->whereBetween('published_at', [$period['start'], $period['end']])
                        ->sum('bookmark_count');

                    $totalScore = $this->calculateInfluenceScore($articleCount, $totalBookmarks);

                    CompanyInfluenceScore::create([
                        'company_id' => $company->id,
                        'period_type' => $periodType,
                        'period_start' => $period['start'],
                        'period_end' => $period['end'],
                        'total_score' => $totalScore,
                        'article_count' => $articleCount,
                        'total_bookmarks' => $totalBookmarks,
                        'calculated_at' => $period['end']->addHours(rand(1, 24)),
                    ]);
                }
            }
        }
    }

    /**
     * Create realistic rankings.
     */
    private function createRankings(): void
    {
        $periodTypes = ['monthly', 'weekly'];

        foreach ($periodTypes as $periodType) {
            $periods = $this->generatePeriods($periodType, 6);

            foreach ($periods as $period) {
                $scores = CompanyInfluenceScore::where('period_type', $periodType)
                    ->where('period_start', $period['start'])
                    ->where('period_end', $period['end'])
                    ->orderBy('total_score', 'desc')
                    ->get();

                foreach ($scores as $index => $score) {
                    CompanyRanking::create([
                        'company_id' => $score->company_id,
                        'ranking_period' => $periodType,
                        'rank_position' => $index + 1,
                        'total_score' => $score->total_score,
                        'article_count' => $score->article_count,
                        'total_bookmarks' => $score->total_bookmarks,
                        'period_start' => $period['start'],
                        'period_end' => $period['end'],
                        'calculated_at' => $score->calculated_at,
                    ]);
                }
            }
        }
    }

    /**
     * Generate realistic article titles.
     */
    private function generateRealisticTitle(): string
    {
        $titles = [
            'React Hooksを使った状態管理の最適化',
            'TypeScriptで始めるモダンWeb開発',
            'Docker ComposeでLocal開発環境を構築する',
            'AWS Lambda + API Gatewayでサーバーレス構築',
            'Vue.js 3.0の新機能とComposition API',
            'Next.js 13のApp Routerを使ったアプリケーション設計',
            'GraphQL + Apollo Clientでのデータ取得最適化',
            'Laravel 10の新機能とパフォーマンス改善',
            'PostgreSQLインデックス最適化のベストプラクティス',
            'Kubernetes上でのマイクロサービス設計',
            'CI/CDパイプラインの効率化とテスト自動化',
            'React Testingライブラリを使った効果的なテスト',
            'Node.js + Express.jsでのRESTful API設計',
            'Python Django REST frameworkによるAPI開発',
            'モノリスからマイクロサービスへの移行戦略',
            'Redisを使ったキャッシュ戦略とパフォーマンス最適化',
            'セキュアなWeb開発のためのOWASP Top 10対策',
            'Elasticsearchでの全文検索とデータ分析',
            'gRPCによる高性能なマイクロサービス通信',
            'Firebase Functionsでサーバーレス開発',
        ];

        return $titles[array_rand($titles)];
    }

    /**
     * Generate realistic author names.
     */
    private function generateAuthorName(): string
    {
        $names = [
            '田中太郎', '佐藤花子', '山田次郎', '鈴木三郎', '高橋美咲',
            '中村健太', '小林美優', '加藤大輝', '吉田萌', '渡辺拓海',
            '伊藤香織', '山本智也', '中島さくら', '西田翔太', '松本あかり',
        ];

        return $names[array_rand($names)];
    }

    /**
     * Generate realistic bookmark counts.
     */
    private function generateRealisticBookmarkCount(): int
    {
        $rand = rand(1, 100);

        if ($rand <= 50) {
            return rand(1, 50);
        } elseif ($rand <= 80) {
            return rand(51, 200);
        } elseif ($rand <= 95) {
            return rand(201, 500);
        } else {
            return rand(501, 2000);
        }
    }

    /**
     * Generate realistic likes counts.
     */
    private function generateRealisticLikesCount(): int
    {
        $rand = rand(1, 100);

        if ($rand <= 60) {
            return rand(0, 20);
        } elseif ($rand <= 85) {
            return rand(21, 100);
        } elseif ($rand <= 97) {
            return rand(101, 300);
        } else {
            return rand(301, 1000);
        }
    }

    /**
     * Generate periods for the given type.
     */
    private function generatePeriods(string $periodType, int $count): array
    {
        $periods = [];
        $current = Carbon::now();

        for ($i = 0; $i < $count; $i++) {
            if ($periodType === 'monthly') {
                $start = $current->copy()->subMonths($i)->startOfMonth();
                $end = $start->copy()->endOfMonth();
            } else {
                $start = $current->copy()->subWeeks($i)->startOfWeek();
                $end = $start->copy()->endOfWeek();
            }

            $periods[] = [
                'start' => $start,
                'end' => $end,
            ];
        }

        return array_reverse($periods);
    }

    /**
     * Calculate influence score based on articles and bookmarks.
     */
    private function calculateInfluenceScore(int $articleCount, int $totalBookmarks): float
    {
        $articleScore = $articleCount * 10;
        $bookmarkScore = $totalBookmarks * 0.5;

        return round($articleScore + $bookmarkScore, 2);
    }
}
