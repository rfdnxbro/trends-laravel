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
     * 複数の手法を組み合わせて会社を特定する
     */
    public function identifyCompany(array $articleData): ?Company
    {
        // 1. 特定のURL/ドメインパターンベースの紐づけ（最優先）
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
}
