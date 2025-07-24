import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { vi } from 'vitest';
import SearchForm from '../SearchForm';

// React Router のモック
const mockNavigate = vi.fn();
vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        useNavigate: () => mockNavigate,
    };
});

const renderWithRouter = (component: React.ReactElement) => {
    return render(
        <BrowserRouter>
            {component}
        </BrowserRouter>
    );
};

describe('SearchForm', () => {
    beforeEach(() => {
        mockNavigate.mockClear();
    });

    describe('基本レンダリング', () => {
        test('検索フォームが正しく表示される', () => {
            renderWithRouter(<SearchForm />);
            
            expect(screen.getByPlaceholderText('企業や記事を検索...')).toBeInTheDocument();
            expect(screen.getByRole('button', { name: '検索' })).toBeInTheDocument();
            expect(screen.getByDisplayValue('すべて')).toBeInTheDocument();
        });

        test('初期値が正しく設定される', () => {
            renderWithRouter(
                <SearchForm 
                    initialQuery="test query"
                    initialType="companies"
                />
            );
            
            expect(screen.getByDisplayValue('test query')).toBeInTheDocument();
            expect(screen.getByDisplayValue('企業')).toBeInTheDocument();
        });

        test('プレースホルダーをカスタマイズできる', () => {
            renderWithRouter(
                <SearchForm placeholder="カスタムプレースホルダー" />
            );
            
            expect(screen.getByPlaceholderText('カスタムプレースホルダー')).toBeInTheDocument();
        });

        test('検索タイプフィルターを非表示にできる', () => {
            renderWithRouter(<SearchForm showTypeFilter={false} />);
            
            expect(screen.queryByDisplayValue('すべて')).not.toBeInTheDocument();
        });
    });

    describe('フォーム操作', () => {
        test('検索クエリを入力できる', async () => {
            renderWithRouter(<SearchForm />);
            
            const input = screen.getByPlaceholderText('企業や記事を検索...');
            fireEvent.change(input, { target: { value: 'Laravel' } });
            
            expect(screen.getByDisplayValue('Laravel')).toBeInTheDocument();
        });

        test('検索タイプを変更できる', async () => {
            renderWithRouter(<SearchForm />);
            
            const select = screen.getByDisplayValue('すべて');
            fireEvent.change(select, { target: { value: 'companies' } });
            
            expect(screen.getByDisplayValue('企業')).toBeInTheDocument();
        });

        test('空の検索クエリでは検索ボタンが無効化される', () => {
            renderWithRouter(<SearchForm />);
            
            const button = screen.getByRole('button', { name: '検索' });
            expect(button).toBeDisabled();
        });

        test('検索クエリがある場合は検索ボタンが有効化される', () => {
            renderWithRouter(<SearchForm />);
            
            const input = screen.getByPlaceholderText('企業や記事を検索...');
            fireEvent.change(input, { target: { value: 'test' } });
            
            const button = screen.getByRole('button', { name: '検索' });
            expect(button).not.toBeDisabled();
        });
    });

    describe('検索実行', () => {
        test('onSearchコールバックが呼ばれる', async () => {
            const mockOnSearch = vi.fn();
            renderWithRouter(<SearchForm onSearch={mockOnSearch} />);
            
            const input = screen.getByPlaceholderText('企業や記事を検索...');
            const button = screen.getByRole('button', { name: '検索' });
            
            fireEvent.change(input, { target: { value: 'test query' } });
            fireEvent.click(button);
            
            expect(mockOnSearch).toHaveBeenCalledWith('test query', 'all');
        });

        test('Enterキーで検索実行される', async () => {
            const mockOnSearch = vi.fn();
            renderWithRouter(<SearchForm onSearch={mockOnSearch} />);
            
            const input = screen.getByPlaceholderText('企業や記事を検索...');
            
            fireEvent.change(input, { target: { value: 'test query' } });
            const form = input.closest('form');
            if (form) {
                fireEvent.submit(form);
            }
            
            expect(mockOnSearch).toHaveBeenCalledWith('test query', 'all');
        });

        test('検索タイプが正しく渡される', () => {
            const mockOnSearch = vi.fn();
            renderWithRouter(<SearchForm onSearch={mockOnSearch} />);
            
            const input = screen.getByPlaceholderText('企業や記事を検索...');
            const select = screen.getByDisplayValue('すべて');
            const button = screen.getByRole('button', { name: '検索' });
            
            fireEvent.change(input, { target: { value: 'test' } });
            fireEvent.change(select, { target: { value: 'articles' } });
            fireEvent.click(button);
            
            expect(mockOnSearch).toHaveBeenCalledWith('test', 'articles');
        });

        test('onSearchが未定義の場合はナビゲーションが実行される', () => {
            renderWithRouter(<SearchForm />);
            
            const input = screen.getByPlaceholderText('企業や記事を検索...');
            const button = screen.getByRole('button', { name: '検索' });
            
            fireEvent.change(input, { target: { value: 'test query' } });
            fireEvent.click(button);
            
            expect(mockNavigate).toHaveBeenCalledWith('/search?q=test+query');
        });

        test('検索タイプがallでない場合はURLパラメータに含まれる', () => {
            renderWithRouter(<SearchForm />);
            
            const input = screen.getByPlaceholderText('企業や記事を検索...');
            const select = screen.getByDisplayValue('すべて');
            const button = screen.getByRole('button', { name: '検索' });
            
            fireEvent.change(input, { target: { value: 'test' } });
            fireEvent.change(select, { target: { value: 'companies' } });
            fireEvent.click(button);
            
            expect(mockNavigate).toHaveBeenCalledWith('/search?q=test&type=companies');
        });

        test('空白のみのクエリでは検索が実行されない', () => {
            const mockOnSearch = vi.fn();
            renderWithRouter(<SearchForm onSearch={mockOnSearch} />);
            
            const input = screen.getByPlaceholderText('企業や記事を検索...');
            const button = screen.getByRole('button', { name: '検索' });
            
            fireEvent.change(input, { target: { value: '   ' } });
            fireEvent.click(button);
            
            expect(mockOnSearch).not.toHaveBeenCalled();
            expect(mockNavigate).not.toHaveBeenCalled();
        });
    });

    describe('スタイリング', () => {
        test('カスタムクラスを適用できる', () => {
            const { container } = renderWithRouter(
                <SearchForm className="custom-class" />
            );
            
            expect(container.querySelector('.custom-class')).toBeInTheDocument();
        });
    });
});