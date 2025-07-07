import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { api } from '../services/api';
import { DashboardStats, TopCompany, QueryKeys } from '../types';

const Dashboard: React.FC = () => {
    // ダッシュボード統計データの取得
    const { data: stats, isLoading: statsLoading } = useQuery({
        queryKey: QueryKeys.DASHBOARD_STATS,
        queryFn: () => api.get<DashboardStats>('/api/dashboard/stats').then(res => res.data),
        retry: 1,
    });

    // 上位企業ランキングの取得  
    const { data: topCompanies, isLoading: companiesLoading } = useQuery({
        queryKey: QueryKeys.TOP_COMPANIES,
        queryFn: () => api.get<TopCompany[]>('/api/companies/top?limit=10').then(res => res.data),
        retry: 1,
    });

    return (
        <div>
            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900">ダッシュボード</h1>
                <p className="text-gray-600">企業の影響力スコアとランキング情報を表示します</p>
            </div>
            
            {/* 統計カード */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div className="metric-card">
                    <h3 className="metric-label mb-2">総企業数</h3>
                    <p className="metric-value">
                        {statsLoading ? (
                            <span className="loading-spinner inline-block"></span>
                        ) : (
                            stats?.totalCompanies || '0'
                        )}
                    </p>
                </div>
                <div className="metric-card">
                    <h3 className="metric-label mb-2">記事総数</h3>
                    <p className="metric-value">
                        {statsLoading ? (
                            <span className="loading-spinner inline-block"></span>
                        ) : (
                            stats?.totalArticles || '0'
                        )}
                    </p>
                </div>
                <div className="metric-card">
                    <h3 className="metric-label mb-2">プラットフォーム数</h3>
                    <p className="metric-value">
                        {statsLoading ? (
                            <span className="loading-spinner inline-block"></span>
                        ) : (
                            stats?.totalPlatforms || '0'
                        )}
                    </p>
                </div>
            </div>

            {/* 上位企業ランキング */}
            <div className="dashboard-card">
                <div className="dashboard-card-header">
                    最新ランキング（TOP 10）
                </div>
                <div>
                    {companiesLoading ? (
                        <div className="flex items-center justify-center py-8">
                            <span className="loading-spinner mr-3"></span>
                            <span className="text-gray-600">ランキングデータを読み込み中...</span>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="data-table">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th>順位</th>
                                        <th>企業名</th>
                                        <th>影響力スコア</th>
                                        <th>詳細</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {topCompanies?.map((company) => (
                                        <tr key={company.id} className="hover:bg-gray-50">
                                            <td className="font-medium">#{company.ranking}</td>
                                            <td className="font-medium text-blue-900">{company.name}</td>
                                            <td>
                                                <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    {company.influence_score.toFixed(2)}
                                                </span>
                                            </td>
                                            <td>
                                                <button className="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                    詳細を見る
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {(!topCompanies || topCompanies.length === 0) && (
                                <div className="text-center py-8 text-gray-500">
                                    ランキングデータがありません
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default Dashboard;