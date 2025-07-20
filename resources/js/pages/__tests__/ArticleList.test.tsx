import React from 'react';
import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { BrowserRouter } from 'react-router-dom';
import { vi } from 'vitest';
import ArticleList from '../ArticleList';

// APIサービスをモック
vi.mock('../../services/api', () => ({
    apiService: {
        getArticles: vi.fn(),
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

describe('ArticleList', () => {
    it('コンポーネントがレンダリングされる', () => {
        const queryClient = createTestQueryClient();
        
        render(
            <QueryClientProvider client={queryClient}>
                <BrowserRouter>
                    <ArticleList />
                </BrowserRouter>
            </QueryClientProvider>
        );

        // タイトルが表示されることを確認
        expect(screen.getByText('記事一覧')).toBeInTheDocument();
        expect(screen.getByText('企業別の技術記事を確認できます')).toBeInTheDocument();
    });
});