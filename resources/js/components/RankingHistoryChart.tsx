import React, { useMemo } from 'react';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    ChartOptions,
} from 'chart.js';
import { Line } from 'react-chartjs-2';
import { RankingHistoryChartProps } from '../types/index';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
);

const RankingHistoryChart: React.FC<RankingHistoryChartProps> = ({
    data,
    config = {},
    className = '',
    companyId,
    maxRank = 100
}) => {
    const defaultConfig = {
        responsive: true,
        maintainAspectRatio: false,
        showLegend: true,
        showTooltips: true,
        height: 400,
        ...config
    };

    const chartData = useMemo(() => {
        const sortedData = [...data].sort((a, b) => 
            new Date(a.date).getTime() - new Date(b.date).getTime()
        );

        return {
            labels: sortedData.map(item => item.date),
            datasets: [
                {
                    label: '順位',
                    data: sortedData.map(item => item.rank),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.1,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    yAxisID: 'y',
                },
                {
                    label: '影響力スコア',
                    data: sortedData.map(item => item.score),
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: false,
                    tension: 0.1,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: 'rgb(16, 185, 129)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    yAxisID: 'y1',
                }
            ]
        };
    }, [data]);

    const options: ChartOptions<'line'> = {
        responsive: defaultConfig.responsive,
        maintainAspectRatio: defaultConfig.maintainAspectRatio,
        plugins: {
            title: {
                display: true,
                text: data.length > 0 ? `${data[0].companyName} の順位推移` : '順位推移',
                font: {
                    size: 16,
                    weight: 'bold'
                }
            },
            legend: {
                display: defaultConfig.showLegend,
                position: 'top' as const,
            },
            tooltip: {
                enabled: defaultConfig.showTooltips,
                mode: 'index' as const,
                intersect: false,
                callbacks: {
                    title: (context) => {
                        return `日付: ${context[0].label}`;
                    },
                    label: (context) => {
                        const label = context.dataset.label || '';
                        const value = context.parsed.y;
                        
                        if (label === '順位') {
                            return `${label}: ${value}位`;
                        } else if (label === '影響力スコア') {
                            return `${label}: ${value.toFixed(1)}`;
                        }
                        return `${label}: ${value}`;
                    }
                }
            },
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: '日付'
                },
                grid: {
                    display: true,
                    color: 'rgba(156, 163, 175, 0.2)'
                }
            },
            y: {
                type: 'linear' as const,
                display: true,
                position: 'left' as const,
                title: {
                    display: true,
                    text: '順位'
                },
                grid: {
                    display: true,
                    color: 'rgba(156, 163, 175, 0.2)'
                },
                min: 1,
                max: maxRank,
                reverse: true,
                ticks: {
                    callback: function(value) {
                        return `${value}位`;
                    }
                }
            },
            y1: {
                type: 'linear' as const,
                display: true,
                position: 'right' as const,
                title: {
                    display: true,
                    text: '影響力スコア'
                },
                grid: {
                    drawOnChartArea: false,
                },
                min: 0,
                ticks: {
                    callback: function(value) {
                        return typeof value === 'number' ? value.toFixed(1) : value;
                    }
                }
            },
        },
        interaction: {
            mode: 'nearest' as const,
            axis: 'x' as const,
            intersect: false,
        },
    };

    if (!data || data.length === 0) {
        return (
            <div className={`flex items-center justify-center h-64 bg-gray-50 rounded-lg ${className}`}>
                <div className="text-center">
                    <p className="text-gray-500">データがありません</p>
                    <p className="text-sm text-gray-400 mt-1">順位履歴データがありません</p>
                </div>
            </div>
        );
    }

    // 統計情報を計算
    const stats = useMemo(() => {
        const ranks = data.map(item => item.rank);
        const scores = data.map(item => item.score);
        
        const bestRank = Math.min(...ranks);
        const worstRank = Math.max(...ranks);
        const avgScore = scores.reduce((sum, score) => sum + score, 0) / scores.length;
        const latestRank = data[data.length - 1]?.rank || 0;
        const previousRank = data[data.length - 2]?.rank || latestRank;
        const rankChange = previousRank - latestRank;

        return {
            bestRank,
            worstRank,
            avgScore,
            latestRank,
            rankChange
        };
    }, [data]);

    return (
        <div className={`bg-white rounded-lg shadow-md p-6 ${className}`}>
            <div style={{ height: defaultConfig.height }}>
                <Line data={chartData} options={options} />
            </div>

            {/* 統計情報 */}
            <div className="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div className="text-center p-3 bg-blue-50 rounded-lg">
                    <div className="text-2xl font-bold text-blue-600">
                        {stats.latestRank}位
                    </div>
                    <div className="text-sm text-gray-600">現在の順位</div>
                    {stats.rankChange !== 0 && (
                        <div className={`text-xs mt-1 ${
                            stats.rankChange > 0 ? 'text-green-600' : 'text-red-600'
                        }`}>
                            {stats.rankChange > 0 ? '↑' : '↓'} {Math.abs(stats.rankChange)}
                        </div>
                    )}
                </div>
                <div className="text-center p-3 bg-green-50 rounded-lg">
                    <div className="text-2xl font-bold text-green-600">
                        {stats.bestRank}位
                    </div>
                    <div className="text-sm text-gray-600">最高順位</div>
                </div>
                <div className="text-center p-3 bg-red-50 rounded-lg">
                    <div className="text-2xl font-bold text-red-600">
                        {stats.worstRank}位
                    </div>
                    <div className="text-sm text-gray-600">最低順位</div>
                </div>
                <div className="text-center p-3 bg-purple-50 rounded-lg">
                    <div className="text-2xl font-bold text-purple-600">
                        {stats.avgScore.toFixed(1)}
                    </div>
                    <div className="text-sm text-gray-600">平均スコア</div>
                </div>
            </div>
        </div>
    );
};

export default RankingHistoryChart;