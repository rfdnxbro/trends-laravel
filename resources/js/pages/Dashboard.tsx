import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { api } from '../services/api';
import { RankingStatsResponse, TopCompaniesResponse, QueryKeys } from '../types';

// ヘルパーコンポーネント: 統計メトリックカード
const MetricCard: React.FC<{ label: string; value: string | number; isLoading: boolean }> = ({ 
    label, 
    value, 
    isLoading 
}) => (
    <div className="metric-card">
        <h3 className="metric-label mb-2">{label}</h3>
        <p className="metric-value">
            {isLoading ? (
                <span className="loading-spinner inline-block"></span>
            ) : (
                value || '0'
            )}
        </p>
    </div>
);

// ヘルパーコンポーネント: ランキングテーブル行
const RankingRow: React.FC<{ company: any }> = ({ company }) => (
    <tr key={company.id || company.company.name} className="hover:bg-gray-50">
        <td className="font-medium">#{company.rank_position}</td>
        <td className="font-medium text-blue-900">{company.company.name}</td>
        <td>
            <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                {company.total_score.toFixed(2)}
            </span>
        </td>
        <td>
            <Link 
                to={`/companies/${company.company.id}`}
                className="text-blue-600 hover:text-blue-900 text-sm font-medium"
            >
                詳細を見る
            </Link>
        </td>
    </tr>
);

// ヘルパーコンポーネント: ランキングテーブル
const RankingTable: React.FC<{ companies: any[]; isLoading: boolean }> = ({ 
    companies, 
    isLoading 
}) => {
    if (isLoading) {
        return (
            <div className="flex items-center justify-center py-8">
                <span className="loading-spinner mr-3"></span>
                <span className="text-gray-600">ランキングデータを読み込み中...</span>
            </div>
        );
    }

    return (
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
                    {companies?.map((company) => (
                        <RankingRow key={company.id || company.company.name} company={company} />
                    ))}
                </tbody>
            </table>
            {(!companies || companies.length === 0) && (
                <div className="text-center py-8 text-gray-500">
                    ランキングデータがありません
                </div>
            )}
        </div>
    );
};

const Dashboard: React.FC = () => {
    // ダッシュボード統計データの取得
    const { data: statsResponse, isLoading: statsLoading } = useQuery({
        queryKey: QueryKeys.DASHBOARD_STATS,
        queryFn: () => api.get<RankingStatsResponse>('/api/rankings/statistics').then(res => res.data),
        retry: 1,
    });

    // 上位企業ランキングの取得  
    const { data: topCompaniesResponse, isLoading: companiesLoading } = useQuery({
        queryKey: QueryKeys.TOP_COMPANIES,
        queryFn: () => api.get<TopCompaniesResponse>('/api/rankings/1m/top/10').then(res => res.data),
        retry: 1,
    });

    const statsData = statsResponse?.data?.['1m'];

    return (
        <div>
            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900">ダッシュボード</h1>
                <p className="text-gray-600">企業の影響力スコアとランキング情報を表示します</p>
            </div>
            
            {/* 統計カード */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <MetricCard 
                    label="総企業数" 
                    value={statsData?.total_companies || '0'} 
                    isLoading={statsLoading} 
                />
                <MetricCard 
                    label="記事総数" 
                    value={statsData?.total_articles || '0'} 
                    isLoading={statsLoading} 
                />
                <MetricCard 
                    label="総ブックマーク数" 
                    value={statsData?.total_bookmarks || '0'} 
                    isLoading={statsLoading} 
                />
            </div>

            {/* 上位企業ランキング */}
            <div className="dashboard-card">
                <div className="dashboard-card-header">
                    最新ランキング（TOP 10）
                </div>
                <div>
                    <RankingTable 
                        companies={topCompaniesResponse?.data || []} 
                        isLoading={companiesLoading} 
                    />
                </div>
            </div>
        </div>
    );
};

export default Dashboard;