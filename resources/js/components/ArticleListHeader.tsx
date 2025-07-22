import React from 'react';
import { ArticleListFilters } from '../types';

interface ArticleListHeaderProps {
    total: number;
    currentPage: number;
    perPage: number;
    filters: ArticleListFilters;
    onSortChange: (sortBy: string, sortOrder: 'asc' | 'desc') => void;
}

const ArticleListHeader: React.FC<ArticleListHeaderProps> = ({
    total,
    currentPage,
    perPage,
    filters,
    onSortChange
}) => {
    const startItem = ((currentPage - 1) * perPage) + 1;
    const endItem = Math.min(currentPage * perPage, total);

    return (
        <div className="flex items-center justify-between mb-4">
            <div className="text-sm text-gray-600">
                {total}件中 {startItem}-{endItem}件を表示
            </div>
            <div className="flex items-center gap-4">
                <select
                    value={`${filters.sort_by}:${filters.sort_order}`}
                    onChange={(e) => {
                        const [sortBy, sortOrder] = e.target.value.split(':');
                        onSortChange(sortBy, sortOrder as 'asc' | 'desc');
                    }}
                    className="text-sm border border-gray-300 rounded px-2 py-1"
                >
                    <option value="published_at:desc">公開日(新しい順)</option>
                    <option value="published_at:asc">公開日(古い順)</option>
                    <option value="engagement_count:desc">エンゲージメント数(多い順)</option>
                    <option value="engagement_count:asc">エンゲージメント数(少ない順)</option>
                    <option value="title:asc">タイトル(A-Z)</option>
                    <option value="title:desc">タイトル(Z-A)</option>
                </select>
            </div>
        </div>
    );
};

export default ArticleListHeader;