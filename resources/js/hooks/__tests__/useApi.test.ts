import { describe, it, expect, vi, beforeEach } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { useApi, useCompanies, useCompany, useRankings } from '@/hooks/useApi';

// API のモック
vi.mock('@/services/api', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    }
}));

const createWrapper = () => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                retry: false,
            },
        },
    });

    return ({ children }: { children: React.ReactNode }) => (
        React.createElement(QueryClientProvider, { client: queryClient }, children)
    );
};

describe('useApi hooks', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('useApi', () => {
        it('API呼び出しが成功する', async () => {
            const mockData = { id: 1, name: 'Test Data' };
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockResolvedValue({ data: mockData });

            const { result } = renderHook(
                () => useApi(['test'], '/test'),
                { wrapper: createWrapper() }
            );

            expect(result.current.isLoading).toBe(true);

            await waitFor(() => {
                expect(result.current.isSuccess).toBe(true);
            });

            expect(result.current.data).toEqual(mockData);
            expect(mockApi.get).toHaveBeenCalledWith('/test');
        });

        it('API呼び出しでエラーが発生する', async () => {
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockRejectedValue(new Error('API Error'));

            const { result } = renderHook(
                () => useApi(['test'], '/test'),
                { wrapper: createWrapper() }
            );

            expect(result.current.isLoading).toBe(true);

            await waitFor(() => {
                expect(result.current.isError).toBe(true);
            });

            expect(result.current.error).toBeInstanceOf(Error);
            expect(result.current.data).toBeUndefined();
        });

        it('カスタムオプションが適用される', async () => {
            const mockData = { id: 1, name: 'Test Data' };
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockResolvedValue({ data: mockData });

            const customOptions = {
                enabled: false,
                staleTime: 5000,
            };

            const { result } = renderHook(
                () => useApi(['test'], '/test', customOptions),
                { wrapper: createWrapper() }
            );

            // enabled: false なので、初期状態でローディングされない
            expect(result.current.isLoading).toBe(false);
            expect(result.current.data).toBeUndefined();
            expect(mockApi.get).not.toHaveBeenCalled();
        });

        it('QueryKeyが正しく設定される', async () => {
            const mockData = { id: 1, name: 'Test Data' };
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockResolvedValue({ data: mockData });

            const queryKey = ['custom', 'test', 'key'];

            const { result } = renderHook(
                () => useApi(queryKey, '/test'),
                { wrapper: createWrapper() }
            );

            await waitFor(() => {
                expect(result.current.isSuccess).toBe(true);
            });

            expect(result.current.data).toEqual(mockData);
        });
    });

    describe('useCompanies', () => {
        it('デフォルトパラメータで企業一覧を取得する', async () => {
            const mockData = {
                data: [{ id: 1, name: 'Company 1' }],
                meta: { total: 1, page: 1 },
            };
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockResolvedValue({ data: mockData });

            const { result } = renderHook(
                () => useCompanies(),
                { wrapper: createWrapper() }
            );

            await waitFor(() => {
                expect(result.current.isSuccess).toBe(true);
            });

            expect(mockApi.get).toHaveBeenCalledWith('/companies?page=1&limit=10');
            expect(result.current.data).toEqual(mockData);
        });

        it('カスタムページネーションパラメータで企業一覧を取得する', async () => {
            const mockData = {
                data: [{ id: 1, name: 'Company 1' }],
                meta: { total: 1, page: 2 },
            };
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockResolvedValue({ data: mockData });

            const { result } = renderHook(
                () => useCompanies(2, 20),
                { wrapper: createWrapper() }
            );

            await waitFor(() => {
                expect(result.current.isSuccess).toBe(true);
            });

            expect(mockApi.get).toHaveBeenCalledWith('/companies?page=2&limit=20');
            expect(result.current.data).toEqual(mockData);
        });

        it('企業一覧取得でエラーが発生する', async () => {
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockRejectedValue(new Error('Companies API Error'));

            const { result } = renderHook(
                () => useCompanies(),
                { wrapper: createWrapper() }
            );

            await waitFor(() => {
                expect(result.current.isError).toBe(true);
            });

            expect(result.current.error).toBeInstanceOf(Error);
        });
    });

    describe('useCompany', () => {
        it('指定IDの企業を取得する', async () => {
            const mockData = {
                data: { id: 123, name: 'Test Company', description: 'Test Description' },
            };
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockResolvedValue({ data: mockData });

            const { result } = renderHook(
                () => useCompany(123),
                { wrapper: createWrapper() }
            );

            await waitFor(() => {
                expect(result.current.isSuccess).toBe(true);
            });

            expect(mockApi.get).toHaveBeenCalledWith('/companies/123');
            expect(result.current.data).toEqual(mockData);
        });

        it('企業取得でエラーが発生する', async () => {
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockRejectedValue(new Error('Company not found'));

            const { result } = renderHook(
                () => useCompany(999),
                { wrapper: createWrapper() }
            );

            await waitFor(() => {
                expect(result.current.isError).toBe(true);
            });

            expect(result.current.error).toBeInstanceOf(Error);
        });

        it('異なる企業IDで正しくキャッシュが分離される', async () => {
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockResolvedValueOnce({ data: { data: { id: 1, name: 'Company 1' } } });
            mockApi.get.mockResolvedValueOnce({ data: { data: { id: 2, name: 'Company 2' } } });

            const { result: result1 } = renderHook(
                () => useCompany(1),
                { wrapper: createWrapper() }
            );

            const { result: result2 } = renderHook(
                () => useCompany(2),
                { wrapper: createWrapper() }
            );

            await waitFor(() => {
                expect(result1.current.isSuccess).toBe(true);
                expect(result2.current.isSuccess).toBe(true);
            });

            expect(mockApi.get).toHaveBeenCalledWith('/companies/1');
            expect(mockApi.get).toHaveBeenCalledWith('/companies/2');
        });
    });

    describe('useRankings', () => {
        it('デフォルト期間でランキングを取得する', async () => {
            const mockData = {
                data: [{ id: 1, company_name: 'Top Company', rank: 1 }],
                meta: { total: 1 },
            };
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockResolvedValue({ data: mockData });

            const { result } = renderHook(
                () => useRankings(),
                { wrapper: createWrapper() }
            );

            await waitFor(() => {
                expect(result.current.isSuccess).toBe(true);
            });

            expect(mockApi.get).toHaveBeenCalledWith('/rankings?period=monthly');
            expect(result.current.data).toEqual(mockData);
        });

        it('カスタム期間でランキングを取得する', async () => {
            const mockData = {
                data: [{ id: 1, company_name: 'Top Company', rank: 1 }],
                meta: { total: 1 },
            };
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockResolvedValue({ data: mockData });

            const { result } = renderHook(
                () => useRankings('weekly'),
                { wrapper: createWrapper() }
            );

            await waitFor(() => {
                expect(result.current.isSuccess).toBe(true);
            });

            expect(mockApi.get).toHaveBeenCalledWith('/rankings?period=weekly');
            expect(result.current.data).toEqual(mockData);
        });

        it('ランキング取得でエラーが発生する', async () => {
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockRejectedValue(new Error('Rankings API Error'));

            const { result } = renderHook(
                () => useRankings('daily'),
                { wrapper: createWrapper() }
            );

            await waitFor(() => {
                expect(result.current.isError).toBe(true);
            });

            expect(result.current.error).toBeInstanceOf(Error);
        });

        it('異なる期間で正しくキャッシュが分離される', async () => {
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockResolvedValueOnce({ data: { data: [{ period: 'daily' }] } });
            mockApi.get.mockResolvedValueOnce({ data: { data: [{ period: 'weekly' }] } });

            const { result: result1 } = renderHook(
                () => useRankings('daily'),
                { wrapper: createWrapper() }
            );

            const { result: result2 } = renderHook(
                () => useRankings('weekly'),
                { wrapper: createWrapper() }
            );

            await waitFor(() => {
                expect(result1.current.isSuccess).toBe(true);
                expect(result2.current.isSuccess).toBe(true);
            });

            expect(mockApi.get).toHaveBeenCalledWith('/rankings?period=daily');
            expect(mockApi.get).toHaveBeenCalledWith('/rankings?period=weekly');
        });
    });

    describe('ローディング状態の管理', () => {
        it('初期状態では isLoading が true である', async () => {
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockImplementation(() => new Promise(() => {})); // 永続的なローディング

            const { result } = renderHook(
                () => useApi(['test'], '/test'),
                { wrapper: createWrapper() }
            );

            expect(result.current.isLoading).toBe(true);
            expect(result.current.isSuccess).toBe(false);
            expect(result.current.isError).toBe(false);
            expect(result.current.data).toBeUndefined();
        });

        it('成功時に正しい状態になる', async () => {
            const mockData = { success: true };
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockResolvedValue({ data: mockData });

            const { result } = renderHook(
                () => useApi(['test'], '/test'),
                { wrapper: createWrapper() }
            );

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
                expect(result.current.isSuccess).toBe(true);
                expect(result.current.isError).toBe(false);
                expect(result.current.data).toEqual(mockData);
            });
        });

        it('エラー時に正しい状態になる', async () => {
            const mockApi = (await vi.importMock('@/services/api')).default;
            mockApi.get.mockRejectedValue(new Error('Test Error'));

            const { result } = renderHook(
                () => useApi(['test'], '/test'),
                { wrapper: createWrapper() }
            );

            await waitFor(() => {
                expect(result.current.isLoading).toBe(false);
                expect(result.current.isSuccess).toBe(false);
                expect(result.current.isError).toBe(true);
                expect(result.current.error).toBeInstanceOf(Error);
                expect(result.current.data).toBeUndefined();
            });
        });
    });
});