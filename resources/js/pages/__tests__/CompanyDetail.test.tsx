import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { BrowserRouter, useParams } from 'react-router-dom';
import CompanyDetail from '@/pages/CompanyDetail';
import { api } from '@/services/api';

// React Router のパラメータをモック
vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        useParams: vi.fn(),
    };
});

// API のモック
vi.mock('@/services/api', () => ({
    default: {
        get: vi.fn(),
    },
}));

const mockCompanyData = {
    id: 1,
    name: 'テスト企業',
    description: 'テスト企業の説明です',
    website: 'https://example.com',
    influence_score: 85.5,
    ranking: 3,
    hatena_username: 'test_hatena',
    qiita_username: 'test_qiita',
    zenn_username: 'test_zenn',
    total_articles: 25,
    created_at: '2023-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
    ranking_history: [
        { date: '2024-01-01', rank: 3, influence_score: 85.5 },
        { date: '2024-01-02', rank: 5, influence_score: 80.0 },
    ],
    recent_articles: [
        {
            id: 1,
            title: 'テスト記事1',
            url: 'https://example.com/article1',
            platform: 'Qiita',
            published_at: '2024-01-01T00:00:00Z',
            bookmark_count: 10,
        },
        {
            id: 2,
            title: 'テスト記事2',
            url: 'https://example.com/article2',
            platform: 'Zenn',
            published_at: '2024-01-02T00:00:00Z',
            bookmark_count: 15,
        },
    ],
};

const TestWrapper: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                retry: false,
            },
        },
    });

    return (
        <QueryClientProvider client={queryClient}>
            <BrowserRouter>
                {children}
            </BrowserRouter>
        </QueryClientProvider>
    );
};

describe('CompanyDetail', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        
        // デフォルトで企業ID 1 を設定
        vi.mocked(useParams).mockReturnValue({ id: '1' });
    });

    it('ローディング状態が正しく表示される', async () => {
        const mockApi = (await vi.importMock('@/services/api')).default;
        mockApi.get.mockImplementation(() => new Promise(() => {})); // 永続的なローディング

        render(
            <TestWrapper>
                <CompanyDetail />
            </TestWrapper>
        );

        expect(screen.getByText('企業データを読み込み中...')).toBeInTheDocument();
        expect(screen.getByText('企業データを読み込み中...').closest('div')).toHaveClass('flex', 'items-center', 'justify-center', 'py-12');
    });

    it('企業データが正常に表示される', async () => {
        const mockApi = (await vi.importMock('@/services/api')).default;
        mockApi.get.mockResolvedValue({ data: mockCompanyData });

        render(
            <TestWrapper>
                <CompanyDetail />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('テスト企業')).toBeInTheDocument();
        });

        // 基本情報の確認
        expect(screen.getByText('テスト企業の説明です')).toBeInTheDocument();
        expect(screen.getByText('85.50')).toBeInTheDocument();
        expect(screen.getByText('#3')).toBeInTheDocument();
        expect(screen.getByText('ウェブサイト')).toBeInTheDocument();
    });

    it('エラー状態が正しく表示される', async () => {
        const mockApi = (await vi.importMock('@/services/api')).default;
        mockApi.get.mockRejectedValue(new Error('API Error'));

        render(
            <TestWrapper>
                <CompanyDetail />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('企業が見つかりません')).toBeInTheDocument();
        });

        expect(screen.getByText('指定された企業IDの情報を取得できませんでした。')).toBeInTheDocument();
        expect(screen.getByText('ダッシュボードに戻る')).toBeInTheDocument();
    });

    it('パンくずナビゲーションが正しく表示される', async () => {
        const mockApi = (await vi.importMock('@/services/api')).default;
        mockApi.get.mockResolvedValue({ data: mockCompanyData });

        render(
            <TestWrapper>
                <CompanyDetail />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByLabelText('Breadcrumb')).toBeInTheDocument();
        });

        expect(screen.getByText('ダッシュボード')).toBeInTheDocument();
        expect(screen.getByText('/')).toBeInTheDocument();
    });

    it('プラットフォーム連携情報が正しく表示される', async () => {
        const mockApi = (await vi.importMock('@/services/api')).default;
        mockApi.get.mockResolvedValue({ data: mockCompanyData });

        render(
            <TestWrapper>
                <CompanyDetail />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('プラットフォーム連携')).toBeInTheDocument();
        });

        expect(screen.getByText('はてなブログ')).toBeInTheDocument();
        expect(screen.getByText('@test_hatena')).toBeInTheDocument();
        expect(screen.getByText('Qiita')).toBeInTheDocument();
        expect(screen.getByText('@test_qiita')).toBeInTheDocument();
        expect(screen.getByText('Zenn')).toBeInTheDocument();
        expect(screen.getByText('@test_zenn')).toBeInTheDocument();
    });

    it('統計情報が正しく表示される', async () => {
        const mockApi = (await vi.importMock('@/services/api')).default;
        mockApi.get.mockResolvedValue({ data: mockCompanyData });

        render(
            <TestWrapper>
                <CompanyDetail />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('統計情報')).toBeInTheDocument();
        });

        expect(screen.getByText('総記事数')).toBeInTheDocument();
        expect(screen.getByText('25')).toBeInTheDocument();
        expect(screen.getByText('登録日')).toBeInTheDocument();
        expect(screen.getByText('最終更新')).toBeInTheDocument();
    });

    it('ランキング履歴が正しく表示される', async () => {
        const mockApi = (await vi.importMock('@/services/api')).default;
        mockApi.get.mockResolvedValue({ data: mockCompanyData });

        render(
            <TestWrapper>
                <CompanyDetail />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('ランキング推移')).toBeInTheDocument();
        });

        expect(screen.getByText('#3')).toBeInTheDocument();
        expect(screen.getByText('#5')).toBeInTheDocument();
    });

    it('最新記事が正しく表示される', async () => {
        const mockApi = (await vi.importMock('@/services/api')).default;
        mockApi.get.mockResolvedValue({ data: mockCompanyData });

        render(
            <TestWrapper>
                <CompanyDetail />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('最新記事')).toBeInTheDocument();
        });

        expect(screen.getByText('テスト記事1')).toBeInTheDocument();
        expect(screen.getByText('テスト記事2')).toBeInTheDocument();
        expect(screen.getByText('ブックマーク: 10')).toBeInTheDocument();
        expect(screen.getByText('ブックマーク: 15')).toBeInTheDocument();
    });

    it('プラットフォーム連携がない場合のメッセージが表示される', async () => {
        const companyWithoutPlatforms = {
            ...mockCompanyData,
            hatena_username: null,
            qiita_username: null,
            zenn_username: null,
        };

        const mockApi = (await vi.importMock('@/services/api')).default;
        mockApi.get.mockResolvedValue({ data: companyWithoutPlatforms });

        render(
            <TestWrapper>
                <CompanyDetail />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('プラットフォーム連携なし')).toBeInTheDocument();
        });
    });

    it('ランキング履歴がない場合のメッセージが表示される', async () => {
        const companyWithoutHistory = {
            ...mockCompanyData,
            ranking_history: [],
        };

        const mockApi = (await vi.importMock('@/services/api')).default;
        mockApi.get.mockResolvedValue({ data: companyWithoutHistory });

        render(
            <TestWrapper>
                <CompanyDetail />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('ランキング履歴なし')).toBeInTheDocument();
        });
    });

    it('不正な企業IDの場合にエラーが表示される', async () => {
        const { useParams } = require('react-router-dom');
        useParams.mockReturnValue({ id: 'invalid' });

        const mockApi = (await vi.importMock('@/services/api')).default;
        mockApi.get.mockRejectedValue(new Error('Invalid ID'));

        render(
            <TestWrapper>
                <CompanyDetail />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('企業が見つかりません')).toBeInTheDocument();
        });
    });

    it('企業IDがない場合にクエリが無効化される', async () => {
        const { useParams } = require('react-router-dom');
        useParams.mockReturnValue({ id: undefined });

        const mockApi = (await vi.importMock('@/services/api')).default;
        
        render(
            <TestWrapper>
                <CompanyDetail />
            </TestWrapper>
        );

        // APIが呼ばれないことを確認
        expect(mockApi.get).not.toHaveBeenCalled();
    });

    it('オプション情報がない場合に正しく表示される', async () => {
        const minimalCompany = {
            id: 1,
            name: 'ミニマル企業',
            created_at: '2023-01-01T00:00:00Z',
            updated_at: '2024-01-01T00:00:00Z',
            total_articles: 0,
        };

        const mockApi = (await vi.importMock('@/services/api')).default;
        mockApi.get.mockResolvedValue({ data: minimalCompany });

        render(
            <TestWrapper>
                <CompanyDetail />
            </TestWrapper>
        );

        await waitFor(() => {
            expect(screen.getByText('ミニマル企業')).toBeInTheDocument();
        });

        expect(screen.getByText('0')).toBeInTheDocument(); // 総記事数
        expect(screen.getByText('プラットフォーム連携なし')).toBeInTheDocument();
        expect(screen.getByText('ランキング履歴なし')).toBeInTheDocument();
        expect(screen.queryByText('最新記事')).not.toBeInTheDocument();
    });
});