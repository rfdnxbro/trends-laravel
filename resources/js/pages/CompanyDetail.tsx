import React from 'react';
import { useParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { api } from '../services/api';
import { Company, Article, QueryKeys } from '../types';

interface CompanyDetailData extends Company {
    recent_articles?: Article[];
    total_articles?: number;
    ranking_history?: Array<{
        date: string;
        rank: number;
        influence_score: number;
    }>;
}

const CompanyDetail: React.FC = () => {
    const { id } = useParams<{ id: string }>();
    const companyId = id ? parseInt(id, 10) : 0;

    const { data: company, isLoading, error } = useQuery({
        queryKey: QueryKeys.COMPANY_DETAIL(companyId),
        queryFn: () => api.get<{data: CompanyDetailData}>(`/api/companies/${companyId}`).then(res => res.data.data),
        enabled: !!companyId,
        retry: 1,
    });

    if (isLoading) {
        return (
            <div className="flex items-center justify-center py-12">
                <span className="loading-spinner mr-3"></span>
                <span className="text-gray-600">企業データを読み込み中...</span>
            </div>
        );
    }

    if (error || !company) {
        return (
            <div className="dashboard-card">
                <div className="text-center py-8">
                    <h2 className="text-lg font-medium text-gray-900 mb-2">企業が見つかりません</h2>
                    <p className="text-gray-600 mb-4">指定された企業IDの情報を取得できませんでした。</p>
                    <Link to="/" className="btn-primary">
                        ダッシュボードに戻る
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div>
            {/* パンくずナビ */}
            <nav className="flex mb-8" aria-label="Breadcrumb">
                <ol className="flex items-center space-x-4">
                    <li>
                        <Link to="/" className="text-gray-400 hover:text-gray-500">
                            ダッシュボード
                        </Link>
                    </li>
                    <li>
                        <span className="text-gray-400">/</span>
                    </li>
                    <li>
                        <span className="text-gray-900 font-medium">{company.name}</span>
                    </li>
                </ol>
            </nav>

            {/* 企業基本情報 */}
            <div className="dashboard-card mb-8">
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">{company.name}</h1>
                        {company.description && (
                            <p className="text-gray-600 mb-4">{company.description}</p>
                        )}
                        <div className="flex items-center space-x-6">
                            {company.influence_score && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">影響力スコア</span>
                                    <p className="text-2xl font-bold text-blue-600">
                                        {company.influence_score.toFixed(2)}
                                    </p>
                                </div>
                            )}
                            {company.ranking && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500">ランキング</span>
                                    <p className="text-2xl font-bold text-green-600">
                                        #{company.ranking}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-col space-y-2">
                        {company.website_url && (
                            <a 
                                href={company.website_url} 
                                target="_blank" 
                                rel="noopener noreferrer"
                                className="btn-secondary text-center"
                            >
                                ウェブサイト
                            </a>
                        )}
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* プラットフォーム情報 */}
                <div className="dashboard-card">
                    <div className="dashboard-card-header">
                        プラットフォーム連携
                    </div>
                    <div className="space-y-3">
                        {company.hatena_username && (
                            <div className="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                                <span className="font-medium text-orange-900">はてなブログ</span>
                                <span className="text-sm text-orange-700">@{company.hatena_username}</span>
                            </div>
                        )}
                        {company.qiita_username && (
                            <div className="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <span className="font-medium text-green-900">Qiita</span>
                                <span className="text-sm text-green-700">@{company.qiita_username}</span>
                            </div>
                        )}
                        {company.zenn_username && (
                            <div className="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <span className="font-medium text-blue-900">Zenn</span>
                                <span className="text-sm text-blue-700">@{company.zenn_username}</span>
                            </div>
                        )}
                        {!company.hatena_username && !company.qiita_username && !company.zenn_username && (
                            <p className="text-gray-500 text-center py-4">
                                プラットフォーム連携なし
                            </p>
                        )}
                    </div>
                </div>

                {/* 統計情報 */}
                <div className="dashboard-card">
                    <div className="dashboard-card-header">
                        統計情報
                    </div>
                    <div className="space-y-4">
                        <div>
                            <span className="text-sm font-medium text-gray-500">総記事数</span>
                            <p className="text-2xl font-bold text-blue-600">
                                {company.total_articles || 0}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-gray-500">登録日</span>
                            <p className="text-sm text-gray-900">
                                {new Date(company.created_at).toLocaleDateString('ja-JP')}
                            </p>
                        </div>
                        <div>
                            <span className="text-sm font-medium text-gray-500">最終更新</span>
                            <p className="text-sm text-gray-900">
                                {new Date(company.updated_at).toLocaleDateString('ja-JP')}
                            </p>
                        </div>
                    </div>
                </div>

                {/* ランキング履歴 */}
                <div className="dashboard-card">
                    <div className="dashboard-card-header">
                        ランキング推移
                    </div>
                    <div>
                        {company.ranking_history && company.ranking_history.length > 0 ? (
                            <div className="space-y-2">
                                {company.ranking_history.slice(0, 5).map((history, index) => (
                                    <div key={index} className="flex items-center justify-between p-2 border rounded">
                                        <span className="text-sm text-gray-600">
                                            {new Date(history.date).toLocaleDateString('ja-JP')}
                                        </span>
                                        <span className="font-medium">#{history.rank}</span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-gray-500 text-center py-4">
                                ランキング履歴なし
                            </p>
                        )}
                    </div>
                </div>
            </div>

            {/* 最新記事 */}
            {company.recent_articles && company.recent_articles.length > 0 && (
                <div className="dashboard-card mt-8">
                    <div className="dashboard-card-header">
                        最新記事
                    </div>
                    <div className="space-y-4">
                        {company.recent_articles.map((article) => (
                            <div key={article.id} className="border-l-4 border-blue-500 pl-4 py-2">
                                <h3 className="font-medium text-gray-900 mb-1">
                                    <a 
                                        href={article.url} 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        className="hover:text-blue-600"
                                    >
                                        {article.title}
                                    </a>
                                </h3>
                                <div className="flex items-center space-x-4 text-sm text-gray-500">
                                    <span>{article.platform.name}</span>
                                    <span>{new Date(article.published_at).toLocaleDateString('ja-JP')}</span>
                                    {article.engagement_count > 0 && (
                                        <span>エンゲージメント: {article.engagement_count}</span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};

export default CompanyDetail;