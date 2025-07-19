import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Company } from '../types';

// Zodスキーマ定義
const companySchema = z.object({
    name: z.string().min(1, '企業名は必須です'),
    domain: z.string().min(1, 'ドメインは必須です'),
    description: z.string().optional(),
    logo_url: z.string().url('有効なURLを入力してください').optional().or(z.literal('')),
    website_url: z.string().url('有効なURLを入力してください').optional().or(z.literal('')),
    is_active: z.boolean().default(true),
    url_patterns: z.array(z.string()).default([]),
    domain_patterns: z.array(z.string()).default([]),
    keywords: z.array(z.string()).default([]),
    zenn_organizations: z.array(z.string()).default([]),
    qiita_username: z.string().optional(),
    zenn_username: z.string().optional(),
});

type CompanyFormData = z.infer<typeof companySchema>;

interface CompanyFormProps {
    company?: Company;
    onSubmit: (data: Partial<Company>) => void;
    onCancel: () => void;
    isLoading?: boolean;
}

const CompanyForm: React.FC<CompanyFormProps> = ({ company, onSubmit, onCancel, isLoading = false }) => {
    const {
        register,
        handleSubmit,
        setValue,
        watch,
        formState: { errors },
        reset
    } = useForm<CompanyFormData>({
        resolver: zodResolver(companySchema),
        mode: 'onChange',
        defaultValues: {
            name: '',
            domain: '',
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
        }
    });

    // 編集時の初期値設定
    useEffect(() => {
        if (company) {
            reset({
                name: company.name,
                domain: company.domain,
                description: company.description || '',
                logo_url: company.logo_url || '',
                website_url: company.website_url || '',
                is_active: company.is_active,
                url_patterns: company.url_patterns || [],
                domain_patterns: company.domain_patterns || [],
                keywords: company.keywords || [],
                zenn_organizations: company.zenn_organizations || [],
                qiita_username: company.qiita_username || '',
                zenn_username: company.zenn_username || '',
            });
        }
    }, [company, reset]);

    // 配列フィールドの処理用ヘルパー
    const handleArrayFieldChange = (fieldName: keyof Pick<CompanyFormData, 'url_patterns' | 'domain_patterns' | 'keywords' | 'zenn_organizations'>, value: string) => {
        const items = value.split(',').map(item => item.trim()).filter(item => item);
        setValue(fieldName, items);
    };

    // フォーム送信処理
    const onFormSubmit = (data: CompanyFormData) => {
        onSubmit(data);
    };

    // 入力フィールドレンダリング用ヘルパー
    const renderTextInput = (
        id: keyof CompanyFormData,
        label: string,
        placeholder?: string,
        required = false,
        type: string = 'text'
    ) => (
        <div>
            <label htmlFor={id} className="block text-sm font-medium text-gray-700">
                {label} {required && <span className="text-red-500">*</span>}
            </label>
            <input
                type={type}
                id={id}
                {...register(id)}
                placeholder={placeholder}
                className={`mt-1 block w-full rounded-md shadow-sm ${
                    errors[id] ? 'border-red-300' : 'border-gray-300'
                } focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm`}
            />
            {errors[id] && <p className="mt-1 text-sm text-red-600">{errors[id]?.message}</p>}
        </div>
    );

    // 配列フィールドレンダリング用ヘルパー
    const renderArrayInput = (
        fieldName: keyof Pick<CompanyFormData, 'url_patterns' | 'domain_patterns' | 'keywords' | 'zenn_organizations'>,
        label: string,
        placeholder: string
    ) => {
        const currentValue = watch(fieldName);
        return (
            <div>
                <label htmlFor={fieldName} className="block text-sm font-medium text-gray-700">
                    {label}
                </label>
                <input
                    type="text"
                    id={fieldName}
                    value={currentValue?.join(', ') || ''}
                    onChange={(e) => handleArrayFieldChange(fieldName, e.target.value)}
                    placeholder={placeholder}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
            </div>
        );
    };

    return (
        <form onSubmit={handleSubmit(onFormSubmit)} className="space-y-6">
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                {renderTextInput('name', '企業名', undefined, true)}
                {renderTextInput('domain', 'ドメイン', 'example.com', true)}
            </div>

            <div>
                <label htmlFor="description" className="block text-sm font-medium text-gray-700">
                    説明
                </label>
                <textarea
                    id="description"
                    {...register('description')}
                    rows={3}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
                {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description.message}</p>}
            </div>

            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                {renderTextInput('logo_url', 'ロゴURL', 'https://example.com/logo.png', false, 'url')}
                {renderTextInput('website_url', 'ウェブサイトURL', 'https://example.com', false, 'url')}
            </div>

            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                {renderTextInput('qiita_username', 'Qiitaユーザー名')}
                {renderTextInput('zenn_username', 'Zennユーザー名')}
            </div>

            {renderArrayInput('keywords', 'キーワード（カンマ区切り）', 'AI, 機械学習, クラウド')}

            {renderArrayInput('url_patterns', 'URLパターン（カンマ区切り）', 'tech.example.com, blog.example.com')}

            {renderArrayInput('domain_patterns', 'ドメインパターン（カンマ区切り）', 'example.com, sub.example.com')}

            {renderArrayInput('zenn_organizations', 'Zenn組織（カンマ区切り）', 'organization1, organization2')}

            <div className="flex items-center">
                <input
                    type="checkbox"
                    id="is_active"
                    {...register('is_active')}
                    className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <label htmlFor="is_active" className="ml-2 block text-sm text-gray-900">
                    アクティブ
                </label>
            </div>

            <div className="flex justify-end space-x-3">
                <button
                    type="button"
                    onClick={onCancel}
                    className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    キャンセル
                </button>
                <button
                    type="submit"
                    disabled={isLoading}
                    className="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                >
                    {isLoading ? '保存中...' : company ? '更新' : '作成'}
                </button>
            </div>
        </form>
    );
};

export default CompanyForm;