import React from 'react';
import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import { vi } from 'vitest';
import ArticleDetail from '../ArticleDetail';

// APIサービスをモック
vi.mock('../../services/api', () => ({
    apiService: {
        getArticleDetail: vi.fn(),
    },
}));

// React Queryクライアントの設定
const createTestQueryClient = () => new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

describe('ArticleDetail', () => {
    it('コンポーネントがレンダリングされる', () => {
        const queryClient = createTestQueryClient();
        
        render(
            <QueryClientProvider client={queryClient}>
                <MemoryRouter initialEntries={['/articles/1']}>
                    <Routes>
                        <Route path="/articles/:id" element={<ArticleDetail />} />
                    </Routes>
                </MemoryRouter>
            </QueryClientProvider>
        );

        // ローディング状態が表示されることを確認
        expect(screen.getByText('記事を読み込み中...')).toBeInTheDocument();
    });
});