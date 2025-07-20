import React from 'react';
import { DocumentTextIcon } from '@heroicons/react/24/outline';
import { ArticleListFilters } from '../types';

interface ArticleEmptyStateProps {
    filters: ArticleListFilters;
    onClearFilters: () => void;
}

const ArticleEmptyState: React.FC<ArticleEmptyStateProps> = ({
    filters,
    onClearFilters
}) => {
    const hasFilters = Boolean(
        filters.search || 
        filters.company_id || 
        filters.platform_id || 
        filters.start_date || 
        filters.end_date
    );

    return (
        <div className="text-center py-12">
            <DocumentTextIcon className="mx-auto h-12 w-12 text-gray-400 mb-4" />
            <div className="text-gray-500">記事が見つかりませんでした</div>
            {hasFilters && (
                <button
                    onClick={onClearFilters}
                    className="mt-2 text-blue-600 hover:text-blue-900 text-sm"
                >
                    フィルターをクリア
                </button>
            )}
        </div>
    );
};

export default ArticleEmptyState;