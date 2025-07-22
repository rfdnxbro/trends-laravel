<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Log;

/**
 * 企業マッチングシステム
 *
 * スクレイピングした記事データから企業を自動識別するサービス。
 * データベースベースの動的検索により、新企業追加時にコード変更不要。
 *
 * マッチング戦略（優先順序）:
 * 0. Organizationベースマッチング - Qiita/Zennの組織情報（最優先）
 * 1. URLパターンマッチング - 企業ブログ等のURL
 * 2. ドメインマッチング - 企業ドメインでの一致
 * 3. ユーザー名マッチング - QiitaやZennのアカウント名
 * 4. キーワードマッチング - 記事タイトル内の企業名
 * 5. Zenn組織マッチング - Zenn組織記事の検出
 *
 * @see docs/wiki/企業マッチングシステム.md
 */
class CompanyMatcher
{
    /**
     * 複数の手法を組み合わせて会社を特定または新規作成する
     *
     * @param  array  $articleData  記事データ
     * @return Company|null 特定または新規作成された企業（またはnull）
     */
    public function identifyOrCreateCompany(array $articleData): ?Company
    {
        // 既存企業との照合を試行
        $company = $this->identifyCompany($articleData);
        if ($company) {
            return $company;
        }

        // 既存企業が見つからない場合、organization情報がある場合のみ新規作成を検討
        if (! empty($articleData['organization']) || ! empty($articleData['organization_name'])) {
            return $this->createNewCompanyFromOrganization($articleData);
        }

        return null;
    }

    /**
     * 複数の手法を組み合わせて会社を特定する
     */
    public function identifyCompany(array $articleData): ?Company
    {
        // 0. Organizationベースのマッチング（最優先）
        if (! empty($articleData['organization']) || ! empty($articleData['organization_name'])) {
            $company = $this->identifyByOrganization($articleData);
            if ($company) {
                Log::info("Organizationベースで会社を特定: {$company->name}", [
                    'organization' => $articleData['organization'] ?? null,
                    'organization_name' => $articleData['organization_name'] ?? null,
                    'article_title' => $articleData['title'] ?? null,
                ]);

                return $company;
            }
        }

        // 1. 特定のURL/ドメインパターンベースの紐づけ
        if (! empty($articleData['url'])) {
            $company = $this->identifyBySpecificUrl($articleData['url']);
            if ($company) {
                Log::info("特定URLベースで会社を特定: {$company->name}", [
                    'url' => $articleData['url'],
                    'article_title' => $articleData['title'] ?? null,
                ]);

                return $company;
            }
        }

        // 2. ドメインベースの紐づけ（完全一致のみ）
        if (! empty($articleData['domain'])) {
            $company = $this->identifyByExactDomain($articleData['domain']);
            if ($company) {
                Log::info("ドメインベースで会社を特定: {$company->name}", [
                    'domain' => $articleData['domain'],
                    'article_title' => $articleData['title'] ?? null,
                ]);

                return $company;
            }
        }

        // 3. ユーザー名ベースの紐づけ（Qiita/Zenn）
        if (! empty($articleData['platform']) && ! empty($articleData['author_name'])) {
            $company = $this->identifyByUsername($articleData['platform'], $articleData['author_name']);
            if ($company) {
                Log::info("ユーザー名ベースで会社を特定: {$company->name}", [
                    'platform' => $articleData['platform'],
                    'username' => $articleData['author_name'],
                    'article_title' => $articleData['title'] ?? null,
                ]);

                return $company;
            }
        }

        // 4. キーワードベースの紐づけ（タイトル内に明確に企業名が含まれる場合のみ）
        $company = $this->identifyByStrictKeywords($articleData);
        if ($company) {
            Log::info("キーワードベースで会社を特定: {$company->name}", [
                'article_title' => $articleData['title'] ?? null,
                'author' => $articleData['author'] ?? null,
            ]);

            return $company;
        }

        return null;
    }

    /**
     * 特定のURLパターンベースの会社識別
     */
    private function identifyBySpecificUrl(string $url): ?Company
    {
        // 全企業のURLパターンを動的に検索
        $companies = Company::whereNotNull('url_patterns')
            ->where('is_active', true)
            ->get();

        foreach ($companies as $company) {
            $patterns = $company->url_patterns ?? [];
            foreach ($patterns as $pattern) {
                if (str_contains($url, $pattern)) {
                    return $company;
                }
            }
        }

        // Zennの組織記事の特別処理
        if (str_contains($url, 'zenn.dev/')) {
            return $this->extractZennOrganization($url);
        }

        return null;
    }

    /**
     * 完全一致ドメインベースの会社識別
     */
    private function identifyByExactDomain(string $domain): ?Company
    {
        // 完全一致のみ（既存のdomain列）
        $company = Company::where('domain', $domain)->first();
        if ($company) {
            return $company;
        }

        // domain_patternsを使った柔軟な検索
        $companies = Company::whereNotNull('domain_patterns')
            ->where('is_active', true)
            ->get();

        foreach ($companies as $company) {
            $patterns = $company->domain_patterns ?? [];
            foreach ($patterns as $pattern) {
                if ($domain === $pattern || str_contains($domain, $pattern)) {
                    return $company;
                }
            }
        }

        return null;
    }

    /**
     * ユーザー名ベースの会社識別
     */
    private function identifyByUsername(string $platform, string $username): ?Company
    {
        $cleanUsername = $this->cleanUsername($username);

        switch ($platform) {
            case 'qiita':
                return Company::where('qiita_username', $cleanUsername)
                    ->orWhere('qiita_username', $username)
                    ->first();
            case 'zenn':
                return Company::where('zenn_username', $cleanUsername)
                    ->orWhere('zenn_username', $username)
                    ->first();
        }

        return null;
    }

    /**
     * 厳密なキーワードベースの会社識別（タイトルに明確に企業名が含まれる場合のみ）
     */
    private function identifyByStrictKeywords(array $articleData): ?Company
    {
        $title = strtolower($articleData['title'] ?? '');

        // 全企業のキーワードを動的に検索
        $companies = Company::whereNotNull('keywords')
            ->where('is_active', true)
            ->get();

        foreach ($companies as $company) {
            $keywords = $company->keywords ?? [];
            foreach ($keywords as $keyword) {
                // 単語境界を使って厳密にマッチ
                if (preg_match('/\b'.preg_quote(strtolower($keyword), '/').'\b/u', $title)) {
                    return $company;
                }
            }
        }

        return null;
    }

    /**
     * ユーザー名をクリーンアップ
     */
    private function cleanUsername(string $username): string
    {
        return trim(ltrim($username, '/@'));
    }

    /**
     * Zennの組織名を抽出
     */
    private function extractZennOrganization(string $url): ?Company
    {
        // zenn.dev/{org}/articles/{slug} のパターン
        if (preg_match('/zenn\.dev\/([^\/]+)\/articles/', $url, $matches)) {
            $orgName = $matches[1];

            // 全企業のzenn_organizationsを動的に検索
            $companies = Company::whereNotNull('zenn_organizations')
                ->where('is_active', true)
                ->get();

            foreach ($companies as $company) {
                $organizations = $company->zenn_organizations ?? [];
                if (in_array($orgName, $organizations)) {
                    return $company;
                }
            }
        }

        return null;
    }

    /**
     * Organizationベースの会社識別
     *
     * @param  array  $articleData  記事データ
     * @return Company|null 企業またはnull
     */
    private function identifyByOrganization(array $articleData): ?Company
    {
        $organization = $articleData['organization'] ?? null;
        $organizationName = $articleData['organization_name'] ?? null;
        $platform = $articleData['platform'] ?? null;

        // 1. organization スラグでの直接マッチング
        if ($organization && $platform) {
            $company = $this->matchByOrganizationSlug($organization, $platform);
            if ($company) {
                return $company;
            }
        }

        // 2. organization_name での部分マッチング
        if ($organizationName) {
            $company = $this->matchByOrganizationName($organizationName, $platform);
            if ($company) {
                return $company;
            }
        }

        // 3. 既存のzenn_organizations配列との照合（後方互換性）
        if ($organization && $platform === 'zenn') {
            $companies = Company::whereNotNull('zenn_organizations')
                ->where('is_active', true)
                ->get();

            foreach ($companies as $company) {
                $organizations = $company->zenn_organizations ?? [];
                if (in_array($organization, $organizations)) {
                    return $company;
                }
            }
        }

        return null;
    }

    /**
     * organizationスラグでの企業マッチング
     *
     * @param  string  $organizationSlug  組織スラグ
     * @param  string|null  $platform  プラットフォーム名
     * @return Company|null 企業またはnull
     */
    private function matchByOrganizationSlug(string $organizationSlug, ?string $platform): ?Company
    {
        // プラットフォーム別のusernameフィールドでマッチング
        switch ($platform) {
            case 'qiita':
                return Company::where('qiita_username', $organizationSlug)
                    ->where('is_active', true)
                    ->first();
            case 'zenn':
                return Company::where('zenn_username', $organizationSlug)
                    ->where('is_active', true)
                    ->first();
        }

        return null;
    }

    /**
     * organization名での企業マッチング
     *
     * @param  string  $organizationName  組織名
     * @param  string|null  $platform  プラットフォーム名
     * @return Company|null 企業またはnull
     */
    private function matchByOrganizationName(string $organizationName, ?string $platform): ?Company
    {
        // 企業名での完全一致
        $company = Company::where('name', $organizationName)
            ->where('is_active', true)
            ->first();
        if ($company) {
            return $company;
        }

        // キーワード配列での部分マッチング
        $companies = Company::whereNotNull('keywords')
            ->where('is_active', true)
            ->get();

        foreach ($companies as $company) {
            $keywords = $company->keywords ?? [];
            foreach ($keywords as $keyword) {
                if (stripos($organizationName, $keyword) !== false || stripos($keyword, $organizationName) !== false) {
                    return $company;
                }
            }
        }

        return null;
    }

    /**
     * organization情報から新規企業を作成
     *
     * @param  array  $articleData  記事データ
     * @return Company|null 新規作成された企業またはnull
     */
    private function createNewCompanyFromOrganization(array $articleData): ?Company
    {
        try {
            $organization = $articleData['organization'] ?? null;
            $organizationName = $articleData['organization_name'] ?? null;
            $organizationUrl = $articleData['organization_url'] ?? null;
            $platform = $articleData['platform'] ?? null;

            // 新規企業作成に最低限必要な情報をチェック
            if (! $organizationName && ! $organization) {
                return null;
            }

            // 企業名を決定（organization_name を優先、なければ organization）
            $companyName = $organizationName ?: $organization;

            // 重複チェック（同じ名前の企業が既に存在しないか）
            $existingCompany = Company::where('name', $companyName)->first();
            if ($existingCompany) {
                Log::info("企業作成スキップ（既存企業と重複）: {$companyName}");

                return null;
            }

            // 新規企業データを準備
            $companyData = [
                'name' => $companyName,
                'domain' => $this->generateDomainFromName($companyName), // ダミードメイン生成
                'is_active' => false, // Issue要求: 新規作成企業はis_active=false
            ];

            // プラットフォーム固有情報を追加
            if ($platform === 'qiita' && $organization) {
                $companyData['qiita_username'] = $organization;
            } elseif ($platform === 'zenn' && $organization) {
                $companyData['zenn_username'] = $organization;
                $companyData['zenn_organizations'] = [$organization];
            }

            // organization_url がある場合はURLとして設定
            if ($organizationUrl) {
                $companyData['website_url'] = $organizationUrl;
            }

            $company = Company::create($companyData);

            Log::info("新規企業を自動作成: {$company->name}", [
                'platform' => $platform,
                'organization' => $organization,
                'organization_name' => $organizationName,
                'is_active' => false,
                'article_title' => $articleData['title'] ?? null,
            ]);

            return $company;

        } catch (\Exception $e) {
            Log::error('新規企業作成エラー', [
                'article_data' => $articleData,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 企業名からダミードメインを生成
     *
     * @param  string  $companyName  企業名
     * @return string ダミードメイン
     */
    private function generateDomainFromName(string $companyName): string
    {
        // 企業名を英数字のみに変換してドメイン化
        $domain = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $companyName));

        // 空になった場合やあまりに短い場合は固定プレフィックスを使用
        if (strlen($domain) < 3) {
            $domain = 'auto-'.uniqid();
        }

        // 重複回避のためにタイムスタンプを付加
        $domain .= '-'.time().'.example.com';

        return $domain;
    }
}
