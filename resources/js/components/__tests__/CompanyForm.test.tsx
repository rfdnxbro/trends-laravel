import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import CompanyForm from '../CompanyForm';
import { Company } from '../../types';

describe('CompanyForm', () => {
    const mockOnSubmit = vi.fn();
    const mockOnCancel = vi.fn();

    beforeEach(() => {
        mockOnSubmit.mockClear();
        mockOnCancel.mockClear();
    });

    it('新規作成フォームが正しく表示される', () => {
        render(
            <CompanyForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        expect(screen.getByLabelText(/企業名/)).toBeInTheDocument();
        expect(screen.getByLabelText('ドメイン *')).toBeInTheDocument();
        expect(screen.getByLabelText('説明')).toBeInTheDocument();
        expect(screen.getByLabelText('ロゴURL')).toBeInTheDocument();
        expect(screen.getByLabelText('ウェブサイトURL')).toBeInTheDocument();
        expect(screen.getByLabelText('Qiitaユーザー名')).toBeInTheDocument();
        expect(screen.getByLabelText('Zennユーザー名')).toBeInTheDocument();
        expect(screen.getByLabelText('キーワード（カンマ区切り）')).toBeInTheDocument();
        expect(screen.getByLabelText('URLパターン（カンマ区切り）')).toBeInTheDocument();
        expect(screen.getByLabelText('ドメインパターン（カンマ区切り）')).toBeInTheDocument();
        expect(screen.getByLabelText('Zenn組織（カンマ区切り）')).toBeInTheDocument();
        expect(screen.getByLabelText('アクティブ')).toBeInTheDocument();
        expect(screen.getByText('作成')).toBeInTheDocument();
    });

    it('編集フォームが正しく表示される', async () => {
        const mockCompany: Company = {
            id: 1,
            name: 'テスト企業',
            domain: 'test.com',
            description: 'テスト企業の説明',
            logo_url: 'https://example.com/logo.png',
            website_url: 'https://test.com',
            is_active: true,
            url_patterns: ['tech.test.com'],
            domain_patterns: ['*.test.com'],
            keywords: ['tech', 'ai'],
            zenn_organizations: ['test-org'],
            qiita_username: 'test_user',
            zenn_username: 'test_zenn',
            created_at: '2023-01-01T00:00:00.000000Z',
            updated_at: '2023-01-01T00:00:00.000000Z',
        };

        render(
            <CompanyForm
                company={mockCompany}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        // React Hook Formの初期化を待つ
        await waitFor(() => {
            expect(screen.getByDisplayValue('テスト企業')).toBeInTheDocument();
        });

        expect(screen.getByDisplayValue('test.com')).toBeInTheDocument();
        expect(screen.getByDisplayValue('テスト企業の説明')).toBeInTheDocument();
        expect(screen.getByDisplayValue('https://example.com/logo.png')).toBeInTheDocument();
        expect(screen.getByDisplayValue('https://test.com')).toBeInTheDocument();
        expect(screen.getByDisplayValue('test_user')).toBeInTheDocument();
        expect(screen.getByDisplayValue('test_zenn')).toBeInTheDocument();
        expect(screen.getByDisplayValue('tech, ai')).toBeInTheDocument();
        expect(screen.getByDisplayValue('tech.test.com')).toBeInTheDocument();
        expect(screen.getByDisplayValue('*.test.com')).toBeInTheDocument();
        expect(screen.getByDisplayValue('test-org')).toBeInTheDocument();
        expect(screen.getByText('更新')).toBeInTheDocument();
    });

    it('必須項目が空の場合にバリデーションエラーが表示される', async () => {
        render(
            <CompanyForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const submitButton = screen.getByText('作成');
        fireEvent.click(submitButton);

        await waitFor(() => {
            expect(screen.getByText('企業名は必須です')).toBeInTheDocument();
            expect(screen.getByText('ドメインは必須です')).toBeInTheDocument();
        });

        expect(mockOnSubmit).not.toHaveBeenCalled();
    });

    it('無効なURLの場合にバリデーションエラーが表示される', async () => {
        render(
            <CompanyForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const nameInput = screen.getByLabelText('企業名 *');
        const domainInput = screen.getByLabelText('ドメイン *');
        const logoUrlInput = screen.getByLabelText('ロゴURL');
        const websiteUrlInput = screen.getByLabelText('ウェブサイトURL');
        const submitButton = screen.getByText('作成');

        fireEvent.change(nameInput, { target: { value: 'テスト企業' } });
        fireEvent.change(domainInput, { target: { value: 'test.com' } });
        fireEvent.change(logoUrlInput, { target: { value: 'invalid-url' } });
        fireEvent.change(websiteUrlInput, { target: { value: 'invalid-url' } });

        fireEvent.click(submitButton);

        await waitFor(() => {
            expect(screen.getAllByText('有効なURLを入力してください')).toHaveLength(2);
        }, { timeout: 3000 });

        expect(mockOnSubmit).not.toHaveBeenCalled();
    });

    it('有効なフォームデータが送信される', async () => {
        render(
            <CompanyForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const nameInput = screen.getByLabelText('企業名 *');
        const domainInput = screen.getByLabelText('ドメイン *');
        const descriptionInput = screen.getByLabelText('説明');
        const logoUrlInput = screen.getByLabelText('ロゴURL');
        const websiteUrlInput = screen.getByLabelText('ウェブサイトURL');
        const qiitaUsernameInput = screen.getByLabelText('Qiitaユーザー名');
        const zennUsernameInput = screen.getByLabelText('Zennユーザー名');
        const keywordsInput = screen.getByLabelText('キーワード（カンマ区切り）');
        const urlPatternsInput = screen.getByLabelText('URLパターン（カンマ区切り）');
        const domainPatternsInput = screen.getByLabelText('ドメインパターン（カンマ区切り）');
        const zennOrganizationsInput = screen.getByLabelText('Zenn組織（カンマ区切り）');
        const isActiveCheckbox = screen.getByLabelText('アクティブ');
        const submitButton = screen.getByText('作成');

        fireEvent.change(nameInput, { target: { value: 'テスト企業' } });
        fireEvent.change(domainInput, { target: { value: 'test.com' } });
        fireEvent.change(descriptionInput, { target: { value: 'テスト企業の説明' } });
        fireEvent.change(logoUrlInput, { target: { value: 'https://example.com/logo.png' } });
        fireEvent.change(websiteUrlInput, { target: { value: 'https://test.com' } });
        fireEvent.change(qiitaUsernameInput, { target: { value: 'test_user' } });
        fireEvent.change(zennUsernameInput, { target: { value: 'test_zenn' } });
        fireEvent.change(keywordsInput, { target: { value: 'tech, ai' } });
        fireEvent.change(urlPatternsInput, { target: { value: 'tech.test.com, blog.test.com' } });
        fireEvent.change(domainPatternsInput, { target: { value: '*.test.com' } });
        fireEvent.change(zennOrganizationsInput, { target: { value: 'test-org' } });

        // is_activeはデフォルトでtrueなので、falseにするためにクリック
        fireEvent.click(isActiveCheckbox);

        fireEvent.click(submitButton);

        await waitFor(() => {
            expect(mockOnSubmit).toHaveBeenCalledWith({
                name: 'テスト企業',
                domain: 'test.com',
                description: 'テスト企業の説明',
                logo_url: 'https://example.com/logo.png',
                website_url: 'https://test.com',
                is_active: false, // チェックボックスをクリックしたため反転
                url_patterns: ['tech.test.com', 'blog.test.com'],
                domain_patterns: ['*.test.com'],
                keywords: ['tech', 'ai'],
                zenn_organizations: ['test-org'],
                qiita_username: 'test_user',
                zenn_username: 'test_zenn',
            });
        });
    });

    it('キャンセルボタンが動作する', () => {
        render(
            <CompanyForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const cancelButton = screen.getByText('キャンセル');
        fireEvent.click(cancelButton);

        expect(mockOnCancel).toHaveBeenCalled();
    });

    it('ローディング状態が表示される', () => {
        render(
            <CompanyForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
                isLoading={true}
            />
        );

        expect(screen.getByText('保存中...')).toBeInTheDocument();
        expect(screen.getByText('保存中...')).toBeDisabled();
    });

    it('空のURL入力は有効である', async () => {
        render(
            <CompanyForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const nameInput = screen.getByLabelText('企業名 *');
        const domainInput = screen.getByLabelText('ドメイン *');
        const logoUrlInput = screen.getByLabelText('ロゴURL');
        const websiteUrlInput = screen.getByLabelText('ウェブサイトURL');
        const submitButton = screen.getByText('作成');

        fireEvent.change(nameInput, { target: { value: 'テスト企業' } });
        fireEvent.change(domainInput, { target: { value: 'test.com' } });
        fireEvent.change(logoUrlInput, { target: { value: '' } });
        fireEvent.change(websiteUrlInput, { target: { value: '' } });

        fireEvent.click(submitButton);

        await waitFor(() => {
            expect(mockOnSubmit).toHaveBeenCalledWith({
                name: 'テスト企業',
                domain: 'test.com',
                description: '',
                logo_url: '',
                website_url: '',
                is_active: true,
                url_patterns: [],
                domain_patterns: [],
                keywords: [],
                zenn_organizations: [],
                qiita_username: '',
                zenn_username: '',
            });
        });
    });

    it('配列フィールドが正しく処理される', async () => {
        render(
            <CompanyForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const nameInput = screen.getByLabelText('企業名 *');
        const domainInput = screen.getByLabelText('ドメイン *');
        const keywordsInput = screen.getByLabelText('キーワード（カンマ区切り）');
        const submitButton = screen.getByText('作成');

        fireEvent.change(nameInput, { target: { value: 'テスト企業' } });
        fireEvent.change(domainInput, { target: { value: 'test.com' } });
        
        // 前後にスペースがある文字列をテスト
        fireEvent.change(keywordsInput, { target: { value: ' tech,  ai , ml,  ' } });

        fireEvent.click(submitButton);

        await waitFor(() => {
            expect(mockOnSubmit).toHaveBeenCalledWith(
                expect.objectContaining({
                    keywords: ['tech', 'ai', 'ml'], // スペースと空文字列が除去されている
                })
            );
        });
    });

    // React Hook Form + Zod 固有のテスト
    it('Zodスキーマによるリアルタイムバリデーションが動作する', async () => {
        render(
            <CompanyForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const nameInput = screen.getByLabelText('企業名 *');
        const submitButton = screen.getByText('作成');
        
        // 一度送信してバリデーションを有効化
        fireEvent.click(submitButton);

        await waitFor(() => {
            expect(screen.getByText('企業名は必須です')).toBeInTheDocument();
        });

        // 有効な値を入力してエラーが消えることを確認（onChangeモード）
        fireEvent.change(nameInput, { target: { value: 'テスト企業' } });

        await waitFor(() => {
            expect(screen.queryByText('企業名は必須です')).not.toBeInTheDocument();
        });

        // 再度空にしてエラーが即座に表示されることを確認
        fireEvent.change(nameInput, { target: { value: '' } });

        await waitFor(() => {
            expect(screen.getByText('企業名は必須です')).toBeInTheDocument();
        });
    });

    it('複数のURL入力で混在バリデーションが正しく動作する', async () => {
        render(
            <CompanyForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const nameInput = screen.getByLabelText('企業名 *');
        const domainInput = screen.getByLabelText('ドメイン *');
        const logoUrlInput = screen.getByLabelText('ロゴURL');
        const websiteUrlInput = screen.getByLabelText('ウェブサイトURL');
        const submitButton = screen.getByText('作成');

        fireEvent.change(nameInput, { target: { value: 'テスト企業' } });
        fireEvent.change(domainInput, { target: { value: 'test.com' } });
        fireEvent.change(logoUrlInput, { target: { value: 'https://valid-url.com/logo.png' } }); // 有効
        fireEvent.change(websiteUrlInput, { target: { value: 'invalid-url' } }); // 無効

        fireEvent.click(submitButton);

        await waitFor(() => {
            // ロゴURLは有効なのでエラーなし、ウェブサイトURLのみエラー
            expect(screen.getAllByText('有効なURLを入力してください')).toHaveLength(1);
        });

        expect(mockOnSubmit).not.toHaveBeenCalled();
    });

    it('React Hook Formのリセット機能が編集時に正しく動作する', async () => {
        const mockCompany: Company = {
            id: 1,
            name: '初期企業名',
            domain: 'initial.com',
            description: '初期説明',
            logo_url: '',
            website_url: '',
            is_active: true,
            url_patterns: ['initial.com'],
            domain_patterns: [],
            keywords: ['initial'],
            zenn_organizations: [],
            qiita_username: '',
            zenn_username: '',
            created_at: '2023-01-01T00:00:00.000000Z',
            updated_at: '2023-01-01T00:00:00.000000Z',
        };

        render(
            <CompanyForm
                company={mockCompany}
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        // フォームが企業データでリセットされることを確認
        await waitFor(() => {
            expect(screen.getByDisplayValue('初期企業名')).toBeInTheDocument();
            expect(screen.getByDisplayValue('初期説明')).toBeInTheDocument();
        });

        // 配列フィールドのリセットも確認
        const keywordsInput = screen.getByLabelText('キーワード（カンマ区切り）');
        const urlPatternsInput = screen.getByLabelText('URLパターン（カンマ区切り）');
        
        expect(keywordsInput).toHaveValue('initial');
        expect(urlPatternsInput).toHaveValue('initial.com');
    });

    it('配列フィールドでwatch機能が正しく動作する', async () => {
        render(
            <CompanyForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        const keywordsInput = screen.getByLabelText('キーワード（カンマ区切り）');
        
        // 配列フィールドに値を入力
        fireEvent.change(keywordsInput, { target: { value: 'keyword1, keyword2' } });

        // 表示される値がwatch関数によって正しく更新されることを確認
        await waitFor(() => {
            expect(keywordsInput).toHaveValue('keyword1, keyword2');
        });

        // 値を変更
        fireEvent.change(keywordsInput, { target: { value: 'new1, new2, new3' } });

        await waitFor(() => {
            expect(keywordsInput).toHaveValue('new1, new2, new3');
        });
    });

    it('フォーム送信時にZodスキーマ全体のバリデーションが実行される', async () => {
        render(
            <CompanyForm
                onSubmit={mockOnSubmit}
                onCancel={mockOnCancel}
            />
        );

        // 必須フィールドを空のまま送信
        const submitButton = screen.getByText('作成');
        fireEvent.click(submitButton);

        // Zodスキーマの全バリデーションエラーが表示される
        await waitFor(() => {
            expect(screen.getByText('企業名は必須です')).toBeInTheDocument();
            expect(screen.getByText('ドメインは必須です')).toBeInTheDocument();
        });

        // onSubmitが呼ばれないことを確認
        expect(mockOnSubmit).not.toHaveBeenCalled();
    });
});