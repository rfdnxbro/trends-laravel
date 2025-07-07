import React from 'react';
import { ChevronUpIcon, ChevronDownIcon, MinusIcon } from '@heroicons/react/24/solid';
import { RankingCardProps } from '../types';

const RankingCard: React.FC<RankingCardProps> = ({
    ranking,
    onClick,
    showRankChange = true
}) => {
    const formatScore = (score: number): string => {
        return score.toLocaleString(undefined, { maximumFractionDigits: 1 });
    };

    const getRankChangeIcon = () => {
        if (!showRankChange || ranking.rank_change === undefined || ranking.rank_change === null) {
            return null;
        }

        if (ranking.rank_change > 0) {
            return (
                <div className="flex items-center text-green-600">
                    <ChevronUpIcon className="w-4 h-4" />
                    <span className="text-sm font-medium">+{ranking.rank_change}</span>
                </div>
            );
        } else if (ranking.rank_change < 0) {
            return (
                <div className="flex items-center text-red-600">
                    <ChevronDownIcon className="w-4 h-4" />
                    <span className="text-sm font-medium">{ranking.rank_change}</span>
                </div>
            );
        } else {
            return (
                <div className="flex items-center text-gray-500">
                    <MinusIcon className="w-4 h-4" />
                    <span className="text-sm">変動なし</span>
                </div>
            );
        }
    };

    return (
        <div
            className={`bg-white rounded-lg shadow-sm border border-gray-200 p-4 transition-all duration-200 ${
                onClick ? 'hover:shadow-md hover:border-gray-300 cursor-pointer' : ''
            }`}
            onClick={onClick}
        >
            <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                    <div className="flex-shrink-0">
                        <div className="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                            <span className="text-lg font-bold text-gray-700">
                                #{ranking.rank}
                            </span>
                        </div>
                    </div>
                    
                    <div className="flex-1 min-w-0">
                        <h3 className="text-lg font-semibold text-gray-900 truncate">
                            {ranking.company.name}
                        </h3>
                        {ranking.company.description && (
                            <p className="text-sm text-gray-600 truncate">
                                {ranking.company.description}
                            </p>
                        )}
                        <div className="flex items-center space-x-4 mt-2">
                            <span className="text-sm text-gray-500">
                                スコア: <span className="font-medium text-gray-900">
                                    {formatScore(ranking.influence_score)}
                                </span>
                            </span>
                            {ranking.company.website && (
                                <a
                                    href={ranking.company.website}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-sm text-blue-600 hover:text-blue-800"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    公式サイト
                                </a>
                            )}
                        </div>
                    </div>
                </div>
                
                <div className="flex-shrink-0">
                    {getRankChangeIcon()}
                </div>
            </div>
            
            <div className="mt-4 flex flex-wrap gap-2">
                {ranking.company.hatena_username && (
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                        はてな
                    </span>
                )}
                {ranking.company.qiita_username && (
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Qiita
                    </span>
                )}
                {ranking.company.zenn_username && (
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        Zenn
                    </span>
                )}
            </div>
        </div>
    );
};

export default RankingCard;