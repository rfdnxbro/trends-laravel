import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import RankingTable from '../RankingTable';
import { CompanyRanking, RankingFilters } from '../../types';

const mockRankings: CompanyRanking[] = [
    {
        id: 1,
        company_id: 1,
        ranking_date: '2025-07-07',
        period: 'monthly',
        influence_score: 1250.5,
        rank: 1,
        company: {
            id: 1,
            name: 'テスト株式会社A',
            description: 'テスト用の会社Aです',
            website: 'https://example-a.com',
            hatena_username: 'test_hatena_a',
            qiita_username: 'test_qiita_a',
            zenn_username: 'test_zenn_a',
            created_at: '2025-01-01T00:00:00.000Z',
            updated_at: '2025-07-07T00:00:00.000Z'
        },
        rank_change: 2
    },
    {
        id: 2,
        company_id: 2,
        ranking_date: '2025-07-07',
        period: 'monthly',
        influence_score: 1100.0,
        rank: 2,
        company: {
            id: 2,
            name: 'テスト株式会社B',
            description: 'テスト用の会社Bです',
            website: 'https://example-b.com',
            hatena_username: 'test_hatena_b',
            qiita_username: 'test_qiita_b',
            zenn_username: 'test_zenn_b',
            created_at: '2025-01-01T00:00:00.000Z',
            updated_at: '2025-07-07T00:00:00.000Z'
        },
        rank_change: -1
    }
];

const defaultFilters: RankingFilters = {
    period: 'monthly',
    sortBy: 'rank',
    sortOrder: 'asc'
};

describe('RankingTable', () => {
    it('基本レイアウトが正しく表示される', () => {
        const handleFiltersChange = vi.fn();
        
        render(
            <RankingTable
                rankings={mockRankings}
                filters={defaultFilters}
                onFiltersChange={handleFiltersChange}
            />
        );
        
        expect(screen.getByText('企業影響力ランキング')).toBeInTheDocument();
        expect(screen.getByPlaceholderText('企業を検索...')).toBeInTheDocument();
        expect(screen.getByText('フィルター')).toBeInTheDocument();
    });

    it('期間タブが正しく表示される', () => {
        const handleFiltersChange = vi.fn();
        
        render(
            <RankingTable
                rankings={mockRankings}
                filters={defaultFilters}
                onFiltersChange={handleFiltersChange}
            />
        );
        
        expect(screen.getByText('日次')).toBeInTheDocument();
        expect(screen.getByText('週次')).toBeInTheDocument();
        expect(screen.getByText('月次')).toBeInTheDocument();
        expect(screen.getByText('四半期')).toBeInTheDocument();
        expect(screen.getByText('半年')).toBeInTheDocument();
        expect(screen.getByText('年次')).toBeInTheDocument();
        expect(screen.getByText('全期間')).toBeInTheDocument();
    });

    it('ランキングカードが表示される', () => {
        const handleFiltersChange = vi.fn();
        
        render(
            <RankingTable
                rankings={mockRankings}
                filters={defaultFilters}
                onFiltersChange={handleFiltersChange}
            />
        );
        
        expect(screen.getByText('テスト株式会社A')).toBeInTheDocument();
        expect(screen.getByText('テスト株式会社B')).toBeInTheDocument();
    });

    it('期間タブクリック時にフィルターが変更される', () => {
        const handleFiltersChange = vi.fn();
        
        render(
            <RankingTable
                rankings={mockRankings}
                filters={defaultFilters}
                onFiltersChange={handleFiltersChange}
            />
        );
        
        fireEvent.click(screen.getByText('週次'));
        
        expect(handleFiltersChange).toHaveBeenCalledWith({
            ...defaultFilters,
            period: 'weekly'
        });
    });

    it('検索フォームの動作が正しい', async () => {
        const handleFiltersChange = vi.fn();
        
        render(
            <RankingTable
                rankings={mockRankings}
                filters={defaultFilters}
                onFiltersChange={handleFiltersChange}
            />
        );
        
        const searchInput = screen.getByPlaceholderText('企業を検索...');
        const searchButton = screen.getByText('検索');
        
        fireEvent.change(searchInput, { target: { value: 'テスト検索' } });
        fireEvent.click(searchButton);
        
        expect(handleFiltersChange).toHaveBeenCalledWith({
            ...defaultFilters,
            searchQuery: 'テスト検索'
        });
    });

    it('フィルターパネルが開閉される', () => {
        const handleFiltersChange = vi.fn();
        
        render(
            <RankingTable
                rankings={mockRankings}
                filters={defaultFilters}
                onFiltersChange={handleFiltersChange}
            />
        );
        
        const filterButton = screen.getByText('フィルター');
        
        // 初期状態ではフィルターパネルは表示されていない
        expect(screen.queryByText('並び順:')).not.toBeInTheDocument();
        
        // フィルターボタンをクリック
        fireEvent.click(filterButton);
        
        // フィルターパネルが表示される
        expect(screen.getByText('並び順:')).toBeInTheDocument();
        expect(screen.getByText('順位')).toBeInTheDocument();
        expect(screen.getByText('スコア')).toBeInTheDocument();
        expect(screen.getByText('企業名')).toBeInTheDocument();
        expect(screen.getByText('順位変動')).toBeInTheDocument();
    });

    it('ソートオプションクリック時にフィルターが変更される', () => {
        const handleFiltersChange = vi.fn();
        
        render(
            <RankingTable
                rankings={mockRankings}
                filters={defaultFilters}
                onFiltersChange={handleFiltersChange}
            />
        );
        
        // フィルターパネルを開く
        fireEvent.click(screen.getByText('フィルター'));
        
        // スコアでソートをクリック
        fireEvent.click(screen.getByText('スコア'));
        
        expect(handleFiltersChange).toHaveBeenCalledWith({
            ...defaultFilters,
            sortBy: 'influence_score',
            sortOrder: 'asc'
        });
    });

    it('同じソートオプションを再度クリックすると順序が逆になる', () => {
        const handleFiltersChange = vi.fn();
        const filtersWithRankSort = { ...defaultFilters, sortBy: 'rank', sortOrder: 'asc' as const };
        
        render(
            <RankingTable
                rankings={mockRankings}
                filters={filtersWithRankSort}
                onFiltersChange={handleFiltersChange}
            />
        );
        
        // フィルターパネルを開く
        fireEvent.click(screen.getByText('フィルター'));
        
        // 現在選択されている「順位」を再度クリック
        fireEvent.click(screen.getByText('順位'));
        
        expect(handleFiltersChange).toHaveBeenCalledWith({
            ...filtersWithRankSort,
            sortBy: 'rank',
            sortOrder: 'desc'
        });
    });

    it('ローディング状態が正しく表示される', () => {
        const handleFiltersChange = vi.fn();
        
        render(
            <RankingTable
                rankings={[]}
                loading={true}
                filters={defaultFilters}
                onFiltersChange={handleFiltersChange}
            />
        );
        
        // ローディング中はスケルトンが表示される
        expect(document.querySelector('.animate-pulse')).toBeInTheDocument();
    });

    it('ランキングが空の場合の表示が正しい', () => {
        const handleFiltersChange = vi.fn();
        
        render(
            <RankingTable
                rankings={[]}
                loading={false}
                filters={defaultFilters}
                onFiltersChange={handleFiltersChange}
            />
        );
        
        expect(screen.getByText('ランキングデータが見つかりません')).toBeInTheDocument();
        expect(screen.getByText('条件を変更して再度お試しください。')).toBeInTheDocument();
    });

    it('企業クリック時にコールバックが呼ばれる', () => {
        const handleFiltersChange = vi.fn();
        const handleCompanyClick = vi.fn();
        
        render(
            <RankingTable
                rankings={mockRankings}
                filters={defaultFilters}
                onFiltersChange={handleFiltersChange}
                onCompanyClick={handleCompanyClick}
            />
        );
        
        fireEvent.click(screen.getByText('テスト株式会社A'));
        
        expect(handleCompanyClick).toHaveBeenCalledWith(mockRankings[0].company);
    });

    it('件数表示が正しい', () => {
        const handleFiltersChange = vi.fn();
        
        render(
            <RankingTable
                rankings={mockRankings}
                filters={defaultFilters}
                onFiltersChange={handleFiltersChange}
            />
        );
        
        expect(screen.getByText('2件の企業を表示中')).toBeInTheDocument();
    });
});