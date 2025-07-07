import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import RankingCard from '../RankingCard';
import { CompanyRanking } from '../../types';

const mockRanking: CompanyRanking = {
    id: 1,
    company_id: 1,
    ranking_date: '2025-07-07',
    period: 'monthly',
    influence_score: 1250.5,
    rank: 1,
    company: {
        id: 1,
        name: 'テスト株式会社',
        description: 'テスト用の会社です',
        website: 'https://example.com',
        hatena_username: 'test_hatena',
        qiita_username: 'test_qiita',
        zenn_username: 'test_zenn',
        created_at: '2025-01-01T00:00:00.000Z',
        updated_at: '2025-07-07T00:00:00.000Z'
    },
    rank_change: 2
};

describe('RankingCard', () => {
    it('基本情報が正しく表示される', () => {
        render(<RankingCard ranking={mockRanking} />);
        
        expect(screen.getByText('#1')).toBeInTheDocument();
        expect(screen.getByText('テスト株式会社')).toBeInTheDocument();
        expect(screen.getByText('テスト用の会社です')).toBeInTheDocument();
        expect(screen.getByText('1,250.5')).toBeInTheDocument();
    });

    it('プラットフォームバッジが正しく表示される', () => {
        render(<RankingCard ranking={mockRanking} />);
        
        expect(screen.getByText('はてな')).toBeInTheDocument();
        expect(screen.getByText('Qiita')).toBeInTheDocument();
        expect(screen.getByText('Zenn')).toBeInTheDocument();
    });

    it('順位上昇時に正しいアイコンが表示される', () => {
        render(<RankingCard ranking={mockRanking} showRankChange={true} />);
        
        expect(screen.getByText('+2')).toBeInTheDocument();
    });

    it('順位下降時に正しいアイコンが表示される', () => {
        const rankingWithDecrease = {
            ...mockRanking,
            rank_change: -3
        };
        
        render(<RankingCard ranking={rankingWithDecrease} showRankChange={true} />);
        
        expect(screen.getByText('-3')).toBeInTheDocument();
    });

    it('順位変動なしの場合に正しいアイコンが表示される', () => {
        const rankingWithNoChange = {
            ...mockRanking,
            rank_change: 0
        };
        
        render(<RankingCard ranking={rankingWithNoChange} showRankChange={true} />);
        
        expect(screen.getByText('変動なし')).toBeInTheDocument();
    });

    it('順位変動を非表示にできる', () => {
        render(<RankingCard ranking={mockRanking} showRankChange={false} />);
        
        expect(screen.queryByText('+2')).not.toBeInTheDocument();
    });

    it('クリック時にコールバックが呼ばれる', () => {
        const handleClick = vi.fn();
        render(<RankingCard ranking={mockRanking} onClick={handleClick} />);
        
        fireEvent.click(screen.getByText('テスト株式会社'));
        expect(handleClick).toHaveBeenCalledTimes(1);
    });

    it('公式サイトリンクが正しく表示される', () => {
        render(<RankingCard ranking={mockRanking} />);
        
        const websiteLink = screen.getByText('公式サイト');
        expect(websiteLink).toBeInTheDocument();
        expect(websiteLink.closest('a')).toHaveAttribute('href', 'https://example.com');
        expect(websiteLink.closest('a')).toHaveAttribute('target', '_blank');
    });

    it('説明文がない場合は表示されない', () => {
        const rankingWithoutDescription = {
            ...mockRanking,
            company: {
                ...mockRanking.company,
                description: undefined
            }
        };
        
        render(<RankingCard ranking={rankingWithoutDescription} />);
        
        expect(screen.queryByText('テスト用の会社です')).not.toBeInTheDocument();
    });

    it('プラットフォームのユーザー名がない場合はバッジが表示されない', () => {
        const rankingWithoutPlatforms = {
            ...mockRanking,
            company: {
                ...mockRanking.company,
                hatena_username: undefined,
                qiita_username: undefined,
                zenn_username: undefined
            }
        };
        
        render(<RankingCard ranking={rankingWithoutPlatforms} />);
        
        expect(screen.queryByText('はてな')).not.toBeInTheDocument();
        expect(screen.queryByText('Qiita')).not.toBeInTheDocument();
        expect(screen.queryByText('Zenn')).not.toBeInTheDocument();
    });
});