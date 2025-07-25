import React, { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import SearchForm from '../components/SearchForm';
import SearchResults from '../components/SearchResults';
import { apiService } from '../services/api';
import { 
    SearchUnifiedParams, 
    SearchCompanyParams, 
    SearchArticleParams,
    SearchUnifiedResponse,
    SearchCompanyResponse,
    SearchArticleResponse,
    Company,
    Article
} from '../types';

const Search: React.FC = () => {
    const [searchParams, setSearchParams] = useSearchParams();
    const [loading, setLoading] = useState(false);
    const [companies, setCompanies] = useState<Company[]>([]);
    const [articles, setArticles] = useState<Article[]>([]);
    const [totalResults, setTotalResults] = useState(0);
    const [searchTime, setSearchTime] = useState(0);
    const [error, setError] = useState<string | null>(null);

    const query = searchParams.get('q') || '';
    const searchType = searchParams.get('type') as 'companies' | 'articles' | 'all' || 'all';
    const limit = parseInt(searchParams.get('limit') || '20');
    const days = parseInt(searchParams.get('days') || '30');
    const minEngagement = parseInt(searchParams.get('min_engagement') || '0');

    const performSearch = async (searchQuery: string, type: 'companies' | 'articles' | 'all') => {
        if (!searchQuery.trim()) {
            setCompanies([]);
            setArticles([]);
            setTotalResults(0);
            setSearchTime(0);
            return;
        }

        setLoading(true);
        setError(null);

        try {
            if (type === 'companies') {
                const params: SearchCompanyParams = {
                    q: searchQuery,
                    limit
                };
                const response = await apiService.searchCompanies(params);
                const data = response.data as SearchCompanyResponse;
                
                setCompanies(data.data.companies);
                setArticles([]);
                setTotalResults(data.meta.total_results);
                setSearchTime(data.meta.search_time);
            } else if (type === 'articles') {
                const params: SearchArticleParams = {
                    q: searchQuery,
                    limit,
                    days,
                    min_engagement: minEngagement
                };
                const response = await apiService.searchArticles(params);
                const data = response.data as SearchArticleResponse;
                
                setCompanies([]);
                setArticles(data.data.articles);
                setTotalResults(data.meta.total_results);
                setSearchTime(data.meta.search_time);
            } else {
                const params: SearchUnifiedParams = {
                    q: searchQuery,
                    type: 'all',
                    limit,
                    days,
                    min_engagement: minEngagement
                };
                const response = await apiService.searchUnified(params);
                const data = response.data as SearchUnifiedResponse;
                
                setCompanies(data.data.companies || []);
                setArticles(data.data.articles || []);
                setTotalResults(data.meta.total_results);
                setSearchTime(data.meta.search_time);
            }
        } catch (err) {
            console.error('Search error:', err);
            setError('検索中にエラーが発生しました。もう一度お試しください。');
            setCompanies([]);
            setArticles([]);
            setTotalResults(0);
            setSearchTime(0);
        } finally {
            setLoading(false);
        }
    };

    const handleSearch = (searchQuery: string, type: 'companies' | 'articles' | 'all') => {
        const params = new URLSearchParams();
        params.set('q', searchQuery);
        if (type !== 'all') {
            params.set('type', type);
        }
        if (limit !== 20) {
            params.set('limit', limit.toString());
        }
        if (days !== 30) {
            params.set('days', days.toString());
        }
        if (minEngagement !== 0) {
            params.set('min_engagement', minEngagement.toString());
        }
        
        setSearchParams(params);
    };

    // URLパラメータが変更されたら検索を実行
    useEffect(() => {
        if (query) {
            performSearch(query, searchType);
        }
    }, [query, searchType, limit, days, minEngagement]);

    return (
        <div className="max-w-4xl mx-auto px-4 py-8">
            <div className="mb-8">
                <h1 className="text-3xl font-bold text-gray-900 mb-6">検索</h1>
                
                <SearchForm
                    onSearch={handleSearch}
                    initialQuery={query}
                    initialType={searchType}
                    showTypeFilter={true}
                    className="mb-6"
                />

                {/* 詳細フィルター（記事検索時のみ表示） */}
                {(searchType === 'articles' || searchType === 'all') && (
                    <div className="bg-gray-50 p-4 rounded-lg mb-6">
                        <h3 className="text-sm font-medium text-gray-700 mb-3">詳細フィルター</h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">
                                    検索期間
                                </label>
                                <select
                                    value={days}
                                    onChange={(e) => {
                                        const params = new URLSearchParams(searchParams);
                                        params.set('days', e.target.value);
                                        setSearchParams(params);
                                    }}
                                    className="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="7">過去1週間</option>
                                    <option value="30">過去1ヶ月</option>
                                    <option value="90">過去3ヶ月</option>
                                    <option value="365">過去1年</option>
                                </select>
                            </div>
                            
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">
                                    最小エンゲージメント数
                                </label>
                                <select
                                    value={minEngagement}
                                    onChange={(e) => {
                                        const params = new URLSearchParams(searchParams);
                                        params.set('min_engagement', e.target.value);
                                        setSearchParams(params);
                                    }}
                                    className="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="0">制限なし</option>
                                    <option value="5">5件以上</option>
                                    <option value="10">10件以上</option>
                                    <option value="50">50件以上</option>
                                    <option value="100">100件以上</option>
                                </select>
                            </div>
                            
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">
                                    表示件数
                                </label>
                                <select
                                    value={limit}
                                    onChange={(e) => {
                                        const params = new URLSearchParams(searchParams);
                                        params.set('limit', e.target.value);
                                        setSearchParams(params);
                                    }}
                                    className="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="10">10件</option>
                                    <option value="20">20件</option>
                                    <option value="50">50件</option>
                                    <option value="100">100件</option>
                                </select>
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* エラー表示 */}
            {error && (
                <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div className="flex">
                        <svg className="w-5 h-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                        </svg>
                        <div className="ml-3">
                            <p className="text-sm text-red-800">{error}</p>
                        </div>
                    </div>
                </div>
            )}

            {/* 検索結果 */}
            {query && (
                <SearchResults
                    companies={companies}
                    articles={articles}
                    loading={loading}
                    query={query}
                    totalResults={totalResults}
                    searchTime={searchTime}
                />
            )}

            {/* 初期状態（検索前） */}
            {!query && (
                <div className="text-center py-12">
                    <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <h3 className="mt-4 text-lg font-medium text-gray-900">検索キーワードを入力してください</h3>
                    <p className="mt-2 text-gray-500">
                        企業名、記事タイトル、著者名などで検索できます。
                    </p>
                </div>
            )}
        </div>
    );
};

export default Search;