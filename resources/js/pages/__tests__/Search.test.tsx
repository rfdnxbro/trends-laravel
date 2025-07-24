import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { vi } from 'vitest';
import Search from '../Search';
import { apiService } from '../../services/api';

// APIサービスのモック
vi.mock('../../services/api', () => ({
    apiService: {
        searchCompanies: vi.fn(),
        searchArticles: vi.fn(),
        searchUnified: vi.fn(),
    }
}));

// React Router DOM のモック
const mockSearchParams = new URLSearchParams();
const mockSetSearchParams = vi.fn();

vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        useSearchParams: () => [mockSearchParams, mockSetSearchParams],
    };
});

const renderWithRouter = (component: React.ReactElement) => {
    return render(
        <BrowserRouter>
            {component}
        </BrowserRouter>
    );
};

const mockCompanyResponse = {
    data: {
        data: {
            companies: [
                {
                    id: 1,
                    name: 'テスト企業',
                    domain: 'test.com',
                    description: 'テスト企業の説明',
                    created_at: '2023-01-01T00:00:00.000000Z',
                    updated_at: '2023-01-01T00:00:00.000000Z',
                    match_score: 0.85
                }
            ]
        },
        meta: {
            total_results: 1,
            search_time: 0.123,
            query: 'test'
        }
    }
};

const mockArticleResponse = {
    data: {
        data: {
            articles: [
                {
                    id: 1,
                    title: 'テスト記事',
                    url: 'https://qiita.com/test/items/123',
                    author_name: 'テスト著者',
                    published_at: '2023-01-01T00:00:00.000000Z',
                    engagement_count: 100,
                    platform: { id: 1, name: 'Qiita' },
                    company: null,
                    created_at: '2023-01-01T00:00:00.000000Z',
                    updated_at: '2023-01-01T00:00:00.000000Z',
                    match_score: 0.92
                }
            ]
        },
        meta: {
            total_results: 1,
            search_time: 0.089,
            query: 'test',
            filters: {
                days: 30,
                min_engagement: 0
            }
        }
    }
};

const mockUnifiedResponse = {
    data: {
        data: {
            companies: mockCompanyResponse.data.data.companies,
            articles: mockArticleResponse.data.data.articles
        },
        meta: {
            total_results: 2,
            search_time: 0.156,
            query: 'test',
            type: 'all',
            filters: {
                days: 30,
                min_engagement: 0
            }
        }
    }
};

describe('Search', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockSearchParams.delete('q');
        mockSearchParams.delete('type');
        mockSearchParams.delete('limit');
        mockSearchParams.delete('days');
        mockSearchParams.delete('min_engagement');
    });

    describe('初期表示', () => {
        test('検索ページが正しく表示される', () => {
            renderWithRouter(<Search />);
            
            expect(screen.getByText('検索')).toBeInTheDocument();
            expect(screen.getByPlaceholderText('企業や記事を検索...')).toBeInTheDocument();
            expect(screen.getByText('検索キーワードを入力してください')).toBeInTheDocument();
        });

        test('クエリパラメータがない場合は初期状態が表示される', () => {
            renderWithRouter(<Search />);
            
            expect(screen.getByText('企業名、記事タイトル、著者名などで検索できます。')).toBeInTheDocument();
        });
    });

    describe('検索機能', () => {
        test('企業検索が実行される', async () => {
            const mockSearchCompanies = vi.mocked(apiService.searchCompanies);
            mockSearchCompanies.mockResolvedValue(mockCompanyResponse);
            
            // クエリパラメータを設定
            mockSearchParams.set('q', 'test');
            mockSearchParams.set('type', 'companies');
            
            renderWithRouter(<Search />);
            
            await waitFor(() => {
                expect(mockSearchCompanies).toHaveBeenCalledWith({
                    q: 'test',
                    limit: 20
                });
            });
            
            expect(screen.getByText('「test」の検索結果 1件')).toBeInTheDocument();
            expect(screen.getByText('テスト企業')).toBeInTheDocument();
        });

        test('記事検索が実行される', async () => {
            const mockSearchArticles = vi.mocked(apiService.searchArticles);
            mockSearchArticles.mockResolvedValue(mockArticleResponse);
            
            mockSearchParams.set('q', 'test');
            mockSearchParams.set('type', 'articles');
            
            renderWithRouter(<Search />);
            
            await waitFor(() => {
                expect(mockSearchArticles).toHaveBeenCalledWith({
                    q: 'test',
                    limit: 20,
                    days: 30,
                    min_engagement: 0
                });
            });
            
            expect(screen.getByText('「test」の検索結果 1件')).toBeInTheDocument();
            expect(screen.getByText('テスト記事')).toBeInTheDocument();
        });

        test('統合検索が実行される', async () => {
            const mockSearchUnified = vi.mocked(apiService.searchUnified);
            mockSearchUnified.mockResolvedValue(mockUnifiedResponse);
            
            mockSearchParams.set('q', 'test');
            mockSearchParams.set('type', 'all');
            
            renderWithRouter(<Search />);
            
            await waitFor(() => {
                expect(mockSearchUnified).toHaveBeenCalledWith({
                    q: 'test',
                    type: 'all',
                    limit: 20,
                    days: 30,
                    min_engagement: 0
                });
            });
            
            expect(screen.getByText('「test」の検索結果 2件')).toBeInTheDocument();
            expect(screen.getByText('テスト企業')).toBeInTheDocument();
            expect(screen.getByText('テスト記事')).toBeInTheDocument();
        });
    });

    describe('フィルター機能', () => {
        test('検索期間フィルターが動作する', async () => {
            const mockSearchUnified = vi.mocked(apiService.searchUnified);
            mockSearchUnified.mockResolvedValue(mockUnifiedResponse);
            
            mockSearchParams.set('q', 'test');
            
            renderWithRouter(<Search />);
            
            await waitFor(() => {
                expect(screen.getByDisplayValue('過去1ヶ月')).toBeInTheDocument();
            });
            
            const daysSelect = screen.getByDisplayValue('過去1ヶ月');
            
            await act(async () => {
                fireEvent.change(daysSelect, { target: { value: '7' } });
            });
            
            expect(mockSetSearchParams).toHaveBeenCalled();
        });

        test('最小エンゲージメント数フィルターが動作する', async () => {
            const mockSearchUnified = vi.mocked(apiService.searchUnified);
            mockSearchUnified.mockResolvedValue(mockUnifiedResponse);
            
            mockSearchParams.set('q', 'test');
            
            renderWithRouter(<Search />);
            
            await waitFor(() => {
                expect(screen.getByDisplayValue('制限なし')).toBeInTheDocument();
            });
            
            const engagementSelect = screen.getByDisplayValue('制限なし');
            
            await act(async () => {
                fireEvent.change(engagementSelect, { target: { value: '10' } });
            });
            
            expect(mockSetSearchParams).toHaveBeenCalled();
        });

        test('表示件数フィルターが動作する', async () => {
            const mockSearchUnified = vi.mocked(apiService.searchUnified);
            mockSearchUnified.mockResolvedValue(mockUnifiedResponse);
            
            mockSearchParams.set('q', 'test');
            
            renderWithRouter(<Search />);
            
            await waitFor(() => {
                expect(screen.getByDisplayValue('20件')).toBeInTheDocument();
            });
            
            const limitSelect = screen.getByDisplayValue('20件');
            
            await act(async () => {
                fireEvent.change(limitSelect, { target: { value: '50' } });
            });
            
            expect(mockSetSearchParams).toHaveBeenCalled();
        });

        test('企業検索時は詳細フィルターが表示されない', async () => {
            const mockSearchCompanies = vi.mocked(apiService.searchCompanies);
            mockSearchCompanies.mockResolvedValue(mockCompanyResponse);
            
            mockSearchParams.set('q', 'test');
            mockSearchParams.set('type', 'companies');
            
            renderWithRouter(<Search />);
            
            await waitFor(() => {
                expect(mockSearchCompanies).toHaveBeenCalled();
            });
            
            expect(screen.queryByText('詳細フィルター')).not.toBeInTheDocument();
        });

        test('記事検索時は詳細フィルターが表示される', async () => {
            const mockSearchArticles = vi.mocked(apiService.searchArticles);
            mockSearchArticles.mockResolvedValue(mockArticleResponse);
            
            mockSearchParams.set('q', 'test');
            mockSearchParams.set('type', 'articles');
            
            renderWithRouter(<Search />);
            
            await waitFor(() => {
                expect(mockSearchArticles).toHaveBeenCalled();
            });
            
            expect(screen.getByText('詳細フィルター')).toBeInTheDocument();
        });
    });

    describe('エラーハンドリング', () => {
        test('検索エラー時にエラーメッセージが表示される', async () => {
            const mockSearchUnified = vi.mocked(apiService.searchUnified);
            mockSearchUnified.mockRejectedValue(new Error('API Error'));
            
            mockSearchParams.set('q', 'test');
            
            renderWithRouter(<Search />);
            
            await waitFor(() => {
                expect(screen.getByText('検索中にエラーが発生しました。もう一度お試しください。')).toBeInTheDocument();
            });
        });
    });

    describe('ローディング状態', () => {
        test('検索中はローディングが表示される', async () => {
            const mockSearchUnified = vi.mocked(apiService.searchUnified);
            // Promiseを解決しないでローディング状態を維持
            mockSearchUnified.mockImplementation(() => new Promise(() => {}));
            
            mockSearchParams.set('q', 'test');
            
            renderWithRouter(<Search />);
            
            await waitFor(() => {
                expect(screen.getByText('検索中...')).toBeInTheDocument();
            });
        });
    });

    describe('検索フォームの統合', () => {
        test('検索フォームから検索が実行される', () => {
            renderWithRouter(<Search />);
            
            const input = screen.getByPlaceholderText('企業や記事を検索...');
            const button = screen.getByRole('button', { name: '検索' });
            
            fireEvent.change(input, { target: { value: 'new search' } });
            fireEvent.click(button);
            
            expect(mockSetSearchParams).toHaveBeenCalled();
        });
    });

    describe('パラメータ処理', () => {
        test('カスタムパラメータが正しく処理される', async () => {
            const mockSearchArticles = vi.mocked(apiService.searchArticles);
            mockSearchArticles.mockResolvedValue(mockArticleResponse);
            
            mockSearchParams.set('q', 'test');
            mockSearchParams.set('type', 'articles');
            mockSearchParams.set('limit', '50');
            mockSearchParams.set('days', '7');
            mockSearchParams.set('min_engagement', '10');
            
            renderWithRouter(<Search />);
            
            await waitFor(() => {
                expect(mockSearchArticles).toHaveBeenCalledWith({
                    q: 'test',
                    limit: 50,
                    days: 7,
                    min_engagement: 10
                });
            });
        });
    });
});