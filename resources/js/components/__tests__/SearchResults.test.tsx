import React from 'react';
import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import SearchResults from '../SearchResults';
import { Company, Article, Platform } from '../../types';

const renderWithRouter = (component: React.ReactElement) => {
    return render(
        <BrowserRouter>
            {component}
        </BrowserRouter>
    );
};

const mockPlatform: Platform = {
    id: 1,
    name: 'Qiita',
    base_url: 'https://qiita.com',
    is_active: true,
    created_at: '2023-01-01T00:00:00.000000Z',
    updated_at: '2023-01-01T00:00:00.000000Z'
};

const mockCompany: Company = {
    id: 1,
    name: 'テスト企業',
    domain: 'test.com',
    description: 'テスト企業の説明',
    logo_url: 'https://example.com/logo.png',
    website_url: 'https://test.com',
    created_at: '2023-01-01T00:00:00.000000Z',
    updated_at: '2023-01-01T00:00:00.000000Z'
};

const mockCompanyWithScore = {
    ...mockCompany,
    match_score: 0.85
};

const mockArticle: Article = {
    id: 1,
    title: 'テスト記事のタイトル',
    url: 'https://qiita.com/test/items/123',
    author_name: 'テスト著者',
    published_at: '2023-01-01T00:00:00.000000Z',
    engagement_count: 100,
    platform_id: 1,
    platform: mockPlatform,
    domain: 'qiita.com',
    created_at: '2023-01-01T00:00:00.000000Z',
    updated_at: '2023-01-01T00:00:00.000000Z',
    company: mockCompany
};

const mockArticleWithScore = {
    ...mockArticle,
    match_score: 0.92
};

describe('SearchResults', () => {
    describe('ローディング状態', () => {
        test('ローディング中は適切な表示がされる', () => {
            renderWithRouter(
                <SearchResults
                    loading={true}
                    query="test"
                    totalResults={0}
                    searchTime={0}
                />
            );
            
            expect(screen.getByText('検索中...')).toBeInTheDocument();
            expect(screen.getByRole('status', { hidden: true })).toBeInTheDocument();
        });
    });

    describe('検索結果なし', () => {
        test('結果がない場合の表示', () => {
            renderWithRouter(
                <SearchResults
                    companies={[]}
                    articles={[]}
                    query="notfound"
                    totalResults={0}
                    searchTime={0.1}
                />
            );
            
            expect(screen.getByText('検索結果が見つかりません')).toBeInTheDocument();
            expect(screen.getByText('「notfound」に一致する結果がありませんでした。')).toBeInTheDocument();
            expect(screen.getByText('別のキーワードで検索してみてください。')).toBeInTheDocument();
        });
    });

    describe('検索結果表示', () => {
        test('検索結果サマリーが表示される', () => {
            renderWithRouter(
                <SearchResults
                    companies={[mockCompanyWithScore]}
                    articles={[mockArticleWithScore]}
                    query="test"
                    totalResults={2}
                    searchTime={0.123}
                />
            );
            
            expect(screen.getByText('test', { exact: false })).toBeInTheDocument();
            expect(screen.getByText('2件', { exact: false })).toBeInTheDocument();
            expect(screen.getByText('0.123秒', { exact: false })).toBeInTheDocument();
        });

        test('企業検索結果が表示される', () => {
            renderWithRouter(
                <SearchResults
                    companies={[mockCompanyWithScore]}
                    articles={[]}
                    query="test"
                    totalResults={1}
                    searchTime={0.1}
                />
            );
            
            expect(screen.getByText('企業 (1件)')).toBeInTheDocument();
            expect(screen.getByText('テスト企業')).toBeInTheDocument();
            expect(screen.getByText('test.com')).toBeInTheDocument();
            expect(screen.getByText('テスト企業の説明')).toBeInTheDocument();
            expect(screen.getByText('85%')).toBeInTheDocument(); // match_score
        });

        test('記事検索結果が表示される', () => {
            renderWithRouter(
                <SearchResults
                    companies={[]}
                    articles={[mockArticleWithScore]}
                    query="test"
                    totalResults={1}
                    searchTime={0.1}
                />
            );
            
            expect(screen.getByText('記事 (1件)')).toBeInTheDocument();
            expect(screen.getByText('テスト記事のタイトル')).toBeInTheDocument();
            expect(screen.getByText('テスト著者')).toBeInTheDocument();
            expect(screen.getByText('100 bookmarks')).toBeInTheDocument();
            expect(screen.getByText('Qiita')).toBeInTheDocument();
            expect(screen.getByText('92%')).toBeInTheDocument(); // match_score
        });

        test('企業と記事の両方が表示される', () => {
            renderWithRouter(
                <SearchResults
                    companies={[mockCompanyWithScore]}
                    articles={[mockArticleWithScore]}
                    query="test"
                    totalResults={2}
                    searchTime={0.1}
                />
            );
            
            expect(screen.getByText('企業 (1件)')).toBeInTheDocument();
            expect(screen.getByText('記事 (1件)')).toBeInTheDocument();
            expect(screen.getByText('テスト企業')).toBeInTheDocument();
            expect(screen.getByText('テスト記事のタイトル')).toBeInTheDocument();
        });
    });

    describe('企業検索結果の詳細', () => {
        test('ロゴがない場合はデフォルトアイコンが表示される', () => {
            const companyWithoutLogo = { ...mockCompanyWithScore, logo_url: undefined };
            
            renderWithRouter(
                <SearchResults
                    companies={[companyWithoutLogo]}
                    articles={[]}
                    query="test"
                    totalResults={1}
                    searchTime={0.1}
                />
            );
            
            expect(screen.getByText('テスト企業')).toBeInTheDocument();
            // デフォルトアイコンのSVGが表示されることを確認
            expect(screen.getByRole('img')).toBeInTheDocument();
        });

        test('説明がない場合は表示されない', () => {
            const companyWithoutDescription = { ...mockCompanyWithScore, description: undefined };
            
            renderWithRouter(
                <SearchResults
                    companies={[companyWithoutDescription]}
                    articles={[]}
                    query="test"
                    totalResults={1}
                    searchTime={0.1}
                />
            );
            
            expect(screen.getByText('テスト企業')).toBeInTheDocument();
            expect(screen.queryByText('テスト企業の説明')).not.toBeInTheDocument();
        });

        test('match_scoreがない場合はスコア表示されない', () => {
            renderWithRouter(
                <SearchResults
                    companies={[mockCompany]}
                    articles={[]}
                    query="test"
                    totalResults={1}
                    searchTime={0.1}
                />
            );
            
            expect(screen.getByText('テスト企業')).toBeInTheDocument();
            expect(screen.queryByText(/\d+%/)).not.toBeInTheDocument();
        });
    });

    describe('記事検索結果の詳細', () => {
        test('著者名がない場合はUnknownが表示される', () => {
            const articleWithoutAuthor = { ...mockArticleWithScore, author_name: undefined };
            
            renderWithRouter(
                <SearchResults
                    companies={[]}
                    articles={[articleWithoutAuthor]}
                    query="test"
                    totalResults={1}
                    searchTime={0.1}
                />
            );
            
            expect(screen.getByText('Unknown')).toBeInTheDocument();
        });

        test('企業情報がある場合は企業リンクが表示される', () => {
            renderWithRouter(
                <SearchResults
                    companies={[]}
                    articles={[mockArticleWithScore]}
                    query="test"
                    totalResults={1}
                    searchTime={0.1}
                />
            );
            
            expect(screen.getByText('テスト企業')).toBeInTheDocument();
        });

        test('企業情報がない場合は企業リンクが表示されない', () => {
            const articleWithoutCompany = { ...mockArticleWithScore, company: null };
            
            renderWithRouter(
                <SearchResults
                    companies={[]}
                    articles={[articleWithoutCompany]}
                    query="test"
                    totalResults={1}
                    searchTime={0.1}
                />
            );
            
            expect(screen.queryByText('テスト企業')).not.toBeInTheDocument();
        });

        test('プラットフォーム名が表示される', () => {
            renderWithRouter(
                <SearchResults
                    companies={[]}
                    articles={[mockArticleWithScore]}
                    query="test"
                    totalResults={1}
                    searchTime={0.1}
                />
            );
            
            expect(screen.getByText('Qiita')).toBeInTheDocument();
        });

        test('プラットフォーム情報がない場合はドメインが表示される', () => {
            const articleWithoutPlatform = { 
                ...mockArticleWithScore, 
                platform: undefined,
                domain: 'example.com'
            };
            
            renderWithRouter(
                <SearchResults
                    companies={[]}
                    articles={[articleWithoutPlatform]}
                    query="test"
                    totalResults={1}
                    searchTime={0.1}
                />
            );
            
            expect(screen.getByText('example.com')).toBeInTheDocument();
        });

        test('日付が正しくフォーマットされる', () => {
            renderWithRouter(
                <SearchResults
                    companies={[]}
                    articles={[mockArticleWithScore]}
                    query="test"
                    totalResults={1}
                    searchTime={0.1}
                />
            );
            
            expect(screen.getByText('2023/1/1')).toBeInTheDocument();
        });
    });

    describe('リンク動作', () => {
        test('企業カードをクリックすると企業詳細ページに遷移する', () => {
            renderWithRouter(
                <SearchResults
                    companies={[mockCompanyWithScore]}
                    articles={[]}
                    query="test"
                    totalResults={1}
                    searchTime={0.1}
                />
            );
            
            const companyLink = screen.getByText('テスト企業').closest('a');
            expect(companyLink).toHaveAttribute('href', '/companies/1');
        });

        test('記事タイトルをクリックすると記事URLに遷移する', () => {
            renderWithRouter(
                <SearchResults
                    companies={[]}
                    articles={[mockArticleWithScore]}
                    query="test"
                    totalResults={1}
                    searchTime={0.1}
                />
            );
            
            const articleLink = screen.getByRole('link', { name: 'テスト記事のタイトル' });
            expect(articleLink).toHaveAttribute('href', 'https://qiita.com/test/items/123');
            expect(articleLink).toHaveAttribute('target', '_blank');
            expect(articleLink).toHaveAttribute('rel', 'noopener noreferrer');
        });
    });
});