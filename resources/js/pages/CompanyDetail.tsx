import React from 'react';
import { useParams } from 'react-router-dom';

const CompanyDetail: React.FC = () => {
    const { id } = useParams<{ id: string }>();

    return (
        <div>
            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900">企業詳細</h1>
                <p className="text-gray-600">企業ID: {id}</p>
            </div>
            
            <div className="bg-white rounded-lg shadow p-6">
                <h2 className="text-lg font-semibold text-gray-900 mb-4">企業情報</h2>
                <p className="text-gray-500">企業データを読み込み中...</p>
            </div>
        </div>
    );
};

export default CompanyDetail;