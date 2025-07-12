import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { BrowserRouter } from 'react-router-dom';
import { vi } from 'vitest';
import CompanyList from '../CompanyList';
import { apiService } from '../../services/api';

// APIサービスをモック
vi.mock('../../services/api', () => ({
    apiService: {
        getCompanies: vi.fn(),
    },
}));

const mockApiService = apiService as {
    getCompanies: ReturnType<typeof vi.fn>;
};

// テストヘルパー
const renderWithProviders = (component: React.ReactElement) => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                retry: false,
            },
        },
    });

    return render(
        <QueryClientProvider client={queryClient}>
            <BrowserRouter>
                {component}
            </BrowserRouter>
        </QueryClientProvider>
    );
};

const mockCompaniesResponse = {
    data: [
        {
            id: 1,
            name: 'Test Company A',
            domain: 'test-a.com',
            description: 'テスト企業Aの説明',
            logo_url: null,
            website_url: 'https://test-a.com',
            is_active: true,
            created_at: '2024-01-01T00:00:00Z',
            updated_at: '2024-01-01T00:00:00Z',
        },
        {
            id: 2,
            name: 'Test Company B',
            domain: 'test-b.com',
            description: 'テスト企業Bの説明',
            logo_url: 'https://example.com/logo.png',
            website_url: 'https://test-b.com',
            is_active: true,
            created_at: '2024-01-02T00:00:00Z',
            updated_at: '2024-01-02T00:00:00Z',
        },
    ],
    meta: {
        current_page: 1,
        per_page: 20,
        total: 2,
        last_page: 1,
        filters: {
            search: undefined,
            domain: undefined,
            is_active: undefined,
            sort_by: 'name',
            sort_order: 'asc',
        },
    },
};

describe('CompanyList', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('企業一覧が正常に表示される', async () => {
        mockApiService.getCompanies.mockResolvedValue({
            data: mockCompaniesResponse,
        });

        renderWithProviders(<CompanyList />);

        // ローディング状態
        expect(screen.getByText('企業データを読み込み中...')).toBeInTheDocument();

        // データ読み込み完了後
        await waitFor(() => {
            expect(screen.getByText('Test Company A')).toBeInTheDocument();
        });

        expect(screen.getByText('Test Company B')).toBeInTheDocument();
        expect(screen.getByText('test-a.com')).toBeInTheDocument();
        expect(screen.getByText('test-b.com')).toBeInTheDocument();
    });

    it('検索機能が正常に動作する', async () => {
        mockApiService.getCompanies.mockResolvedValue({
            data: mockCompaniesResponse,
        });

        renderWithProviders(<CompanyList />);

        await waitFor(() => {
            expect(screen.getByText('Test Company A')).toBeInTheDocument();
        });

        // 検索ボックスに入力
        const searchInput = screen.getByPlaceholderText('企業名で検索...');
        fireEvent.change(searchInput, { target: { value: 'Company A' } });

        // APIが検索パラメータ付きで呼ばれることを確認
        await waitFor(() => {
            expect(mockApiService.getCompanies).toHaveBeenCalledWith(
                expect.objectContaining({
                    search: 'Company A',
                    page: 1,
                })
            );
        });
    });

    it('ページサイズ変更が正常に動作する', async () => {
        mockApiService.getCompanies.mockResolvedValue({
            data: mockCompaniesResponse,
        });

        renderWithProviders(<CompanyList />);

        await waitFor(() => {
            expect(screen.getByText('Test Company A')).toBeInTheDocument();
        });

        // ページサイズを変更
        const perPageSelect = screen.getByDisplayValue('20件');
        fireEvent.change(perPageSelect, { target: { value: '50' } });

        // APIが新しいページサイズで呼ばれることを確認
        await waitFor(() => {
            expect(mockApiService.getCompanies).toHaveBeenCalledWith(
                expect.objectContaining({
                    per_page: 50,
                    page: 1,
                })
            );
        });
    });

    it('ソート機能が正常に動作する', async () => {
        mockApiService.getCompanies.mockResolvedValue({
            data: mockCompaniesResponse,
        });

        renderWithProviders(<CompanyList />);

        await waitFor(() => {
            expect(screen.getByText('Test Company A')).toBeInTheDocument();
        });

        // 企業名カラムをクリックしてソート
        const nameHeader = screen.getByText('企業名');
        fireEvent.click(nameHeader);

        // APIがソートパラメータ付きで呼ばれることを確認
        await waitFor(() => {
            expect(mockApiService.getCompanies).toHaveBeenCalledWith(
                expect.objectContaining({
                    sort_by: 'name',
                    sort_order: 'desc', // 昇順から降順に変更
                    page: 1,
                })
            );
        });
    });

    it('エラー状態が適切に表示される', async () => {
        mockApiService.getCompanies.mockRejectedValue(new Error('API Error'));

        renderWithProviders(<CompanyList />);

        await waitFor(() => {
            expect(screen.getByText('企業一覧の読み込みに失敗しました')).toBeInTheDocument();
        });

        expect(screen.getByText('再読み込み')).toBeInTheDocument();
    });

    it('空の結果が適切に表示される', async () => {
        mockApiService.getCompanies.mockResolvedValue({
            data: {
                data: [],
                meta: {
                    current_page: 1,
                    per_page: 20,
                    total: 0,
                    last_page: 1,
                    filters: {
                        search: 'NonExistent',
                        domain: undefined,
                        is_active: undefined,
                        sort_by: 'name',
                        sort_order: 'asc',
                    },
                },
            },
        });

        renderWithProviders(<CompanyList />);

        await waitFor(() => {
            expect(screen.getByText('企業が見つかりませんでした')).toBeInTheDocument();
        });
    });

    it('ページネーションが正常に動作する', async () => {
        const multiPageResponse = {
            ...mockCompaniesResponse,
            meta: {
                ...mockCompaniesResponse.meta,
                current_page: 1,
                last_page: 3,
                total: 50,
            },
        };

        mockApiService.getCompanies.mockResolvedValue({
            data: multiPageResponse,
        });

        renderWithProviders(<CompanyList />);

        await waitFor(() => {
            expect(screen.getByText('Test Company A')).toBeInTheDocument();
        });

        // 次のページボタンをクリック
        const nextButton = screen.getByText('次へ');
        fireEvent.click(nextButton);

        // APIが次のページで呼ばれることを確認
        await waitFor(() => {
            expect(mockApiService.getCompanies).toHaveBeenCalledWith(
                expect.objectContaining({
                    page: 2,
                })
            );
        });
    });

    it('企業ロゴが適切に表示される', async () => {
        mockApiService.getCompanies.mockResolvedValue({
            data: mockCompaniesResponse,
        });

        renderWithProviders(<CompanyList />);

        await waitFor(() => {
            expect(screen.getByText('Test Company B')).toBeInTheDocument();
        });

        // ロゴ画像があるかチェック
        const logoImage = screen.getByAltText('Test Company B logo');
        expect(logoImage).toBeInTheDocument();
        expect(logoImage).toHaveAttribute('src', 'https://example.com/logo.png');
    });

    it('詳細ページへのリンクが正常に機能する', async () => {
        mockApiService.getCompanies.mockResolvedValue({
            data: mockCompaniesResponse,
        });

        renderWithProviders(<CompanyList />);

        await waitFor(() => {
            expect(screen.getByText('Test Company A')).toBeInTheDocument();
        });

        // 詳細リンクが存在することを確認
        const detailLinks = screen.getAllByText('詳細');
        expect(detailLinks).toHaveLength(2);
        
        // リンクのhref属性を確認
        expect(detailLinks[0].closest('a')).toHaveAttribute('href', '/companies/1');
        expect(detailLinks[1].closest('a')).toHaveAttribute('href', '/companies/2');
    });
});