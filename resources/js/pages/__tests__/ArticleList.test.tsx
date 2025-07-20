import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { BrowserRouter } from 'react-router-dom';
import { vi } from 'vitest';
import ArticleList from '../ArticleList';
import { apiService } from '../../services/api';

// APIサービスをモック
vi.mock('../../services/api', () => ({
    apiService: {
        getArticles: vi.fn(),
    },
}));

const mockApiService = apiService as {
    getArticles: ReturnType<typeof vi.fn>;
};

// テストヘルパー
const createTestQueryClient = () => new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

const renderWithProviders = (component: React.ReactElement) => {
    const queryClient = createTestQueryClient();
    return render(
        <QueryClientProvider client={queryClient}>
            <BrowserRouter>
                {component}
            </BrowserRouter>
        </QueryClientProvider>
    );
};

const mockArticlesResponse = {
    data: {
        data: [
            {
                id: 1,
                title: 'Test Article 1',
                url: 'https://example.com/article1',
                author_name: 'Test Author',
                published_at: '2023-01-01T00:00:00Z',
                bookmark_count: 10,
                company: {
                    id: 1,
                    name: 'Test Company',
                    domain: 'example.com',
                    logo_url: 'https://example.com/logo.png',
                    created_at: '2023-01-01T00:00:00Z',
                    updated_at: '2023-01-01T00:00:00Z',
                },
                platform: {
                    id: 1,
                    name: 'Test Platform',
                },
                platform_id: 1,
                company_id: 1,
                created_at: '2023-01-01T00:00:00Z',
                updated_at: '2023-01-01T00:00:00Z',
            },
            {
                id: 2,
                title: 'Test Article 2',
                url: 'https://example.com/article2',
                author_name: 'Test Author 2',
                published_at: '2023-01-02T00:00:00Z',
                bookmark_count: 5,
                company: {
                    id: 2,
                    name: 'Test Company 2',
                    domain: 'example2.com',
                    logo_url: undefined,
                    created_at: '2023-01-02T00:00:00Z',
                    updated_at: '2023-01-02T00:00:00Z',
                },
                platform: {
                    id: 1,
                    name: 'Test Platform',
                },
                platform_id: 1,
                company_id: 2,
                created_at: '2023-01-02T00:00:00Z',
                updated_at: '2023-01-02T00:00:00Z',
            },
        ],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 2,
        },
    },
};

beforeEach(() => {
    vi.clearAllMocks();
});

describe('ArticleList', () => {
    it('記事一覧が正しく表示される', async () => {
        mockApiService.getArticles.mockResolvedValueOnce(mockArticlesResponse);

        renderWithProviders(<ArticleList />);

        await waitFor(() => {
            expect(screen.getByText('記事一覧')).toBeInTheDocument();
        });

        expect(screen.getByText('Test Article 1')).toBeInTheDocument();
        expect(screen.getByText('Test Article 2')).toBeInTheDocument();
        expect(screen.getByText('Test Company')).toBeInTheDocument();
        expect(screen.getByText('Test Company 2')).toBeInTheDocument();
    });

    it('企業のロゴが表示される', async () => {
        mockApiService.getArticles.mockResolvedValueOnce(mockArticlesResponse);

        renderWithProviders(<ArticleList />);

        await waitFor(() => {
            expect(screen.getByText('記事一覧')).toBeInTheDocument();
        });

        const logo = screen.getByAltText('Test Company');
        expect(logo).toBeInTheDocument();
        expect(logo).toHaveAttribute('src', 'https://example.com/logo.png');
    });

    it('ブックマーク数が表示される', async () => {
        mockApiService.getArticles.mockResolvedValueOnce(mockArticlesResponse);

        renderWithProviders(<ArticleList />);

        await waitFor(() => {
            expect(screen.getByText('記事一覧')).toBeInTheDocument();
        });

        expect(screen.getByText('10')).toBeInTheDocument();
        expect(screen.getByText('5')).toBeInTheDocument();
    });

    it('記事のリンクが正しく設定される', async () => {
        mockApiService.getArticles.mockResolvedValueOnce(mockArticlesResponse);

        renderWithProviders(<ArticleList />);

        await waitFor(() => {
            expect(screen.getByText('記事一覧')).toBeInTheDocument();
        });

        const links = screen.getAllByText('記事を読む');
        expect(links[0].closest('a')).toHaveAttribute('href', 'https://example.com/article1');
        expect(links[0].closest('a')).toHaveAttribute('target', '_blank');
        expect(links[1].closest('a')).toHaveAttribute('href', 'https://example.com/article2');
        expect(links[1].closest('a')).toHaveAttribute('target', '_blank');
    });

    it('ローディング状態が表示される', () => {
        mockApiService.getArticles.mockImplementationOnce(
            () => new Promise(() => {}) // 永続的にpending状態
        );

        renderWithProviders(<ArticleList />);

        expect(screen.getByText('記事データを読み込み中...')).toBeInTheDocument();
    });

    it('エラー状態が表示される', async () => {
        mockApiService.getArticles.mockRejectedValueOnce(new Error('Network error'));

        renderWithProviders(<ArticleList />);

        await waitFor(() => {
            expect(screen.getByText('記事一覧の読み込みに失敗しました')).toBeInTheDocument();
        });

        expect(screen.getByText('再読み込み')).toBeInTheDocument();
    });

    it('記事が見つからない場合のメッセージが表示される', async () => {
        const emptyResponse = {
            data: {
                data: [],
                meta: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 20,
                    total: 0,
                },
            },
        };

        mockApiService.getArticles.mockResolvedValueOnce(emptyResponse);

        renderWithProviders(<ArticleList />);

        await waitFor(() => {
            expect(screen.getByText('記事一覧')).toBeInTheDocument();
        });

        expect(screen.getByText('記事が見つかりませんでした')).toBeInTheDocument();
    });
});