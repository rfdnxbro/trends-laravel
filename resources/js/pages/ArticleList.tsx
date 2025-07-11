import React, { useState, useEffect } from 'react';
import { ChevronRightIcon } from '@heroicons/react/24/outline';
import { Article, Company, Platform, PaginationData } from '../types';

const ArticleList: React.FC = () => {
    const [articles, setArticles] = useState<PaginationData | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [currentPage, setCurrentPage] = useState(1);

    useEffect(() => {
        fetchArticles(currentPage);
    }, [currentPage]);

    const fetchArticles = async (page: number) => {
        try {
            setLoading(true);
            const response = await fetch(`/api/articles?page=${page}`);
            if (!response.ok) {
                throw new Error('記事の取得に失敗しました');
            }
            const data = await response.json();
            setArticles(data);
            setError(null);
        } catch (err) {
            setError(err instanceof Error ? err.message : '不明なエラーが発生しました');
        } finally {
            setLoading(false);
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    if (loading) {
        return (
            <div className="flex justify-center items-center h-64">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600 mx-auto"></div>
                    <p className="mt-4 text-gray-600">読み込み中...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="bg-red-50 border border-red-200 rounded-md p-4">
                <div className="flex">
                    <div className="ml-3">
                        <h3 className="text-sm font-medium text-red-800">エラー</h3>
                        <div className="mt-2 text-sm text-red-700">{error}</div>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="sm:flex sm:items-center">
                <div className="sm:flex-auto">
                    <h1 className="text-2xl font-semibold text-gray-900">記事一覧</h1>
                    <p className="mt-2 text-sm text-gray-700">
                        企業別の技術記事を確認できます。
                    </p>
                </div>
            </div>

            {articles && (
                <>
                    <div className="bg-white shadow overflow-hidden sm:rounded-md">
                        <ul role="list" className="divide-y divide-gray-200">
                            {articles.data.map((article) => (
                                <li key={article.id}>
                                    <a
                                        href={article.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="block hover:bg-gray-50 px-4 py-4 sm:px-6"
                                    >
                                        <div className="flex items-center justify-between">
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-gray-900 truncate">
                                                    {article.title}
                                                </p>
                                                <div className="mt-2 flex items-center text-sm text-gray-500">
                                                    <div className="flex items-center space-x-4">
                                                        <div className="flex items-center space-x-2">
                                                            {article.company?.logo_url && (
                                                                <img
                                                                    src={article.company.logo_url}
                                                                    alt={article.company.name}
                                                                    className="h-5 w-5 rounded-full"
                                                                />
                                                            )}
                                                            <span className="font-medium text-gray-900">
                                                                {article.company?.name || '不明な企業'}
                                                            </span>
                                                        </div>
                                                        <span className="text-gray-300">•</span>
                                                        <span className="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                                            {article.platform.name}
                                                        </span>
                                                        {article.author_name && (
                                                            <>
                                                                <span className="text-gray-300">•</span>
                                                                <span>{article.author_name}</span>
                                                            </>
                                                        )}
                                                        <span className="text-gray-300">•</span>
                                                        <span>{formatDate(article.published_at)}</span>
                                                        {article.bookmark_count > 0 && (
                                                            <>
                                                                <span className="text-gray-300">•</span>
                                                                <span className="text-green-600">
                                                                    {article.bookmark_count} ブックマーク
                                                                </span>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="ml-5 flex-shrink-0">
                                                <ChevronRightIcon className="h-5 w-5 text-gray-400" />
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {articles.last_page > 1 && (
                        <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                            <div className="flex-1 flex justify-between sm:hidden">
                                <button
                                    onClick={() => setCurrentPage(currentPage - 1)}
                                    disabled={currentPage === 1}
                                    className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    前へ
                                </button>
                                <button
                                    onClick={() => setCurrentPage(currentPage + 1)}
                                    disabled={currentPage === articles.last_page}
                                    className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    次へ
                                </button>
                            </div>
                            <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p className="text-sm text-gray-700">
                                        <span className="font-medium">{articles.total}</span> 件中{' '}
                                        <span className="font-medium">
                                            {(articles.current_page - 1) * articles.per_page + 1}
                                        </span>{' '}
                                        -{' '}
                                        <span className="font-medium">
                                            {Math.min(articles.current_page * articles.per_page, articles.total)}
                                        </span>{' '}
                                        件を表示
                                    </p>
                                </div>
                                <div>
                                    <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <button
                                            onClick={() => setCurrentPage(currentPage - 1)}
                                            disabled={currentPage === 1}
                                            className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            前へ
                                        </button>
                                        {Array.from({ length: articles.last_page }, (_, i) => i + 1)
                                            .filter(page => 
                                                page === 1 || 
                                                page === articles.last_page || 
                                                Math.abs(page - currentPage) <= 2
                                            )
                                            .map((page, index, array) => (
                                                <React.Fragment key={page}>
                                                    {index > 0 && array[index - 1] !== page - 1 && (
                                                        <span className="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                                            ...
                                                        </span>
                                                    )}
                                                    <button
                                                        onClick={() => setCurrentPage(page)}
                                                        className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                            page === currentPage
                                                                ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                        }`}
                                                    >
                                                        {page}
                                                    </button>
                                                </React.Fragment>
                                            ))}
                                        <button
                                            onClick={() => setCurrentPage(currentPage + 1)}
                                            disabled={currentPage === articles.last_page}
                                            className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            次へ
                                        </button>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    )}
                </>
            )}
        </div>
    );
};

export default ArticleList;