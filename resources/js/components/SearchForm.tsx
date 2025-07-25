import React, { useState, FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';

interface SearchFormProps {
    onSearch?: (query: string, type: 'companies' | 'articles' | 'all') => void;
    initialQuery?: string;
    initialType?: 'companies' | 'articles' | 'all';
    showTypeFilter?: boolean;
    placeholder?: string;
    className?: string;
}

const SearchForm: React.FC<SearchFormProps> = ({
    onSearch,
    initialQuery = '',
    initialType = 'all',
    showTypeFilter = true,
    placeholder = '企業や記事を検索...',
    className = ''
}) => {
    const [query, setQuery] = useState(initialQuery);
    const [searchType, setSearchType] = useState<'companies' | 'articles' | 'all'>(initialType);
    const navigate = useNavigate();

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        if (!query.trim()) return;

        if (onSearch) {
            onSearch(query.trim(), searchType);
        } else {
            // デフォルト動作：検索ページへ遷移
            const params = new URLSearchParams();
            params.set('q', query.trim());
            if (searchType !== 'all') {
                params.set('type', searchType);
            }
            navigate(`/search?${params.toString()}`);
        }
    };

    return (
        <form onSubmit={handleSubmit} className={`flex flex-col sm:flex-row gap-2 ${className}`}>
            <div className="flex-1 relative">
                <input
                    type="text"
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    placeholder={placeholder}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                />
                <div className="absolute inset-y-0 right-0 flex items-center pr-3">
                    <svg
                        className="h-5 w-5 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                </div>
            </div>
            
            {showTypeFilter && (
                <select
                    value={searchType}
                    onChange={(e) => setSearchType(e.target.value as 'companies' | 'articles' | 'all')}
                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white"
                >
                    <option value="all">すべて</option>
                    <option value="companies">企業</option>
                    <option value="articles">記事</option>
                </select>
            )}
            
            <button
                type="submit"
                disabled={!query.trim()}
                className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
            >
                検索
            </button>
        </form>
    );
};

export default SearchForm;