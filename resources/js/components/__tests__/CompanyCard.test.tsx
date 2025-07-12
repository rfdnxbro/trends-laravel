import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { vi } from 'vitest';
import CompanyCard from '../CompanyCard';
import { Company } from '../../types';

// テストヘルパー
const renderWithRouter = (component: React.ReactElement) => {
    return render(
        <BrowserRouter>
            {component}
        </BrowserRouter>
    );
};

const mockCompany: Company = {
    id: 1,
    name: 'Test Company',
    domain: 'test.com',
    description: 'これはテスト企業の説明文です。',
    logo_url: 'https://example.com/logo.png',
    website_url: 'https://test.com',
    is_active: true,
    qiita_username: 'test_qiita',
    zenn_username: 'test_zenn',
    influence_score: 85.5,
    ranking: 5,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
};

describe('CompanyCard', () => {
    it('企業情報が正常に表示される', () => {
        renderWithRouter(<CompanyCard company={mockCompany} />);

        expect(screen.getByText('Test Company')).toBeInTheDocument();
        expect(screen.getAllByText('test.com')).toHaveLength(2); // ドメイン表示とウェブサイトリンクの2箇所
        expect(screen.getByText('これはテスト企業の説明文です。')).toBeInTheDocument();
    });

    it('企業ロゴが表示される', () => {
        renderWithRouter(<CompanyCard company={mockCompany} />);

        const logoImage = screen.getByAltText('Test Company logo');
        expect(logoImage).toBeInTheDocument();
        expect(logoImage).toHaveAttribute('src', 'https://example.com/logo.png');
    });

    it('ロゴがない場合はアイコンが表示される', () => {
        const companyWithoutLogo = { ...mockCompany, logo_url: null };
        
        renderWithRouter(<CompanyCard company={companyWithoutLogo} />);

        const buildingIcon = screen.getByTestId('building-icon');
        expect(buildingIcon).toBeInTheDocument();
    });

    it('アクティブ状態が正しく表示される', () => {
        renderWithRouter(<CompanyCard company={mockCompany} />);

        expect(screen.getByText('アクティブ')).toBeInTheDocument();
        expect(screen.getByText('アクティブ')).toHaveClass('bg-green-100', 'text-green-800');
    });

    it('非アクティブ状態が正しく表示される', () => {
        const inactiveCompany = { ...mockCompany, is_active: false };
        
        renderWithRouter(<CompanyCard company={inactiveCompany} />);

        expect(screen.getByText('非アクティブ')).toBeInTheDocument();
        expect(screen.getByText('非アクティブ')).toHaveClass('bg-gray-100', 'text-gray-800');
    });

    it('影響力スコアが表示される', () => {
        renderWithRouter(<CompanyCard company={mockCompany} />);

        expect(screen.getByText('影響力スコア')).toBeInTheDocument();
        expect(screen.getByText('85.50')).toBeInTheDocument();
    });

    it('ランキングが表示される', () => {
        renderWithRouter(<CompanyCard company={mockCompany} />);

        expect(screen.getByText('ランキング')).toBeInTheDocument();
        expect(screen.getByText('#5')).toBeInTheDocument();
    });

    it('ウェブサイトリンクが表示される', () => {
        renderWithRouter(<CompanyCard company={mockCompany} />);

        expect(screen.getByText('ウェブサイト')).toBeInTheDocument();
        
        const websiteLink = screen.getByRole('link', { name: 'test.com' });
        expect(websiteLink).toBeInTheDocument();
        expect(websiteLink).toHaveAttribute('href', 'https://test.com');
        expect(websiteLink).toHaveAttribute('target', '_blank');
    });

    it('SNSアカウントが表示される', () => {
        renderWithRouter(<CompanyCard company={mockCompany} />);

        expect(screen.getByText('SNS アカウント')).toBeInTheDocument();
        
        const qiitaLink = screen.getByText('Qiita');
        expect(qiitaLink).toBeInTheDocument();
        expect(qiitaLink).toHaveAttribute('href', 'https://qiita.com/test_qiita');
        
        const zennLink = screen.getByText('Zenn');
        expect(zennLink).toBeInTheDocument();
        expect(zennLink).toHaveAttribute('href', 'https://zenn.dev/test_zenn');
    });

    it('詳細ページへのリンクが正常に機能する', () => {
        renderWithRouter(<CompanyCard company={mockCompany} />);

        const detailLink = screen.getByText('詳細を見る');
        expect(detailLink).toBeInTheDocument();
        expect(detailLink.closest('a')).toHaveAttribute('href', '/companies/1');
    });

    it('クリックハンドラーが呼ばれる', () => {
        const mockOnClick = vi.fn();
        
        renderWithRouter(<CompanyCard company={mockCompany} onClick={mockOnClick} />);

        const card = screen.getByText('Test Company').closest('div');
        if (card) {
            fireEvent.click(card);
        }

        expect(mockOnClick).toHaveBeenCalledTimes(1);
    });

    it('showActionsがfalseの場合アクションが非表示になる', () => {
        renderWithRouter(<CompanyCard company={mockCompany} showActions={false} />);

        expect(screen.queryByText('詳細を見る')).not.toBeInTheDocument();
        expect(screen.queryByText(/登録:/)).not.toBeInTheDocument();
    });

    it('オプション項目がない場合は表示されない', () => {
        const minimalCompany: Company = {
            id: 1,
            name: 'Minimal Company',
            domain: 'minimal.com',
            created_at: '2024-01-01T00:00:00Z',
            updated_at: '2024-01-01T00:00:00Z',
        };

        renderWithRouter(<CompanyCard company={minimalCompany} />);

        expect(screen.getByText('Minimal Company')).toBeInTheDocument();
        expect(screen.queryByText('影響力スコア')).not.toBeInTheDocument();
        expect(screen.queryByText('ランキング')).not.toBeInTheDocument();
        expect(screen.queryByText('ウェブサイト')).not.toBeInTheDocument();
        expect(screen.queryByText('SNS アカウント')).not.toBeInTheDocument();
    });

    it('ロゴ読み込みエラー時にフォールバックが表示される', () => {
        renderWithRouter(<CompanyCard company={mockCompany} />);

        const logoImage = screen.getByAltText('Test Company logo');
        
        // ロゴ読み込みエラーをシミュレート
        fireEvent.error(logoImage);

        // フォールバックアイコンが表示される
        const buildingIcon = screen.getByTestId('building-icon');
        expect(buildingIcon).toBeInTheDocument();
    });

    it('外部リンククリック時にイベント伝播が停止される', () => {
        const mockOnClick = vi.fn();
        
        renderWithRouter(<CompanyCard company={mockCompany} onClick={mockOnClick} />);

        // ウェブサイトリンクをクリック
        const websiteLink = screen.getByRole('link', { name: 'test.com' });
        fireEvent.click(websiteLink);

        // カードのonClickが呼ばれないことを確認
        expect(mockOnClick).not.toHaveBeenCalled();
    });
});