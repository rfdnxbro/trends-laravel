import React from 'react';

const Dashboard: React.FC = () => {
    return (
        <div>
            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900">ダッシュボード</h1>
                <p className="text-gray-600">企業の影響力スコアとランキング情報を表示します</p>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div className="bg-white rounded-lg shadow p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">総企業数</h3>
                    <p className="text-3xl font-bold text-blue-600">-</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">記事総数</h3>
                    <p className="text-3xl font-bold text-green-600">-</p>
                </div>
                <div className="bg-white rounded-lg shadow p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">プラットフォーム数</h3>
                    <p className="text-3xl font-bold text-purple-600">-</p>
                </div>
            </div>

            <div className="bg-white rounded-lg shadow">
                <div className="px-6 py-4 border-b">
                    <h2 className="text-lg font-semibold text-gray-900">最新ランキング</h2>
                </div>
                <div className="p-6">
                    <p className="text-gray-500">ランキングデータを読み込み中...</p>
                </div>
            </div>
        </div>
    );
};

export default Dashboard;