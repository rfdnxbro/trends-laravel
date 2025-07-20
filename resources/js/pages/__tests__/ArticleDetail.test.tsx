import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import { vi } from 'vitest';
import ArticleDetail from '../ArticleDetail';
import { apiService } from '../../services/api';

// APIサービスをモック
vi.mock('../../services/api', () => ({
    apiService: {
        getArticleDetail: vi.fn(),
    },
}));

const mockApiService = apiService as {
    getArticleDetail: ReturnType<typeof vi.fn>;
};

// テストヘルパー
const createTestQueryClient = () => new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
            refetchOnWindowFocus: false,
            staleTime: 0,
            cacheTime: 0,
        },
    },
    logger: {
        log: () => {},
        warn: () => {},
        error: () => {},
    },
});

const renderWithProviders = (articleId: string = '1') => {
    const queryClient = createTestQueryClient();
    return render(
        <QueryClientProvider client={queryClient}>
            <MemoryRouter initialEntries={[`/articles/${articleId}`]}>
                <Routes>
                    <Route path="/articles/:id" element={<ArticleDetail />} />
                </Routes>
            </MemoryRouter>
        </QueryClientProvider>
    );
};

const mockArticleResponse = {
    data: {
            id: 1,
            title: 'Test Article Title',
            url: 'https://example.com/article',
            author: 'Test Author',
            author_name: 'Test Author',
            author_url: 'https://example.com/author',
            published_at: '2023-01-01T12:00:00Z',
            bookmark_count: 25,
            likes_count: 10,
            view_count: 100,
            domain: 'example.com',
            scraped_at: '2023-01-01T10:00:00Z',
            created_at: '2023-01-01T09:00:00Z',
            updated_at: '2023-01-01T11:00:00Z',
            company: {
                id: 1,
                name: 'Test Company',
                domain: 'company.com',
                logo_url: 'https://company.com/logo.png',
                description: 'Test company description',
                website_url: 'https://company.com',
                created_at: '2023-01-01T00:00:00Z',
                updated_at: '2023-01-01T00:00:00Z',
            },
            platform: {
                id: 1,
                name: 'Test Platform',
                base_url: 'https://platform.com',
            },
            platform_id: 1,
            company_id: 1,
        },
};

beforeEach(() => {
    vi.clearAllMocks();
    mockApiService.getArticleDetail.mockReset();
    // デフォルトで成功レスポンスを返す
    mockApiService.getArticleDetail.mockResolvedValue(mockArticleResponse);
});

describe('ArticleDetail', () => {
    it('記事詳細が正しく表示される', async () => {

        renderWithProviders('1');

        await waitFor(() => {
            expect(screen.getByText('Test Article Title')).toBeInTheDocument();
        });

        expect(screen.getByText('Test Company')).toBeInTheDocument();
        expect(screen.getAllByText('Test Platform')[0]).toBeInTheDocument();
        expect(screen.getByText('記事を読む')).toBeInTheDocument();
    });

    it('企業情報セクションが表示される', async () => {
        mockApiService.getArticleDetail.mockResolvedValue(mockArticleResponse);

        renderWithProviders('1');

        await waitFor(() => {
            expect(screen.getByText('企業情報')).toBeInTheDocument();
        });

        expect(screen.getByText('Test Company')).toBeInTheDocument();
        expect(screen.getByText('Test company description')).toBeInTheDocument();
        const websiteLink = screen.getByText('ウェブサイト');
        expect(websiteLink.closest('a')).toHaveAttribute('href', 'https://company.com');
    });

    it('統計情報が表示される', async () => {
        mockApiService.getArticleDetail.mockResolvedValue(mockArticleResponse);

        renderWithProviders('1');

        await waitFor(() => {
            expect(screen.getByText('統計情報')).toBeInTheDocument();
        });

        expect(screen.getByText('ブックマーク')).toBeInTheDocument();
        expect(screen.getByText('25')).toBeInTheDocument();
        expect(screen.getByText('いいね')).toBeInTheDocument();
        expect(screen.getByText('10')).toBeInTheDocument();
        expect(screen.getByText('閲覧数')).toBeInTheDocument();
        expect(screen.getByText('100')).toBeInTheDocument();
    });

    it('記事URLセクションが機能する', async () => {
        mockApiService.getArticleDetail.mockResolvedValue(mockArticleResponse);

        renderWithProviders('1');

        await waitFor(() => {
            expect(screen.getByText('記事URL')).toBeInTheDocument();
        });

        expect(screen.getByDisplayValue('https://example.com/article')).toBeInTheDocument();
        expect(screen.getByText('コピー')).toBeInTheDocument();
    });

    it('プラットフォーム詳細が表示される', async () => {
        mockApiService.getArticleDetail.mockResolvedValue(mockArticleResponse);

        renderWithProviders('1');

        await waitFor(() => {
            expect(screen.getByText('プラットフォーム')).toBeInTheDocument();
        });

        expect(screen.getAllByText('Test Platform')[0]).toBeInTheDocument();
        expect(screen.getByText('https://platform.com')).toBeInTheDocument();
        expect(screen.getByText('example.com')).toBeInTheDocument();
    });

    it('メタデータが表示される', async () => {
        mockApiService.getArticleDetail.mockResolvedValue(mockArticleResponse);

        renderWithProviders('1');

        await waitFor(() => {
            expect(screen.getByText('メタデータ')).toBeInTheDocument();
        });

        expect(screen.getByText('記事ID:')).toBeInTheDocument();
        expect(screen.getByText('1')).toBeInTheDocument();
        expect(screen.getByText('登録日時:')).toBeInTheDocument();
        expect(screen.getByText('更新日時:')).toBeInTheDocument();
    });

    it('戻るボタンが機能する', async () => {
        mockApiService.getArticleDetail.mockResolvedValue(mockArticleResponse);

        renderWithProviders('1');

        await waitFor(() => {
            expect(screen.getByText('戻る')).toBeInTheDocument();
        });
    });

    it('記事一覧に戻るリンクが機能する', async () => {
        mockApiService.getArticleDetail.mockResolvedValue(mockArticleResponse);

        renderWithProviders('1');

        await waitFor(() => {
            const backLink = screen.getByText('記事一覧に戻る');
            expect(backLink.closest('a')).toHaveAttribute('href', '/articles');
        });
    });

    it('記事を読むボタンが外部リンクとして機能する', async () => {
        mockApiService.getArticleDetail.mockResolvedValue(mockArticleResponse);

        renderWithProviders('1');

        await waitFor(() => {
            const readLinks = screen.getAllByText('記事を読む');
            expect(readLinks[0].closest('a')).toHaveAttribute('href', 'https://example.com/article');
            expect(readLinks[0].closest('a')).toHaveAttribute('target', '_blank');
            expect(readLinks[0].closest('a')).toHaveAttribute('rel', 'noopener noreferrer');
        });
    });

    it('共有ボタンが存在する', async () => {
        mockApiService.getArticleDetail.mockResolvedValue(mockArticleResponse);

        renderWithProviders('1');

        await waitFor(() => {
            expect(screen.getByText('共有')).toBeInTheDocument();
        });
    });

    it('ローディング状態が適切に表示される', () => {
        mockApiService.getArticleDetail.mockImplementationOnce(
            () => new Promise(() => {}) // 永続的にpending状態
        );

        renderWithProviders('1');

        expect(screen.getByText('記事を読み込み中...')).toBeInTheDocument();
    });

    it('エラー状態が適切にハンドリングされる', async () => {
        mockApiService.getArticleDetail.mockRejectedValue(new Error('Network error'));

        renderWithProviders('1');

        await waitFor(() => {
            expect(screen.getByText('記事の読み込みに失敗しました')).toBeInTheDocument();
        }, { timeout: 3000 });

        expect(screen.getByText('戻る')).toBeInTheDocument();
        expect(screen.getByText('再読み込み')).toBeInTheDocument();
    });
});