import React from 'react';
import { Link } from 'react-router-dom';
import { Company, Article } from '../types';

interface SearchResultsProps {
    companies?: Company[];
    articles?: Article[];
    loading?: boolean;
    query: string;
    totalResults: number;
    searchTime: number;
}

const SearchResults: React.FC<SearchResultsProps> = ({
    companies = [],
    articles = [],
    loading = false,
    query,
    totalResults,
    searchTime
}) => {
    if (loading) {
        return (
            <div className="flex justify-center items-center py-12">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span className="ml-2 text-gray-600">検索中...</span>
            </div>
        );
    }

    if (totalResults === 0) {
        return (
            <div className="text-center py-12">
                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <h3 className="mt-4 text-lg font-medium text-gray-900">検索結果が見つかりません</h3>
                <p className="mt-2 text-gray-500">
                    「{query}」に一致する結果がありませんでした。
                </p>
                <p className="text-gray-500">
                    別のキーワードで検索してみてください。
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* 検索結果サマリー */}
            <div className="bg-gray-50 px-4 py-3 rounded-lg">
                <p className="text-sm text-gray-600">
                    「<span className="font-medium">{query}</span>」の検索結果 {totalResults}件
                    <span className="ml-2 text-gray-500">({searchTime}秒)</span>
                </p>
            </div>

            {/* 企業の検索結果 */}
            {companies.length > 0 && (
                <div>
                    <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <svg className="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm3 1h6v4H7V5zm8 8v2a1 1 0 01-1 1H6a1 1 0 01-1-1v-2h10z" clipRule="evenodd" />
                        </svg>
                        企業 ({companies.length}件)
                    </h3>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {companies.map((company) => (
                            <CompanySearchResult key={company.id} company={company} />
                        ))}
                    </div>
                </div>
            )}

            {/* 記事の検索結果 */}
            {articles.length > 0 && (
                <div>
                    <h3 className="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <svg className="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clipRule="evenodd" />
                        </svg>
                        記事 ({articles.length}件)
                    </h3>
                    <div className="space-y-4">
                        {articles.map((article) => (
                            <ArticleSearchResult key={article.id} article={article} />
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};

const CompanySearchResult: React.FC<{ company: Company }> = ({ company }) => {
    const matchScore = (company as any).match_score;
    
    return (
        <Link
            to={`/companies/${company.id}`}
            className="block p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
        >
            <div className="flex items-start space-x-3">
                {company.logo_url ? (
                    <img
                        src={company.logo_url}
                        alt={`${company.name} logo`}
                        className="w-10 h-10 rounded object-cover flex-shrink-0"
                    />
                ) : (
                    <div className="w-10 h-10 bg-gray-200 rounded flex items-center justify-center flex-shrink-0">
                        <svg className="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm3 1h6v4H7V5zm8 8v2a1 1 0 01-1 1H6a1 1 0 01-1-1v-2h10z" clipRule="evenodd" />
                        </svg>
                    </div>
                )}
                <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between">
                        <h4 className="text-sm font-medium text-gray-900 truncate">
                            {company.name}
                        </h4>
                        {matchScore && (
                            <span className="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                {Math.round(matchScore * 100)}%
                            </span>
                        )}
                    </div>
                    <p className="text-sm text-gray-500 truncate">{company.domain}</p>
                    {company.description && (
                        <p className="text-xs text-gray-400 mt-1 line-clamp-2">
                            {company.description}
                        </p>
                    )}
                </div>
            </div>
        </Link>
    );
};

const ArticleSearchResult: React.FC<{ article: Article }> = ({ article }) => {
    const matchScore = (article as any).match_score;
    
    return (
        <div className="p-4 bg-white border border-gray-200 rounded-lg">
            <div className="flex items-start justify-between">
                <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between">
                        <a
                            href={article.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-blue-600 hover:text-blue-800 font-medium text-sm leading-tight hover:underline"
                        >
                            {article.title}
                        </a>
                        {matchScore && (
                            <span className="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded flex-shrink-0">
                                {Math.round(matchScore * 100)}%
                            </span>
                        )}
                    </div>
                    
                    <div className="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                        <span className="flex items-center">
                            <svg className="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                            </svg>
                            {article.author_name || 'Unknown'}
                        </span>
                        
                        <span className="flex items-center">
                            <svg className="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 011 1v1a1 1 0 01-1 1H4a1 1 0 01-1-1v-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clipRule="evenodd" />
                            </svg>
                            {article.engagement_count} bookmarks
                        </span>
                        
                        <span>{article.platform?.name || article.domain}</span>
                        
                        <span>
                            {new Date(article.published_at).toLocaleDateString('ja-JP')}
                        </span>
                    </div>
                    
                    {article.company && (
                        <div className="mt-2">
                            <Link
                                to={`/companies/${article.company.id}`}
                                className="inline-flex items-center text-xs text-blue-600 hover:text-blue-800"
                            >
                                <svg className="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm3 1h6v4H7V5zm8 8v2a1 1 0 01-1 1H6a1 1 0 01-1-1v-2h10z" clipRule="evenodd" />
                                </svg>
                                {article.company.name}
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default SearchResults;