import React, { useState } from 'react';
import { useQuery, useQueryClient, useMutation } from '@tanstack/react-query';
import { apiService } from '../services/api';
import { ArticlesListResponse, ArticleListFilters, QueryKeys, Article, Company, Platform, ArticleFormData } from '../types';
import ArticleFilters from '../components/ArticleFilters';
import ArticleListHeader from '../components/ArticleListHeader';
import ArticleItem from '../components/ArticleItem';
import ArticlePagination from '../components/ArticlePagination';
import ArticleEmptyState from '../components/ArticleEmptyState';
import ArticleEditModal from '../components/ArticleEditModal';
import ArticleDeleteModal from '../components/ArticleDeleteModal';

const ArticleList: React.FC = () => {
    const [filters, setFilters] = useState<ArticleListFilters>({
        page: 1,
        per_page: 20,
        sort_by: 'published_at',
        sort_order: 'desc',
    });

    const [showFilters, setShowFilters] = useState(false);
    const [editingArticle, setEditingArticle] = useState<Article | null>(null);
    const [deletingArticle, setDeletingArticle] = useState<Article | null>(null);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    
    const queryClient = useQueryClient();

    const { data: response, isLoading, error } = useQuery({
        queryKey: QueryKeys.ARTICLES_LIST(filters),
        queryFn: () => apiService.getArticles(filters).then(res => res.data as ArticlesListResponse),
        retry: 1,
    });

    // 企業の一覧を取得
    const { data: companiesResponse } = useQuery({
        queryKey: ['companies-for-articles'],
        queryFn: () => apiService.getCompanies().then(res => res.data.data as Company[]),
        staleTime: 5 * 60 * 1000, // 5分
    });

    // プラットフォーム一覧を取得
    const { data: platformsResponse } = useQuery({
        queryKey: ['platforms'],
        queryFn: () => apiService.getPlatforms().then(res => res.data.data as Platform[]),
    });

    // 記事更新ミューテーション
    const updateArticleMutation = useMutation({
        mutationFn: ({ id, data }: { id: number; data: ArticleFormData }) => 
            apiService.updateArticle(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: QueryKeys.ARTICLES_LIST(filters) });
            setIsEditModalOpen(false);
            setEditingArticle(null);
        },
    });

    // 記事削除ミューテーション
    const deleteArticleMutation = useMutation({
        mutationFn: (id: number) => apiService.deleteArticle(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: QueryKeys.ARTICLES_LIST(filters) });
            setIsDeleteModalOpen(false);
            setDeletingArticle(null);
        },
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
        if (!dateString) {
            return '日時不明';
        }
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return '日時不明';
            }
            return date.toLocaleDateString('ja-JP', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch {
            return '日時不明';
        }
    };

    const clearFilters = () => {
        setFilters({
            page: 1,
            per_page: 20,
            sort_by: 'published_at',
            sort_order: 'desc',
        });
    };

    const handleEditArticle = (article: Article) => {
        setEditingArticle(article);
        setIsEditModalOpen(true);
    };

    const handleDeleteArticle = (article: Article) => {
        setDeletingArticle(article);
        setIsDeleteModalOpen(true);
    };

    const handleUpdateSubmit = async (data: ArticleFormData) => {
        if (!editingArticle) return;
        await updateArticleMutation.mutateAsync({ id: editingArticle.id, data });
    };

    const handleDeleteConfirm = async () => {
        if (!deletingArticle) return;
        await deleteArticleMutation.mutateAsync(deletingArticle.id);
    };

    const handleCloseEditModal = () => {
        setIsEditModalOpen(false);
        setEditingArticle(null);
    };

    const handleCloseDeleteModal = () => {
        setIsDeleteModalOpen(false);
        setDeletingArticle(null);
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
                                    onEdit={handleEditArticle}
                                    onDelete={handleDeleteArticle}
                                    showActions={true}
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

            {/* 編集モーダル */}
            <ArticleEditModal
                isOpen={isEditModalOpen}
                article={editingArticle}
                companies={companiesResponse || []}
                platforms={platformsResponse || []}
                onClose={handleCloseEditModal}
                onSubmit={handleUpdateSubmit}
                loading={updateArticleMutation.isPending}
            />

            {/* 削除モーダル */}
            <ArticleDeleteModal
                isOpen={isDeleteModalOpen}
                article={deletingArticle}
                onClose={handleCloseDeleteModal}
                onConfirm={handleDeleteConfirm}
                loading={deleteArticleMutation.isPending}
            />
        </div>
    );
};

export default ArticleList;