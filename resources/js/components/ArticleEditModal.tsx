import React, { useState, useEffect } from 'react';
import { XMarkIcon } from '@heroicons/react/24/outline';
import { Article, ArticleFormData, Company, Platform } from '../types';

interface ArticleEditModalProps {
    isOpen: boolean;
    article: Article | null;
    companies: Company[];
    platforms: Platform[];
    onClose: () => void;
    onSubmit: (data: ArticleFormData) => Promise<void>;
    loading?: boolean;
}

const ArticleEditModal: React.FC<ArticleEditModalProps> = ({
    isOpen,
    article,
    companies,
    platforms,
    onClose,
    onSubmit,
    loading = false
}) => {
    const [formData, setFormData] = useState<ArticleFormData>({
        title: '',
        url: '',
        company_id: null,
        platform_id: undefined,
        author_name: '',
        author: '',
        author_url: '',
        published_at: '',
        engagement_count: 0,
        domain: '',
        platform: '',
    });

    const [errors, setErrors] = useState<Record<string, string>>({});

    useEffect(() => {
        if (article && isOpen) {
            setFormData({
                title: article.title || '',
                url: article.url || '',
                company_id: article.company_id || null,
                platform_id: article.platform_id || '',
                author_name: article.author_name || '',
                author: article.author || '',
                author_url: article.author_url || '',
                published_at: article.published_at ? article.published_at.split(' ')[0] : '',
                engagement_count: article.engagement_count || 0,
                domain: article.domain || '',
                platform: article.platform?.name || '',
            });
            setErrors({});
        }
    }, [article, isOpen]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setErrors({});

        try {
            // 空の値やundefinedを除外したデータを作成
            const submitData: ArticleFormData = {};
            
            Object.entries(formData).forEach(([key, value]) => {
                if (value !== '' && value !== undefined && value !== null) {
                    if (key === 'company_id' && value === 'null') {
                        submitData[key as keyof ArticleFormData] = null;
                    } else {
                        (submitData as Record<string, unknown>)[key] = value;
                    }
                }
            });

            await onSubmit(submitData);
            onClose();
        } catch (error: unknown) {
            if (error && typeof error === 'object' && 'response' in error) {
                const axiosError = error as { response?: { data?: { errors?: Record<string, string> } } };
                if (axiosError.response?.data?.errors) {
                    setErrors(axiosError.response.data.errors);
                }
            }
        }
    };

    const handleInputChange = (field: keyof ArticleFormData, value: string | number | null) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
        
        // エラーがある場合はクリア
        if (errors[field]) {
            setErrors(prev => ({
                ...prev,
                [field]: ''
            }));
        }
    };

    if (!isOpen || !article) return null;

    return (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" data-testid="article-edit-modal">
            <div className="relative top-20 mx-auto p-5 border w-full max-w-2xl bg-white rounded-lg shadow-lg">
                <div className="flex items-start justify-between pb-4 border-b">
                    <h3 className="text-lg font-semibold text-gray-900">
                        記事を編集
                    </h3>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600"
                        data-testid="close-button"
                    >
                        <XMarkIcon className="h-6 w-6" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="mt-6">
                    <div className="space-y-4">
                        {/* タイトル */}
                        <div>
                            <label htmlFor="title" className="block text-sm font-medium text-gray-700">
                                タイトル
                            </label>
                            <input
                                type="text"
                                id="title"
                                value={formData.title}
                                onChange={(e) => handleInputChange('title', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                data-testid="title-input"
                            />
                            {errors.title && (
                                <p className="mt-1 text-sm text-red-600">{errors.title}</p>
                            )}
                        </div>

                        {/* URL */}
                        <div>
                            <label htmlFor="url" className="block text-sm font-medium text-gray-700">
                                URL
                            </label>
                            <input
                                type="url"
                                id="url"
                                value={formData.url}
                                onChange={(e) => handleInputChange('url', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                data-testid="url-input"
                            />
                            {errors.url && (
                                <p className="mt-1 text-sm text-red-600">{errors.url}</p>
                            )}
                        </div>

                        {/* 企業選択 */}
                        <div>
                            <label htmlFor="company_id" className="block text-sm font-medium text-gray-700">
                                企業
                            </label>
                            <select
                                id="company_id"
                                value={formData.company_id || 'null'}
                                onChange={(e) => {
                                    const value = e.target.value === 'null' ? null : parseInt(e.target.value);
                                    handleInputChange('company_id', value);
                                }}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                data-testid="company-select"
                            >
                                <option value="null">企業を選択してください</option>
                                {companies.map((company) => (
                                    <option key={company.id} value={company.id}>
                                        {company.name}
                                    </option>
                                ))}
                            </select>
                            {errors.company_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.company_id}</p>
                            )}
                        </div>

                        {/* プラットフォーム選択 */}
                        <div>
                            <label htmlFor="platform_id" className="block text-sm font-medium text-gray-700">
                                プラットフォーム
                            </label>
                            <select
                                id="platform_id"
                                value={formData.platform_id || ''}
                                onChange={(e) => {
                                    const value = e.target.value ? parseInt(e.target.value) : undefined;
                                    handleInputChange('platform_id', value);
                                }}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                data-testid="platform-select"
                            >
                                <option value="">プラットフォームを選択してください</option>
                                {platforms.map((platform) => (
                                    <option key={platform.id} value={platform.id}>
                                        {platform.name}
                                    </option>
                                ))}
                            </select>
                            {errors.platform_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.platform_id}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* 著者名 */}
                            <div>
                                <label htmlFor="author_name" className="block text-sm font-medium text-gray-700">
                                    著者名
                                </label>
                                <input
                                    type="text"
                                    id="author_name"
                                    value={formData.author_name}
                                    onChange={(e) => handleInputChange('author_name', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    data-testid="author-name-input"
                                />
                                {errors.author_name && (
                                    <p className="mt-1 text-sm text-red-600">{errors.author_name}</p>
                                )}
                            </div>

                            {/* 公開日 */}
                            <div>
                                <label htmlFor="published_at" className="block text-sm font-medium text-gray-700">
                                    公開日
                                </label>
                                <input
                                    type="date"
                                    id="published_at"
                                    value={formData.published_at}
                                    onChange={(e) => handleInputChange('published_at', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    data-testid="published-at-input"
                                />
                                {errors.published_at && (
                                    <p className="mt-1 text-sm text-red-600">{errors.published_at}</p>
                                )}
                            </div>
                        </div>

                        {/* エンゲージメント数 */}
                        <div>
                            <label htmlFor="engagement_count" className="block text-sm font-medium text-gray-700">
                                エンゲージメント数
                            </label>
                            <input
                                type="number"
                                id="engagement_count"
                                min="0"
                                value={formData.engagement_count}
                                onChange={(e) => handleInputChange('engagement_count', parseInt(e.target.value) || 0)}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                data-testid="engagement-count-input"
                            />
                            {errors.engagement_count && (
                                <p className="mt-1 text-sm text-red-600">{errors.engagement_count}</p>
                            )}
                        </div>
                    </div>

                    <div className="mt-6 flex items-center justify-end space-x-3 pt-4 border-t">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            data-testid="cancel-button"
                        >
                            キャンセル
                        </button>
                        <button
                            type="submit"
                            disabled={loading}
                            className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            data-testid="submit-button"
                        >
                            {loading ? '更新中...' : '更新'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default ArticleEditModal;