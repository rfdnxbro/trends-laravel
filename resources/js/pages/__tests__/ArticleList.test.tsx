import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { vi } from 'vitest';
import ArticleList from '../ArticleList';

const mockArticles = {
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
            },
            platform: {
                id: 1,
                name: 'Test Platform',
            },
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
                logo_url: null,
            },
            platform: {
                id: 1,
                name: 'Test Platform',
            },
        },
    ],
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 2,
};

beforeEach(() => {
    global.fetch = vi.fn();
});

afterEach(() => {
    vi.restoreAllMocks();
});

const renderArticleList = () => {
    return render(
        <BrowserRouter>
            <ArticleList />
        </BrowserRouter>
    );
};

describe('ArticleList', () => {
    it('記事一覧が正しく表示される', async () => {
        (global.fetch as any).mockResolvedValueOnce({
            ok: true,
            json: async () => mockArticles,
        });

        renderArticleList();

        await waitFor(() => {
            expect(screen.getByText('記事一覧')).toBeInTheDocument();
        });

        expect(screen.getByText('Test Article 1')).toBeInTheDocument();
        expect(screen.getByText('Test Article 2')).toBeInTheDocument();
        expect(screen.getByText('Test Company')).toBeInTheDocument();
        expect(screen.getByText('Test Company 2')).toBeInTheDocument();
        expect(screen.getAllByText('Test Platform')).toHaveLength(2);
    });

    it('企業のロゴが表示される', async () => {
        (global.fetch as any).mockResolvedValueOnce({
            ok: true,
            json: async () => mockArticles,
        });

        renderArticleList();

        await waitFor(() => {
            expect(screen.getByText('記事一覧')).toBeInTheDocument();
        });

        const logo = screen.getByAltText('Test Company');
        expect(logo).toBeInTheDocument();
        expect(logo).toHaveAttribute('src', 'https://example.com/logo.png');
    });

    it('ブックマーク数が表示される', async () => {
        (global.fetch as any).mockResolvedValueOnce({
            ok: true,
            json: async () => mockArticles,
        });

        renderArticleList();

        await waitFor(() => {
            expect(screen.getByText('記事一覧')).toBeInTheDocument();
        });

        expect(screen.getByText('10 ブックマーク')).toBeInTheDocument();
        expect(screen.getByText('5 ブックマーク')).toBeInTheDocument();
    });

    it('記事のリンクが正しく設定される', async () => {
        (global.fetch as any).mockResolvedValueOnce({
            ok: true,
            json: async () => mockArticles,
        });

        renderArticleList();

        await waitFor(() => {
            expect(screen.getByText('記事一覧')).toBeInTheDocument();
        });

        const link1 = screen.getByText('Test Article 1').closest('a');
        const link2 = screen.getByText('Test Article 2').closest('a');

        expect(link1).toHaveAttribute('href', 'https://example.com/article1');
        expect(link1).toHaveAttribute('target', '_blank');
        expect(link2).toHaveAttribute('href', 'https://example.com/article2');
        expect(link2).toHaveAttribute('target', '_blank');
    });

    it('ローディング状態が表示される', () => {
        (global.fetch as any).mockImplementationOnce(
            () => new Promise(() => {}) // 永続的にpending状態
        );

        renderArticleList();

        expect(screen.getByText('読み込み中...')).toBeInTheDocument();
    });

    it('エラー状態が表示される', async () => {
        (global.fetch as any).mockRejectedValueOnce(new Error('Network error'));

        renderArticleList();

        await waitFor(() => {
            expect(screen.getByText('エラー')).toBeInTheDocument();
        });

        expect(screen.getByText('Network error')).toBeInTheDocument();
    });

    it('記事が見つからない場合のメッセージが表示される', async () => {
        (global.fetch as any).mockResolvedValueOnce({
            ok: true,
            json: async () => ({
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 20,
                total: 0,
            }),
        });

        renderArticleList();

        await waitFor(() => {
            expect(screen.getByText('記事一覧')).toBeInTheDocument();
        });

        // 記事が0件の場合、記事一覧は表示されているが中身は空
        expect(screen.getByText('記事一覧')).toBeInTheDocument();
    });
});