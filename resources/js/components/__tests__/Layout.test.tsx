import React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import Layout from '../Layout';

vi.mock('../Header', () => ({
    default: () => <div data-testid="header">Header Component</div>,
}));

vi.mock('../Sidebar', () => ({
    default: () => <div data-testid="sidebar">Sidebar Component</div>,
}));

describe('Layout', () => {
    it('レイアウトが正しくレンダリングされる', () => {
        render(
            <Layout>
                <div data-testid="test-content">Test Content</div>
            </Layout>
        );

        expect(screen.getByTestId('header')).toBeInTheDocument();
        expect(screen.getByTestId('sidebar')).toBeInTheDocument();
        expect(screen.getByTestId('test-content')).toBeInTheDocument();
    });

    it('childrenが正しくmainタグ内に表示される', () => {
        render(
            <Layout>
                <div data-testid="child-content">Child Content</div>
            </Layout>
        );

        const main = screen.getByRole('main');
        expect(main).toBeInTheDocument();
        expect(main).toContainElement(screen.getByTestId('child-content'));
    });

    it('正しいCSS クラスが適用される', () => {
        const { container } = render(
            <Layout>
                <div>Content</div>
            </Layout>
        );

        const rootDiv = container.firstChild as HTMLElement;
        expect(rootDiv).toHaveClass('min-h-screen', 'bg-gray-100');

        const flexContainer = rootDiv.querySelector('.flex');
        expect(flexContainer).toBeInTheDocument();

        const main = screen.getByRole('main');
        expect(main).toHaveClass('flex-1', 'p-6');
    });

    it('レスポンシブ レイアウト構造が適用される', () => {
        render(
            <Layout>
                <div>Responsive Content</div>
            </Layout>
        );

        const main = screen.getByRole('main');
        expect(main.className).toContain('flex-1');
    });

    it('HeaderとSidebarが統合されている', () => {
        render(
            <Layout>
                <div>Content</div>
            </Layout>
        );

        expect(screen.getByTestId('header')).toBeInTheDocument();
        expect(screen.getByTestId('sidebar')).toBeInTheDocument();
    });

    it('複数の子要素が正しく表示される', () => {
        render(
            <Layout>
                <div data-testid="first-child">First Child</div>
                <div data-testid="second-child">Second Child</div>
                <p data-testid="third-child">Third Child</p>
            </Layout>
        );

        expect(screen.getByTestId('first-child')).toBeInTheDocument();
        expect(screen.getByTestId('second-child')).toBeInTheDocument();
        expect(screen.getByTestId('third-child')).toBeInTheDocument();
    });

    it('空のchildrenでもエラーが発生しない', () => {
        expect(() => {
            render(<Layout>{null}</Layout>);
        }).not.toThrow();
    });
});