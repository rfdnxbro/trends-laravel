import React, { useState } from 'react';
import { MagnifyingGlassIcon, FunnelIcon, ArrowUpIcon, ArrowDownIcon } from '@heroicons/react/24/outline';
import { RankingTableProps, RankingPeriod, RankingSortOption } from '../types';
import { UI_CONSTANTS } from '../constants/api';
import RankingCard from './RankingCard';

const RankingTable: React.FC<RankingTableProps> = ({
    rankings,
    loading = false,
    filters,
    onFiltersChange,
    onCompanyClick
}) => {
    const [isFilterOpen, setIsFilterOpen] = useState(false);
    const [searchInput, setSearchInput] = useState(filters.searchQuery || '');

    const periods: RankingPeriod[] = [
        { value: 'daily', label: '日次' },
        { value: 'weekly', label: '週次' },
        { value: 'monthly', label: '月次' },
        { value: 'quarterly', label: '四半期' },
        { value: 'half_yearly', label: '半年' },
        { value: 'yearly', label: '年次' },
        { value: 'all_time', label: '全期間' }
    ];

    const sortOptions: RankingSortOption[] = [
        { value: 'rank', label: '順位' },
        { value: 'influence_score', label: 'スコア' },
        { value: 'company_name', label: '企業名' },
        { value: 'rank_change', label: '順位変動' }
    ];

    const handlePeriodChange = (period: string) => {
        onFiltersChange({ ...filters, period });
    };

    const handleSortChange = (sortBy: string) => {
        const newSortOrder = filters.sortBy === sortBy && filters.sortOrder === 'asc' ? 'desc' : 'asc';
        onFiltersChange({ ...filters, sortBy, sortOrder: newSortOrder });
    };

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onFiltersChange({ ...filters, searchQuery: searchInput });
    };

    const getSortIcon = (sortBy: string) => {
        if (filters.sortBy !== sortBy) return null;
        return filters.sortOrder === 'asc' ? 
            <ArrowUpIcon className={`w-${UI_CONSTANTS.ICON_SIZE} h-${UI_CONSTANTS.ICON_SIZE}`} /> : 
            <ArrowDownIcon className={`w-${UI_CONSTANTS.ICON_SIZE} h-${UI_CONSTANTS.ICON_SIZE}`} />;
    };

    if (loading) {
        return (
            <div className="bg-white rounded-lg shadow">
                <div className="p-6">
                    <div className="animate-pulse">
                        <div className="h-8 bg-gray-200 rounded w-1/4 mb-4"></div>
                        <div className="space-y-3">
                            {[...Array(UI_CONSTANTS.SKELETON_COUNT)].map((_, i) => (
                                <div key={i} className="h-20 bg-gray-200 rounded"></div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-lg shadow">
            {/* ヘッダー */}
            <div className="p-6 border-b border-gray-200">
                <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                    <h2 className="text-2xl font-bold text-gray-900">企業影響力ランキング</h2>
                    
                    <div className="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                        {/* 検索フォーム */}
                        <form onSubmit={handleSearchSubmit} className="flex">
                            <div className="relative">
                                <MagnifyingGlassIcon className={`absolute left-3 top-1/2 transform -translate-y-1/2 h-${UI_CONSTANTS.ICON_SIZE} w-${UI_CONSTANTS.ICON_SIZE} text-gray-400`} />
                                <input
                                    type="text"
                                    placeholder="企業を検索..."
                                    value={searchInput}
                                    onChange={(e) => setSearchInput(e.target.value)}
                                    className="pl-10 pr-4 py-2 border border-gray-300 rounded-l-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                                />
                            </div>
                            <button
                                type="submit"
                                className="px-4 py-2 bg-blue-600 text-white rounded-r-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 text-sm"
                            >
                                検索
                            </button>
                        </form>

                        {/* フィルターボタン */}
                        <button
                            onClick={() => setIsFilterOpen(!isFilterOpen)}
                            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 text-sm"
                        >
                            <FunnelIcon className={`w-${UI_CONSTANTS.ICON_SIZE} h-${UI_CONSTANTS.ICON_SIZE} mr-2`} />
                            フィルター
                        </button>
                    </div>
                </div>

                {/* 期間タブ */}
                <div className="mt-6">
                    <nav className="flex space-x-8 overflow-x-auto">
                        {periods.map((period) => (
                            <button
                                key={period.value}
                                onClick={() => handlePeriodChange(period.value)}
                                className={`whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm ${
                                    filters.period === period.value
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                }`}
                            >
                                {period.label}
                            </button>
                        ))}
                    </nav>
                </div>

                {/* フィルターパネル */}
                {isFilterOpen && (
                    <div className="mt-4 p-4 bg-gray-50 rounded-md border">
                        <div className="flex flex-wrap items-center gap-4">
                            <label className="text-sm font-medium text-gray-700">並び順:</label>
                            {sortOptions.map((option) => (
                                <button
                                    key={option.value}
                                    onClick={() => handleSortChange(option.value)}
                                    className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${
                                        filters.sortBy === option.value
                                            ? 'bg-blue-100 text-blue-800'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    {option.label}
                                    {getSortIcon(option.value)}
                                </button>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* ランキングリスト */}
            <div className="p-6">
                {rankings.length === 0 ? (
                    <div className="text-center py-12">
                        <div className="text-gray-500">
                            <MagnifyingGlassIcon className={`mx-auto h-${UI_CONSTANTS.EMPTY_ICON_SIZE} w-${UI_CONSTANTS.EMPTY_ICON_SIZE} mb-4`} />
                            <p className="text-lg font-medium">ランキングデータが見つかりません</p>
                            <p className="text-sm">条件を変更して再度お試しください。</p>
                        </div>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {rankings.map((ranking) => (
                            <RankingCard
                                key={ranking.id}
                                ranking={ranking}
                                onClick={() => onCompanyClick?.(ranking.company)}
                                showRankChange={true}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* ページネーション用のスペース（将来実装） */}
            {rankings.length > 0 && (
                <div className="px-6 py-4 border-t border-gray-200">
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-gray-700">
                            {rankings.length}件の企業を表示中
                        </p>
                        {/* 将来的にページネーションコンポーネントを追加 */}
                    </div>
                </div>
            )}
        </div>
    );
};

export default RankingTable;