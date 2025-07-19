import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { MagnifyingGlassIcon, BuildingOfficeIcon, PlusIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
import { apiService } from '../services/api';
import { CompaniesListResponse, CompanyListFilters, QueryKeys, Company } from '../types';
import Modal from '../components/Modal';
import CompanyForm from '../components/CompanyForm';

const CompanyList: React.FC = () => {
    const [filters, setFilters] = useState<CompanyListFilters>({
        page: 1,
        per_page: 20,
        sort_by: 'name',
        sort_order: 'asc',
    });
    
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [selectedCompany, setSelectedCompany] = useState<Company | null>(null);
    
    const queryClient = useQueryClient();

    const { data: response, isLoading, error } = useQuery({
        queryKey: QueryKeys.COMPANIES_LIST(filters),
        queryFn: () => apiService.getCompanies(filters).then(res => res.data as CompaniesListResponse),
        retry: 1,
    });

    const createMutation = useMutation({
        mutationFn: (data: Partial<Company>) => apiService.createCompany(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['companies'] });
            setIsCreateModalOpen(false);
            window.alert('企業を作成しました');
        },
        onError: (error: Error) => {
            window.alert('エラー: ' + (error.response?.data?.message || '企業の作成に失敗しました'));
        },
    });

    const updateMutation = useMutation({
        mutationFn: ({ id, data }: { id: number; data: Partial<Company> }) => 
            apiService.updateCompany(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['companies'] });
            setIsEditModalOpen(false);
            setSelectedCompany(null);
            window.alert('企業情報を更新しました');
        },
        onError: (error: Error) => {
            window.alert('エラー: ' + (error.response?.data?.message || '企業情報の更新に失敗しました'));
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (id: number) => apiService.deleteCompany(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['companies'] });
            setIsDeleteModalOpen(false);
            setSelectedCompany(null);
            window.alert('企業を削除しました');
        },
        onError: (error: Error) => {
            window.alert('エラー: ' + (error.response?.data?.message || '企業の削除に失敗しました'));
        },
    });

    const handleSearchChange = (search: string) => {
        setFilters(prev => ({
            ...prev,
            search: search || undefined,
            page: 1,
        }));
    };

    const handleSortChange = (sortBy: string) => {
        setFilters(prev => ({
            ...prev,
            sort_by: sortBy,
            sort_order: prev.sort_by === sortBy && prev.sort_order === 'asc' ? 'desc' : 'asc',
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

    const handleEdit = (company: Company) => {
        setSelectedCompany(company);
        setIsEditModalOpen(true);
    };

    const handleDelete = (company: Company) => {
        setSelectedCompany(company);
        setIsDeleteModalOpen(true);
    };

    const handleCreateSubmit = (data: Partial<Company>) => {
        createMutation.mutate(data);
    };

    const handleUpdateSubmit = (data: Partial<Company>) => {
        if (selectedCompany) {
            updateMutation.mutate({ id: selectedCompany.id, data });
        }
    };

    const handleDeleteConfirm = () => {
        if (selectedCompany) {
            deleteMutation.mutate(selectedCompany.id);
        }
    };

    if (error) {
        return (
            <div className="text-center py-8">
                <div className="text-red-600 mb-4">企業一覧の読み込みに失敗しました</div>
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
                    <h1 className="text-2xl font-bold text-gray-900">企業一覧</h1>
                    <p className="text-gray-600">登録されている企業の一覧を表示します</p>
                </div>
                <button
                    onClick={() => setIsCreateModalOpen(true)}
                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <PlusIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
                    新規作成
                </button>
            </div>

            {/* 検索・フィルター */}
            <div className="dashboard-card mb-6">
                <div className="flex flex-col sm:flex-row gap-4">
                    <div className="flex-1">
                        <div className="relative">
                            <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                type="text"
                                placeholder="企業名で検索..."
                                value={filters.search || ''}
                                onChange={(e) => handleSearchChange(e.target.value)}
                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <select
                            value={filters.per_page}
                            onChange={(e) => handlePerPageChange(Number(e.target.value))}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value={10}>10件</option>
                            <option value={20}>20件</option>
                            <option value={50}>50件</option>
                            <option value={100}>100件</option>
                        </select>
                    </div>
                </div>
            </div>

            {/* 企業一覧 */}
            <div className="dashboard-card">
                {isLoading ? (
                    <div className="flex items-center justify-center py-12">
                        <span className="loading-spinner mr-3"></span>
                        <span className="text-gray-600">企業データを読み込み中...</span>
                    </div>
                ) : response?.data && response.data.length > 0 ? (
                    <>
                        {/* ヘッダー */}
                        <div className="flex items-center justify-between mb-4">
                            <div className="text-sm text-gray-600">
                                {response.meta.total}社中 {((response.meta.current_page - 1) * response.meta.per_page) + 1}-{Math.min(response.meta.current_page * response.meta.per_page, response.meta.total)}社を表示
                            </div>
                        </div>

                        {/* テーブル */}
                        <div className="overflow-x-auto">
                            <table className="data-table">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="cursor-pointer hover:bg-gray-100" onClick={() => handleSortChange('name')}>
                                            企業名
                                            {filters.sort_by === 'name' && (
                                                <span className="ml-1">
                                                    {filters.sort_order === 'asc' ? '↑' : '↓'}
                                                </span>
                                            )}
                                        </th>
                                        <th>ドメイン</th>
                                        <th>説明</th>
                                        <th className="cursor-pointer hover:bg-gray-100" onClick={() => handleSortChange('created_at')}>
                                            登録日
                                            {filters.sort_by === 'created_at' && (
                                                <span className="ml-1">
                                                    {filters.sort_order === 'asc' ? '↑' : '↓'}
                                                </span>
                                            )}
                                        </th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {response.data.map((company) => (
                                        <tr key={company.id} className="hover:bg-gray-50">
                                            <td className="font-medium text-blue-900">
                                                <div className="flex items-center">
                                                    {company.logo_url ? (
                                                        <img 
                                                            src={company.logo_url} 
                                                            alt={`${company.name} logo`}
                                                            className="w-8 h-8 rounded mr-3 object-cover"
                                                        />
                                                    ) : (
                                                        <BuildingOfficeIcon className="w-8 h-8 text-gray-400 mr-3" />
                                                    )}
                                                    {company.name}
                                                </div>
                                            </td>
                                            <td className="text-sm text-gray-600">
                                                {company.domain}
                                            </td>
                                            <td className="text-sm text-gray-600 max-w-xs truncate">
                                                {company.description || '-'}
                                            </td>
                                            <td className="text-sm text-gray-600">
                                                {new Date(company.created_at).toLocaleDateString('ja-JP')}
                                            </td>
                                            <td>
                                                <div className="flex items-center space-x-3">
                                                    <Link 
                                                        to={`/companies/${company.id}`}
                                                        className="text-blue-600 hover:text-blue-900 text-sm font-medium"
                                                    >
                                                        詳細
                                                    </Link>
                                                    <button
                                                        onClick={() => handleEdit(company)}
                                                        className="text-indigo-600 hover:text-indigo-900"
                                                    >
                                                        <PencilIcon className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(company)}
                                                        className="text-red-600 hover:text-red-900"
                                                    >
                                                        <TrashIcon className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* ページネーション */}
                        {response.meta.last_page > 1 && (
                            <div className="flex items-center justify-between mt-6">
                                <div className="flex items-center space-x-2">
                                    <button
                                        onClick={() => handlePageChange(response.meta.current_page - 1)}
                                        disabled={response.meta.current_page === 1}
                                        className="px-3 py-1 text-sm border border-gray-300 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                                    >
                                        前へ
                                    </button>
                                    
                                    <div className="flex items-center space-x-1">
                                        {Array.from({ length: Math.min(5, response.meta.last_page) }, (_, i) => {
                                            const pageNumber = Math.max(1, response.meta.current_page - 2) + i;
                                            if (pageNumber > response.meta.last_page) return null;
                                            
                                            return (
                                                <button
                                                    key={pageNumber}
                                                    onClick={() => handlePageChange(pageNumber)}
                                                    className={`px-3 py-1 text-sm border rounded ${
                                                        pageNumber === response.meta.current_page
                                                            ? 'bg-blue-600 text-white border-blue-600'
                                                            : 'border-gray-300 hover:bg-gray-50'
                                                    }`}
                                                >
                                                    {pageNumber}
                                                </button>
                                            );
                                        })}
                                    </div>
                                    
                                    <button
                                        onClick={() => handlePageChange(response.meta.current_page + 1)}
                                        disabled={response.meta.current_page === response.meta.last_page}
                                        className="px-3 py-1 text-sm border border-gray-300 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                                    >
                                        次へ
                                    </button>
                                </div>
                                
                                <div className="text-sm text-gray-600">
                                    ページ {response.meta.current_page} / {response.meta.last_page}
                                </div>
                            </div>
                        )}
                    </>
                ) : (
                    <div className="text-center py-12">
                        <BuildingOfficeIcon className="mx-auto h-12 w-12 text-gray-400 mb-4" />
                        <div className="text-gray-500">企業が見つかりませんでした</div>
                        {filters.search && (
                            <button
                                onClick={() => handleSearchChange('')}
                                className="mt-2 text-blue-600 hover:text-blue-900 text-sm"
                            >
                                検索をクリア
                            </button>
                        )}
                    </div>
                )}
            </div>

            {/* 作成モーダル */}
            <Modal
                isOpen={isCreateModalOpen}
                onClose={() => setIsCreateModalOpen(false)}
                title="企業新規作成"
                size="xl"
            >
                <CompanyForm
                    onSubmit={handleCreateSubmit}
                    onCancel={() => setIsCreateModalOpen(false)}
                    isLoading={createMutation.isLoading}
                />
            </Modal>

            {/* 編集モーダル */}
            <Modal
                isOpen={isEditModalOpen}
                onClose={() => {
                    setIsEditModalOpen(false);
                    setSelectedCompany(null);
                }}
                title="企業情報編集"
                size="xl"
            >
                {selectedCompany && (
                    <CompanyForm
                        company={selectedCompany}
                        onSubmit={handleUpdateSubmit}
                        onCancel={() => {
                            setIsEditModalOpen(false);
                            setSelectedCompany(null);
                        }}
                        isLoading={updateMutation.isLoading}
                    />
                )}
            </Modal>

            {/* 削除確認モーダル */}
            <Modal
                isOpen={isDeleteModalOpen}
                onClose={() => {
                    setIsDeleteModalOpen(false);
                    setSelectedCompany(null);
                }}
                title="企業削除確認"
                size="sm"
            >
                <div className="text-sm text-gray-600 mb-4">
                    「{selectedCompany?.name}」を削除してもよろしいですか？<br />
                    この操作は取り消せません。
                </div>
                <div className="flex justify-end space-x-3">
                    <button
                        type="button"
                        onClick={() => {
                            setIsDeleteModalOpen(false);
                            setSelectedCompany(null);
                        }}
                        className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        キャンセル
                    </button>
                    <button
                        type="button"
                        onClick={handleDeleteConfirm}
                        disabled={deleteMutation.isLoading}
                        className="inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50"
                    >
                        {deleteMutation.isLoading ? '削除中...' : '削除'}
                    </button>
                </div>
            </Modal>
        </div>
    );
};

export default CompanyList;