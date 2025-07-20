import React from 'react';
import { MagnifyingGlassIcon, FunnelIcon } from '@heroicons/react/24/outline';
import { ArticleListFilters } from '../types';

interface ArticleFiltersProps {
    filters: ArticleListFilters;
    showFilters: boolean;
    onSearchChange: (search: string) => void;
    onToggleFilters: () => void;
    onPerPageChange: (perPage: number) => void;
    onDateRangeFilter: (startDate: string, endDate: string) => void;
    onClearFilters: () => void;
}

const ArticleFilters: React.FC<ArticleFiltersProps> = ({
    filters,
    showFilters,
    onSearchChange,
    onToggleFilters,
    onPerPageChange,
    onDateRangeFilter,
    onClearFilters
}) => {
    return (
        <div className="dashboard-card mb-6">
            <div className="flex flex-col gap-4">
                {/* 基本検索 */}
                <div className="flex flex-col sm:flex-row gap-4">
                    <div className="flex-1">
                        <div className="relative">
                            <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                type="text"
                                placeholder="記事タイトルや著者名で検索..."
                                value={filters.search || ''}
                                onChange={(e) => onSearchChange(e.target.value)}
                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <button
                            onClick={onToggleFilters}
                            className={`inline-flex items-center px-3 py-2 border rounded-md text-sm font-medium ${
                                showFilters 
                                    ? 'border-blue-500 text-blue-600 bg-blue-50' 
                                    : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'
                            }`}
                        >
                            <FunnelIcon className="h-4 w-4 mr-2" />
                            フィルター
                        </button>
                        <select
                            value={filters.per_page}
                            onChange={(e) => onPerPageChange(Number(e.target.value))}
                            className="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value={10}>10件</option>
                            <option value={20}>20件</option>
                            <option value={50}>50件</option>
                            <option value={100}>100件</option>
                        </select>
                    </div>
                </div>

                {/* 詳細フィルター */}
                {showFilters && (
                    <div className="border-t pt-4 space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    開始日
                                </label>
                                <input
                                    type="date"
                                    value={filters.start_date || ''}
                                    onChange={(e) => onDateRangeFilter(e.target.value, filters.end_date || '')}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    終了日
                                </label>
                                <input
                                    type="date"
                                    value={filters.end_date || ''}
                                    onChange={(e) => onDateRangeFilter(filters.start_date || '', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>
                        
                        {(filters.search || filters.company_id || filters.platform_id || filters.start_date || filters.end_date) && (
                            <div className="flex justify-end">
                                <button
                                    onClick={onClearFilters}
                                    className="text-sm text-blue-600 hover:text-blue-900"
                                >
                                    フィルターをクリア
                                </button>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
};

export default ArticleFilters;