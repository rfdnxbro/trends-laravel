import React from 'react';
import { Link } from 'react-router-dom';
import { BuildingOfficeIcon, GlobeAltIcon } from '@heroicons/react/24/outline';
import { Company } from '../types';

interface CompanyCardProps {
    company: Company;
    onClick?: () => void;
    showActions?: boolean;
}

const CompanyCard: React.FC<CompanyCardProps> = ({ 
    company, 
    onClick,
    showActions = true 
}) => {
    const handleClick = () => {
        if (onClick) {
            onClick();
        }
    };

    return (
        <div 
            className={`bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200 ${
                onClick ? 'cursor-pointer' : ''
            }`}
            onClick={handleClick}
        >
            {/* ヘッダー */}
            <div className="flex items-start justify-between mb-4">
                <div className="flex items-center space-x-3">
                    {company.logo_url ? (
                        <img 
                            src={company.logo_url} 
                            alt={`${company.name} logo`}
                            className="w-12 h-12 rounded-lg object-cover border border-gray-200"
                            onError={(e) => {
                                e.currentTarget.style.display = 'none';
                                e.currentTarget.nextElementSibling?.classList.remove('hidden');
                            }}
                        />
                    ) : null}
                    <BuildingOfficeIcon 
                        data-testid="building-icon"
                        className={`w-12 h-12 text-gray-400 p-2 border border-gray-200 rounded-lg bg-gray-50 ${
                            company.logo_url ? 'hidden' : ''
                        }`} 
                    />
                    <div className="flex-1 min-w-0">
                        <h3 className="text-lg font-semibold text-gray-900 truncate">
                            {company.name}
                        </h3>
                        {company.domain && (
                            <div className="flex items-center text-sm text-gray-600 mt-1">
                                <GlobeAltIcon className="w-4 h-4 mr-1" />
                                <span className="truncate">{company.domain}</span>
                            </div>
                        )}
                    </div>
                </div>
                
                {company.is_active !== undefined && (
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        company.is_active
                            ? 'bg-green-100 text-green-800'
                            : 'bg-gray-100 text-gray-800'
                    }`}>
                        {company.is_active ? 'アクティブ' : '非アクティブ'}
                    </span>
                )}
            </div>

            {/* 説明 */}
            {company.description && (
                <div className="mb-4">
                    <p className="text-sm text-gray-600 line-clamp-3">
                        {company.description}
                    </p>
                </div>
            )}

            {/* メタ情報 */}
            <div className="mb-4 space-y-2">
                {company.influence_score !== undefined && (
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-gray-600">影響力スコア</span>
                        <span className="font-medium text-blue-600">
                            {company.influence_score.toFixed(2)}
                        </span>
                    </div>
                )}
                
                {company.ranking !== undefined && (
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-gray-600">ランキング</span>
                        <span className="font-medium text-orange-600">
                            #{company.ranking}
                        </span>
                    </div>
                )}

                {company.website_url && (
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-gray-600">ウェブサイト</span>
                        <a 
                            href={company.website_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-blue-600 hover:text-blue-800 truncate max-w-xs"
                            onClick={(e) => e.stopPropagation()}
                        >
                            {company.website_url.replace(/^https?:\/\//, '')}
                        </a>
                    </div>
                )}
            </div>

            {/* SNS リンク */}
            {(company.qiita_username || company.zenn_username) && (
                <div className="mb-4">
                    <div className="text-xs text-gray-500 mb-2">SNS アカウント</div>
                    <div className="flex space-x-2">
                        {company.qiita_username && (
                            <a
                                href={`https://qiita.com/${company.qiita_username}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded hover:bg-green-200"
                                onClick={(e) => e.stopPropagation()}
                            >
                                Qiita
                            </a>
                        )}
                        {company.zenn_username && (
                            <a
                                href={`https://zenn.dev/${company.zenn_username}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded hover:bg-blue-200"
                                onClick={(e) => e.stopPropagation()}
                            >
                                Zenn
                            </a>
                        )}
                    </div>
                </div>
            )}

            {/* アクション */}
            {showActions && (
                <div className="flex justify-between items-center pt-4 border-t border-gray-100">
                    <div className="text-xs text-gray-500">
                        登録: {new Date(company.created_at).toLocaleDateString('ja-JP')}
                    </div>
                    <Link 
                        to={`/companies/${company.id}`}
                        className="inline-flex items-center px-3 py-1 text-sm font-medium text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition-colors"
                        onClick={(e) => e.stopPropagation()}
                    >
                        詳細を見る
                    </Link>
                </div>
            )}
        </div>
    );
};

export default CompanyCard;