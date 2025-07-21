import React from 'react';

interface ArticlePaginationProps {
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
}

const ArticlePagination: React.FC<ArticlePaginationProps> = ({
    currentPage,
    lastPage,
    onPageChange
}) => {
    if (lastPage <= 1) {
        return null;
    }

    return (
        <div className="flex items-center justify-between mt-6">
            <div className="flex items-center space-x-2">
                <button
                    onClick={() => onPageChange(currentPage - 1)}
                    disabled={currentPage === 1}
                    className="px-3 py-1 text-sm border border-gray-300 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                >
                    前へ
                </button>
                
                <div className="flex items-center space-x-1">
                    {Array.from({ length: Math.min(5, lastPage) }, (_, i) => {
                        const pageNumber = Math.max(1, currentPage - 2) + i;
                        if (pageNumber > lastPage) return null;
                        
                        return (
                            <button
                                key={pageNumber}
                                onClick={() => onPageChange(pageNumber)}
                                className={`px-3 py-1 text-sm border rounded ${
                                    pageNumber === currentPage
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
                    onClick={() => onPageChange(currentPage + 1)}
                    disabled={currentPage === lastPage}
                    className="px-3 py-1 text-sm border border-gray-300 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                >
                    次へ
                </button>
            </div>
            
            <div className="text-sm text-gray-600">
                ページ {currentPage} / {lastPage}
            </div>
        </div>
    );
};

export default ArticlePagination;