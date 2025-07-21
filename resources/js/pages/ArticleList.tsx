import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { apiService } from '../services/api';
import { ArticlesListResponse, ArticleListFilters, QueryKeys } from '../types';
import ArticleFilters from '../components/ArticleFilters';
import ArticleListHeader from '../components/ArticleListHeader';
import ArticleItem from '../components/ArticleItem';
import ArticlePagination from '../components/ArticlePagination';
import ArticleEmptyState from '../components/ArticleEmptyState';

const ArticleList: React.FC = () => {
    const [filters, setFilters] = useState<ArticleListFilters>({
        page: 1,
        per_page: 20,
        sort_by: 'published_at',
        sort_order: 'desc',
    });

    const [showFilters, setShowFilters] = useState(false);

    const { data: response, isLoading, error } = useQuery({
        queryKey: QueryKeys.ARTICLES_LIST(filters),
        queryFn: () => apiService.getArticles(filters).then(res => res.data as ArticlesListResponse),
        retry: 1,
    });

    const handleSearchChange = (search: string) => {
        setFilters(prev => ({
            ...prev,
            search: search || undefined,
            page: 1,
        }));
    };


    const handleDateRangeFilter = (startDate: string, endDate: string) => {
        setFilters(prev => ({
            ...prev,
            start_date: startDate || undefined,
            end_date: endDate || undefined,
            page: 1,
        }));
    };


    const handlePageChange = (page: number) => {
        setFilters(prev => ({ ...prev, page }));
    };

    const handlePerPageChange = (perPage: number) => {
        setFilters(prev => ({
            ...prev,
            per_page: perPage,
            page: 1,
        }));
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const clearFilters = () => {
        setFilters({
            page: 1,
            per_page: 20,
            sort_by: 'published_at',
            sort_order: 'desc',
        });
    };

    if (error) {
        return (
            <div className="text-center py-8">
                <div className="text-red-600 mb-4">記事一覧の読み込みに失敗しました</div>
                <button 
                    onClick={() => window.location.reload()} 
                    className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    再読み込み
                </button>
            </div>
        );
    }

    return (
        <div>
            <div className="mb-8 flex justify-between items-start">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">記事一覧</h1>
                    <p className="text-gray-600">企業別の技術記事を確認できます</p>
                </div>
            </div>

            <ArticleFilters
                filters={filters}
                showFilters={showFilters}
                onSearchChange={handleSearchChange}
                onToggleFilters={() => setShowFilters(!showFilters)}
                onPerPageChange={handlePerPageChange}
                onDateRangeFilter={handleDateRangeFilter}
                onClearFilters={clearFilters}
            />

            {/* 記事一覧 */}
            <div className="dashboard-card">
                {isLoading ? (
                    <div className="flex items-center justify-center py-12">
                        <span className="loading-spinner mr-3"></span>
                        <span className="text-gray-600">記事データを読み込み中...</span>
                    </div>
                ) : response?.data && response.data.length > 0 ? (
                    <>
                        <ArticleListHeader
                            total={response.meta.total}
                            currentPage={response.meta.current_page}
                            perPage={response.meta.per_page}
                            filters={filters}
                            onSortChange={(sortBy, sortOrder) => {
                                setFilters(prev => ({ ...prev, sort_by: sortBy, sort_order: sortOrder, page: 1 }));
                            }}
                        />

                        {/* 記事リスト */}
                        <div className="space-y-0 divide-y divide-gray-200" data-testid="article-list">
                            {response.data.map((article) => (
                                <ArticleItem
                                    key={article.id}
                                    article={article}
                                    formatDate={formatDate}
                                />
                            ))}
                        </div>

                        <ArticlePagination
                            currentPage={response.meta.current_page}
                            lastPage={response.meta.last_page}
                            onPageChange={handlePageChange}
                        />
                    </>
                ) : (
                    <div className="text-center py-8">
                        <p>記事が見つかりませんでした</p>
                        <ArticleEmptyState
                            filters={filters}
                            onClearFilters={clearFilters}
                        />
                    </div>
                )}
            </div>
        </div>
    );
};

export default ArticleList;